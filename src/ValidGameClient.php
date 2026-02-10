<?php

declare(strict_types=1);

namespace Triyatna\PhpValidGame;

use Psr\Log\LoggerInterface;
use Triyatna\PhpValidGame\Contracts\ProviderInterface;
use Triyatna\PhpValidGame\DTO\ValidationResult;
use Triyatna\PhpValidGame\Enums\Provider;
use Triyatna\PhpValidGame\Enums\StatusCode;
use Triyatna\PhpValidGame\Providers\CodashopProvider;
use Triyatna\PhpValidGame\Providers\GopayGamesProvider;
use Triyatna\PhpValidGame\Registry\GameRegistry;
use Triyatna\PhpValidGame\Support\Normalizer;
use Triyatna\PhpValidGame\Transport\HttpClient;

/**
 * Main entry point for game ID validation.
 *
 * Supports validation via Codashop scraping and/or GoPay Games API.
 * Can be configured with a preferred provider and optional fallback.
 *
 * Usage:
 *   $client = new ValidGameClient();
 *   $result = $client->check('freefire', '123456789');
 *   $result = $client->check('mobilelegends', '123456', '7890');
 */
final class ValidGameClient
{
    /** @var ProviderInterface[] */
    private array $providers = [];

    public function __construct(
        private readonly ?Provider $preferredProvider = null,
        private readonly bool $fallback = true,
        ?string $proxy = null,
        private readonly bool $debug = false,
        private readonly ?LoggerInterface $logger = null,
        private readonly int $timeout = 15,
    ) {
        GameRegistry::init();

        $http = new HttpClient(
            proxy: $proxy,
            timeout: $this->timeout,
            debug: $this->debug,
            logger: $this->logger,
        );

        // Register built-in providers (order matters for fallback)
        if ($this->preferredProvider === Provider::GOPAY_GAMES) {
            $this->providers[] = new GopayGamesProvider($http, $this->debug, $this->logger);
            $this->providers[] = new CodashopProvider($http, $this->debug, $this->logger);
        } else {
            $this->providers[] = new CodashopProvider($http, $this->debug, $this->logger);
            $this->providers[] = new GopayGamesProvider($http, $this->debug, $this->logger);
        }
    }

    /**
     * Add a custom provider to the provider chain.
     */
    public function addProvider(ProviderInterface $provider): self
    {
        $this->providers[] = $provider;

        return $this;
    }

    /**
     * Validate a game user ID using available providers.
     *
     * Resolves game name/alias → canonical code, validates input,
     * then delegates to the preferred provider (with optional fallback).
     *
     * @param string          $game   Game name, alias, or canonical code
     * @param string|int|null $userId Player user ID
     * @param string|int|null $zoneId Optional zone/server ID
     */
    public function check(string $game, string|int|null $userId, string|int|null $zoneId = null): ValidationResult
    {
        // Resolve and validate input
        [$canonical, $error] = $this->resolveAndValidate($game, $userId, $zoneId);
        if ($error !== null) {
            return $error;
        }

        // Try providers in order
        $lastResult = null;

        foreach ($this->providers as $provider) {
            if (!$provider->supports($canonical)) {
                continue;
            }

            try {
                $result = $provider->validate($canonical, $userId, $zoneId);

                // If successful or fallback is disabled, return immediately
                if ($result->isValid() || !$this->fallback) {
                    return $result;
                }

                $lastResult = $result;
            } catch (\Throwable $e) {
                $this->logger?->warning('ValidGame provider error', [
                    'provider' => $provider->name(),
                    'game'     => $canonical,
                    'error'    => $e->getMessage(),
                ]);

                $lastResult = ValidationResult::make(
                    status: false,
                    code: StatusCode::PROVIDER_ERROR,
                    message: "Provider {$provider->name()} failed: {$e->getMessage()}",
                    game: $canonical,
                    userId: $userId,
                    zoneId: $zoneId,
                    provider: $provider->name(),
                );

                if (!$this->fallback) {
                    return $lastResult;
                }
            }
        }

        // Return last result if any provider was tried
        if ($lastResult !== null) {
            return $lastResult;
        }

        // No provider supports this game
        return ValidationResult::make(
            status: false,
            code: StatusCode::PROVIDER_ERROR,
            message: "No provider available for game '{$canonical}'.",
            game: $canonical,
            userId: $userId,
            zoneId: $zoneId,
        );
    }

    /**
     * Validate using a specific provider only.
     */
    public function checkWith(Provider $provider, string $game, string|int|null $userId, string|int|null $zoneId = null): ValidationResult
    {
        [$canonical, $error] = $this->resolveAndValidate($game, $userId, $zoneId);
        if ($error !== null) {
            return $error;
        }

        foreach ($this->providers as $p) {
            if ($p->name() === $provider->value && $p->supports($canonical)) {
                return $p->validate($canonical, $userId, $zoneId);
            }
        }

        return ValidationResult::make(
            status: false,
            code: StatusCode::PROVIDER_ERROR,
            message: "Provider '{$provider->value}' does not support game '{$canonical}'.",
            game: $canonical,
            userId: $userId,
            zoneId: $zoneId,
            provider: $provider->value,
        );
    }

    /**
     * Get all supported game codes.
     *
     * @return list<string>
     */
    public function supportedGames(): array
    {
        return GameRegistry::allCodes();
    }

    /**
     * Get all supported games with labels.
     *
     * @return array<string, string>
     */
    public function supportedGamesWithLabels(): array
    {
        return GameRegistry::allGames();
    }

    /**
     * Get a detailed list of all available games.
     *
     * Each entry contains: code, label, requiresZone, providers, aliases, servers.
     *
     * @return list<array{code: string, label: string, requiresZone: bool, providers: list<string>, aliases: list<string>, servers: list<string>}>
     */
    public function listGames(): array
    {
        return GameRegistry::listGames();
    }

    /**
     * Get all games that support a specific provider.
     *
     * @return list<string>
     */
    public function gamesForProvider(string $provider): array
    {
        return GameRegistry::gamesForProvider($provider);
    }

    /**
     * Search for games matching a partial query.
     *
     * @return array<string, string> canonical => label
     */
    public function searchGames(string $query): array
    {
        return GameRegistry::search($query);
    }

    /**
     * Magic method: auto-generates convenience helpers for ANY registered game.
     *
     * Converts camelCase method name to canonical game code, e.g.:
     *   $client->freefire('123')         → check('freefire', '123')
     *   $client->mobileLegends('1','2')  → check('mobilelegends', '1', '2')
     *   $client->honorOfKings('123')     → check('hok', '123')
     *   $client->magicChessGoGo('1','2') → check('magicchessgogo', '1', '2')
     *
     * @param string       $method
     * @param array<mixed> $args
     */
    public function __call(string $method, array $args): ValidationResult
    {
        // Normalize camelCase to lowercase: "mobileLegends" → "mobilelegends"
        $canonical = GameRegistry::resolveCanonical($method);

        if ($canonical === null) {
            // Also try as-is lowercase
            $canonical = GameRegistry::resolveCanonical(Normalizer::canonicalGame($method));
        }

        if ($canonical === null) {
            return ValidationResult::make(
                status: false,
                code: StatusCode::UNKNOWN_GAME,
                message: "Unknown game method '{$method}'. Use check() with a valid game code.",
                game: $method,
            );
        }

        $userId = $args[0] ?? null;
        $zoneId = $args[1] ?? null;

        return $this->check($canonical, $userId, $zoneId);
    }

    /**
     * Resolve game input and validate common requirements.
     *
     * @return array{0: string, 1: ValidationResult|null}
     */
    private function resolveAndValidate(string $game, string|int|null $userId, string|int|null $zoneId): array
    {
        $canonical = GameRegistry::resolveCanonical($game) ?? Normalizer::canonicalGame($game);

        if ($userId === null || $userId === '') {
            return [$canonical, ValidationResult::make(
                status: false,
                code: StatusCode::INVALID_INPUT,
                message: 'User ID is required.',
                game: $canonical,
                userId: $userId,
                zoneId: $zoneId,
            )];
        }

        $def = GameRegistry::get($canonical);
        if (!$def) {
            return [$canonical, ValidationResult::make(
                status: false,
                code: StatusCode::UNKNOWN_GAME,
                message: "Unknown game '{$game}'.",
                game: $canonical,
                userId: $userId,
                zoneId: $zoneId,
            )];
        }

        if (($def['requiresZone'] ?? false) && ($zoneId === null || $zoneId === '')) {
            return [$canonical, ValidationResult::make(
                status: false,
                code: StatusCode::INVALID_INPUT,
                message: "Zone/Server ID is required for {$def['label']}.",
                game: $canonical,
                userId: $userId,
                zoneId: $zoneId,
            )];
        }

        return [$canonical, null];
    }
}
