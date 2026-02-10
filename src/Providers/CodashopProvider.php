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
 * Validates game user IDs by scraping the Codashop initPayment endpoint.
 *
 * Sends a form-encoded POST to Codashop's order initialization endpoint
 * and parses the JSON response to determine validity and extract the nickname.
 */
final class CodashopProvider implements ProviderInterface
{
    private const BASE_URL = 'https://order-sg.codashop.com/initPayment.action';

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
        return Provider::CODASHOP->value;
    }

    public function supports(string $gameCode): bool
    {
        $def = GameRegistry::get($gameCode);

        return $def !== null && !empty($def['codashop']);
    }

    public function validate(string $gameCode, string|int $userId, string|int|null $zoneId = null): ValidationResult
    {
        $def = GameRegistry::get($gameCode);

        if (!$def || empty($def['codashop'])) {
            return ValidationResult::make(
                status: false,
                code: StatusCode::UNKNOWN_GAME,
                message: "Game '{$gameCode}' is not supported by Codashop provider.",
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

        // Build payload
        /** @var callable $builder */
        $builder = $def['codashop']['payload'];
        $payload = $builder($userId, $zoneId);

        if (!\is_array($payload)) {
            return ValidationResult::make(
                status: false,
                code: StatusCode::EXCEPTION,
                message: 'Payload builder returned invalid data.',
                game: $gameCode,
                userId: $userId,
                zoneId: $zoneId,
                provider: $this->name(),
            );
        }

        // Send request
        try {
            $resp = $this->http->postForm(self::BASE_URL, $payload);
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
                message: 'Received non-JSON or empty response from Codashop.',
                game: $gameCode,
                userId: $userId,
                zoneId: $zoneId,
                provider: $this->name(),
                httpStatus: $status,
                meta: $this->debug ? ['raw' => \mb_substr($body, 0, 2000)] : null,
            );
        }

        // Success: errorCode present and empty string
        if ($status >= 200 && $status < 300 && isset($data['errorCode']) && $data['errorCode'] === '') {
            [$nickname, $extractedZone] = $this->extractNicknameAndZone($gameCode, $data);

            return ValidationResult::make(
                status: true,
                code: StatusCode::OK,
                message: 'User ID is valid.',
                game: $gameCode,
                userId: $userId,
                zoneId: $extractedZone ?? $zoneId,
                nickname: $nickname,
                provider: $this->name(),
                httpStatus: $status,
                meta: $this->debug ? ['data' => $data] : null,
            );
        }

        // API error
        if (isset($data['errorCode']) && $data['errorCode'] !== '') {
            $msg = $data['errorMsg'] ?? 'API returned an error.';

            return ValidationResult::make(
                status: false,
                code: StatusCode::API_ERROR,
                message: $msg,
                game: $gameCode,
                userId: $userId,
                zoneId: $zoneId,
                provider: $this->name(),
                httpStatus: $status,
                meta: $this->debug ? ['data' => $data] : null,
            );
        }

        // Unexpected
        return ValidationResult::make(
            status: false,
            code: StatusCode::UNEXPECTED_FORMAT,
            message: "Unexpected response from Codashop (HTTP {$status}).",
            game: $gameCode,
            userId: $userId,
            zoneId: $zoneId,
            provider: $this->name(),
            httpStatus: $status,
            meta: $this->debug ? ['data' => $data, 'raw' => \mb_substr($body, 0, 2000)] : null,
        );
    }

    /**
     * Extract nickname and zone from the Codashop response.
     *
     * @param array<string, mixed> $data
     * @return array{0: ?string, 1: string|int|null}
     */
    private function extractNicknameAndZone(string $gameCode, array $data): array
    {
        $def = GameRegistry::get($gameCode);

        if (!$def) {
            return [null, null];
        }

        $nickname = null;
        $zone     = null;

        // Extract nickname
        if (isset($def['nicknameFrom']) && \is_array($def['nicknameFrom'])) {
            foreach ($def['nicknameFrom'] as $path) {
                $value = Normalizer::dotGet($data, $path);
                if (\is_string($value) && $value !== '') {
                    $nickname = $value;
                    break;
                }
            }
        } else {
            $value = Normalizer::dotGet($data, 'confirmationFields.username');
            if (\is_string($value)) {
                $nickname = $value;
            }
        }

        // Extract zone/server
        if (isset($def['serverFrom']) && \is_string($def['serverFrom'])) {
            $sv = Normalizer::dotGet($data, $def['serverFrom']);
            if (\is_string($sv) || \is_int($sv)) {
                $zone = $sv;
            }
        }

        return [$nickname, $zone];
    }
}
