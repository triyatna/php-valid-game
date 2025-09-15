<?php

declare(strict_types=1);

namespace Triyatna\PhpValidGame;

use Psr\Log\LoggerInterface;
use Triyatna\PhpValidGame\Contracts\PayloadResolverInterface;
use Triyatna\PhpValidGame\DTO\GameResult;
use Triyatna\PhpValidGame\Exceptions\HttpRequestException;
use Triyatna\PhpValidGame\Exceptions\InvalidInputException;
use Triyatna\PhpValidGame\Registry\GameRegistry;
use Triyatna\PhpValidGame\Resolvers\StaticPayloadResolver;
use Triyatna\PhpValidGame\Support\Str;
use Triyatna\PhpValidGame\Transport\HttpClient;

final class ValidGameClient
{
    private PayloadResolverInterface $resolver;
    private HttpClient $http;

    public function __construct(
        ?PayloadResolverInterface $resolver = null,
        ?string $proxy = null,
        private readonly bool $debug = false,
        private readonly ?LoggerInterface $logger = null,
        private readonly bool $treatUnknownAsSuccess = false // follow your original service behavior if desired
    ) {
        GameRegistry::init();
        $this->resolver = $resolver ?? new StaticPayloadResolver();
        $this->http     = new HttpClient(proxy: $proxy, debug: $debug, logger: $logger);
    }

    /**
     * Core check: accepts any human game name or code (aliases supported).
     */
    public function check(string $game, string|int|null $uid, string|int|null $server = null): GameResult
    {
        $canonical = GameRegistry::resolveCanonical($game) ?? Str::canonicalGame($game);

        if ($uid === null || $uid === '') {
            return GameResult::make(false, 'INVALID_INPUT', 'User ID is required.', $canonical, $uid, $server);
        }

        // Unknown game handling
        if (!GameRegistry::def($canonical)) {
            if ($this->treatUnknownAsSuccess) {
                return GameResult::make(
                    true,
                    'UNKNOWN_GAME',
                    "Game '{$game}' is not recognized by this server, treated as success.",
                    $canonical,
                    $uid,
                    $server
                );
            }
            return GameResult::make(false, 'UNKNOWN_GAME', "Unknown game '{$game}'.", $canonical, $uid, $server);
        }

        // Resolve payload
        try {
            $payload = $this->resolver->resolve($canonical, $uid, $server);
        } catch (InvalidInputException $e) {
            return GameResult::make(false, 'INVALID_INPUT', $e->getMessage(), $canonical, $uid, $server);
        } catch (\Throwable $e) {
            return GameResult::make(false, 'EXCEPTION', 'Failed building payload: ' . $e->getMessage(), $canonical, $uid, $server);
        }

        // Send
        try {
            $resp = $this->http->postInit($payload);
        } catch (HttpRequestException $e) {
            return GameResult::make(false, 'HTTP_ERROR', $e->getMessage(), $canonical, $uid, $server, httpStatus: null);
        } catch (\Throwable $e) {
            return GameResult::make(false, 'EXCEPTION', $e->getMessage(), $canonical, $uid, $server);
        }

        $status = $resp['status'] ?? 0;
        $body   = $resp['body'] ?? '';

        // Parse JSON (API returns JSON when Accept: application/json)
        $data = null;
        if (\is_string($body) && $body !== '') {
            $data = \json_decode($body, true);
        }

        if ($data === null) {
            return GameResult::make(
                false,
                'NON_JSON',
                'Received non-JSON or empty response.',
                $canonical,
                $uid,
                $server,
                httpStatus: $status,
                meta: $this->debug ? ['raw' => \mb_substr($body, 0, 2000)] : null
            );
        }

        // Success path: errorCode present and empty string
        if (($status >= 200 && $status < 300) && isset($data['errorCode']) && $data['errorCode'] === '') {
            [$nickname, $serverExtracted] = $this->extractNicknameAndServer($canonical, $data);

            return GameResult::make(
                true,
                'OK',
                'Success requesting to API.',
                $canonical,
                $uid,
                $serverExtracted ?? $server,
                $nickname,
                httpStatus: $status,
                meta: $this->debug ? ['data' => $data] : null
            );
        }

        // API error but HTTP success
        if (isset($data['errorCode']) && $data['errorCode'] !== '') {
            $msg = $data['errorMsg'] ?? 'API returned an error.';
            return GameResult::make(false, 'API_ERROR', $msg, $canonical, $uid, $server, httpStatus: $status, meta: $this->debug ? ['data' => $data] : null);
        }

        // HTTP non-2xx (or malformed success)
        return GameResult::make(
            false,
            'UNEXPECTED_FORMAT',
            'Unexpected response or HTTP error: ' . $status,
            $canonical,
            $uid,
            $server,
            httpStatus: $status,
            meta: $this->debug ? ['data' => $data, 'raw' => \mb_substr($body, 0, 2000)] : null
        );
    }

    /**
     * Convenience helpers matching your service:
     * Usage: $client->freefire('123456'); $client->mobileLegends('123','456');
     */
    public function freefire(string|int $uid): GameResult
    {
        return $this->check('freefire', $uid);
    }
    public function aetherGazer(string|int $uid): GameResult
    {
        return $this->check('aethergazer', $uid);
    }
    public function aov(string|int $uid): GameResult
    {
        return $this->check('aov', $uid);
    }
    public function autoChess(string|int $uid): GameResult
    {
        return $this->check('autochess', $uid);
    }
    public function azurLane(string|int $uid, string $serverNameOrCode): GameResult
    {
        return $this->check('azurlane', $uid, $serverNameOrCode);
    }
    public function badLanders(string|int $uid, string $serverNameOrCode): GameResult
    {
        return $this->check('badlanders', $uid, $serverNameOrCode);
    }
    public function barbarq(string|int $uid): GameResult
    {
        return $this->check('barbarq', $uid);
    }
    public function basketrio(string|int $uid, string $serverNameOrCode): GameResult
    {
        return $this->check('basketrio', $uid, $serverNameOrCode);
    }
    public function cod(string|int $uid): GameResult
    {
        return $this->check('cod', $uid);
    }
    public function dragonCity(string|int $uid): GameResult
    {
        return $this->check('dragoncity', $uid);
    }
    public function hago(string|int $uid): GameResult
    {
        return $this->check('hago', $uid);
    }
    public function mobileLegends(string|int $uid, string|int $zoneId): GameResult
    {
        return $this->check('mobilelegends', $uid, $zoneId);
    }
    public function pointBlank(string|int $uid): GameResult
    {
        return $this->check('pb', $uid);
    }
    public function valorant(string|int $uid): GameResult
    {
        return $this->check('valorant', $uid);
    }

    /**
     * Extract nickname (and sometimes server) based on registry hints.
     * @param array<string,mixed> $data
     * @return array{0:?string,1:string|int|null}
     */
    private function extractNicknameAndServer(string $canonical, array $data): array
    {
        $def = GameRegistry::def($canonical);
        if (!$def) {
            return [null, null];
        }

        $nickname = null;
        $server   = null;

        $fetch = static function (array $src, string $path): mixed {
            // dot.notation with numeric indices
            $parts = \explode('.', $path);
            $cur = $src;
            foreach ($parts as $p) {
                if (\is_array($cur)) {
                    if (\ctype_digit($p)) {
                        $idx = (int)$p;
                        $cur = $cur[$idx] ?? null;
                    } else {
                        $cur = $cur[$p] ?? null;
                    }
                } else {
                    return null;
                }
            }
            return $cur;
        };

        if (isset($def['nicknameFrom']) && \is_array($def['nicknameFrom'])) {
            foreach ($def['nicknameFrom'] as $path) {
                $nickname = $fetch($data, $path);
                if (\is_string($nickname) && $nickname !== '') {
                    break;
                }
                $nickname = null;
            }
        } else {
            $nickname = \is_string($fetch($data, 'confirmationFields.username')) ? (string)$fetch($data, 'confirmationFields.username') : null;
        }

        if (isset($def['serverFrom']) && \is_string($def['serverFrom'])) {
            $sv = $fetch($data, $def['serverFrom']);
            $server = (\is_string($sv) || \is_int($sv)) ? $sv : null;
        }

        return [$nickname, $server];
    }
}
