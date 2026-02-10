<?php

declare(strict_types=1);

namespace Triyatna\PhpValidGame\Providers;

use Psr\Log\LoggerInterface;
use Triyatna\PhpValidGame\Contracts\ProviderInterface;
use Triyatna\PhpValidGame\DTO\ValidationResult;
use Triyatna\PhpValidGame\Enums\Provider;
use Triyatna\PhpValidGame\Enums\StatusCode;
use Triyatna\PhpValidGame\Exceptions\HttpException;
use Triyatna\PhpValidGame\Registry\GameRegistry;
use Triyatna\PhpValidGame\Support\Normalizer;
use Triyatna\PhpValidGame\Transport\HttpClient;

/**
 * Validates game user IDs via the GoPay Games API.
 *
 * Sends a JSON POST to the GoPay Games user-account endpoint
 * and parses the response to determine validity and extract the nickname.
 */
final class GopayGamesProvider implements ProviderInterface
{
    private const API_URL = 'https://gopay.co.id/games/v1/order/user-account';

    private HttpClient $http;

    public function __construct(
        ?HttpClient $http = null,
        private readonly bool $debug = false,
        private readonly ?LoggerInterface $logger = null,
    ) {
        $this->http = $http ?? new HttpClient(debug: $this->debug, logger: $this->logger);
    }

    public function name(): string
    {
        return Provider::GOPAY_GAMES->value;
    }

    public function supports(string $gameCode): bool
    {
        $def = GameRegistry::get($gameCode);

        return $def !== null && !empty($def['gopayCode']);
    }

    public function validate(string $gameCode, string|int $userId, string|int|null $zoneId = null): ValidationResult
    {
        $def = GameRegistry::get($gameCode);

        if (!$def || empty($def['gopayCode'])) {
            return ValidationResult::make(
                status: false,
                code: StatusCode::UNKNOWN_GAME,
                message: "Game '{$gameCode}' is not supported by GoPay Games provider.",
                game: $gameCode,
                userId: $userId,
                zoneId: $zoneId,
                provider: $this->name(),
            );
        }

        // Resolve server name to code if mapping exists
        if ($zoneId !== null && $zoneId !== '') {
            $zoneId = GameRegistry::resolveServer($gameCode, (string) $zoneId);
        }

        // Build JSON payload
        $jsonBody = [
            'code' => $def['gopayCode'],
            'data' => [
                'userId' => (string) $userId,
                'zoneId' => (string) ($zoneId ?? ''),
            ],
        ];

        // Send request
        try {
            $resp = $this->http->postJson(self::API_URL, $jsonBody);
        } catch (HttpException $e) {
            return ValidationResult::make(
                status: false,
                code: StatusCode::HTTP_ERROR,
                message: $e->getMessage(),
                game: $gameCode,
                userId: $userId,
                zoneId: $zoneId,
                provider: $this->name(),
            );
        }

        $status = $resp['status'] ?? 0;
        $body   = $resp['body'] ?? '';

        // Parse JSON
        $data = null;
        if (\is_string($body) && $body !== '') {
            $data = \json_decode($body, true);
        }

        if ($data === null) {
            return ValidationResult::make(
                status: false,
                code: StatusCode::NON_JSON,
                message: 'Received non-JSON or empty response from GoPay Games.',
                game: $gameCode,
                userId: $userId,
                zoneId: $zoneId,
                provider: $this->name(),
                httpStatus: $status,
                meta: $this->debug ? ['raw' => \mb_substr($body, 0, 2000)] : null,
            );
        }

        // Check for success
        // GoPay Games API returns { "success": true, "data": { "userAccount": "Nickname" } }
        // or { "success": false, "message": "error message" }
        if ($this->isSuccessResponse($status, $data)) {
            $nickname = $this->extractNickname($data);

            return ValidationResult::make(
                status: true,
                code: StatusCode::OK,
                message: 'User ID is valid.',
                game: $gameCode,
                userId: $userId,
                zoneId: $zoneId,
                nickname: $nickname,
                provider: $this->name(),
                httpStatus: $status,
                meta: $this->debug ? ['data' => $data] : null,
            );
        }

        // Error response
        $errorMessage = $this->extractErrorMessage($data);

        return ValidationResult::make(
            status: false,
            code: StatusCode::API_ERROR,
            message: $errorMessage,
            game: $gameCode,
            userId: $userId,
            zoneId: $zoneId,
            provider: $this->name(),
            httpStatus: $status,
            meta: $this->debug ? ['data' => $data] : null,
        );
    }

    /**
     * Determine if the API response indicates success.
     *
     * @param array<string, mixed> $data
     */
    private function isSuccessResponse(int $httpStatus, array $data): bool
    {
        if ($httpStatus < 200 || $httpStatus >= 300) {
            return false;
        }

        // Primary: {"message": "Success", "data": {"username": "..."}}
        if (isset($data['message']) && \strtolower((string) $data['message']) === 'success') {
            return true;
        }

        // Alternative: {"success": true, ...}
        if (isset($data['success']) && $data['success'] === true) {
            return true;
        }

        // Alternative: {"status": true, ...}
        if (isset($data['status']) && $data['status'] === true) {
            return true;
        }

        // Fallback: data.username or data.userAccount present
        if (isset($data['data']) && \is_array($data['data'])) {
            $username = $data['data']['username'] ?? $data['data']['userAccount'] ?? null;
            if (\is_string($username) && $username !== '') {
                return true;
            }
        }

        return false;
    }

    /**
     * Extract the player nickname from a successful response.
     *
     * @param array<string, mixed> $data
     */
    private function extractNickname(array $data): ?string
    {
        $paths = [
            'data.username',
            'data.userAccount',
            'data.nickname',
            'data.name',
            'username',
            'userAccount',
        ];

        foreach ($paths as $path) {
            $value = Normalizer::dotGet($data, $path);
            if (\is_string($value) && $value !== '') {
                return $value;
            }
        }

        return null;
    }

    /**
     * Extract an error message from the API response.
     *
     * @param array<string, mixed> $data
     */
    private function extractErrorMessage(array $data): string
    {
        $paths = ['message', 'error', 'data.message', 'errorMessage', 'msg'];

        foreach ($paths as $path) {
            $value = Normalizer::dotGet($data, $path);
            if (\is_string($value) && $value !== '') {
                return $value;
            }
        }

        return 'GoPay Games API returned an error.';
    }
}
