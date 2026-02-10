<?php

declare(strict_types=1);

namespace Triyatna\PhpValidGame\Contracts;

use Triyatna\PhpValidGame\DTO\ValidationResult;

/**
 * Contract for game ID validation providers.
 *
 * Each provider implements its own mechanism for validating
 * a game user ID (e.g., Codashop scraping, GoPay Games API).
 */
interface ProviderInterface
{
    /**
     * Get the provider name identifier.
     */
    public function name(): string;

    /**
     * Check whether this provider supports the given game.
     */
    public function supports(string $gameCode): bool;

    /**
     * Validate a game user ID.
     *
     * @param string          $gameCode Canonical game code (e.g., "freefire")
     * @param string|int      $userId   The user/player ID to validate
     * @param string|int|null $zoneId   Optional zone/server ID
     */
    public function validate(string $gameCode, string|int $userId, string|int|null $zoneId = null): ValidationResult;
}
