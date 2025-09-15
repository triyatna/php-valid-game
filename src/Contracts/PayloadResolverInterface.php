<?php

declare(strict_types=1);

namespace Triyatna\PhpValidGame\Contracts;

/**
 * Resolves a POST payload for a specific game based on uid/server etc.
 * Different implementations can provide static templates or scrape/build dynamically.
 */
interface PayloadResolverInterface
{
    /**
     * @param string $canonicalGame  canonical game code (e.g., "freefire")
     * @param string|int $uid        user id
     * @param string|int|null $server optional server/zone name or code
     * @return array<string,mixed>    a flat form payload (application/x-www-form-urlencoded)
     */
    public function resolve(string $canonicalGame, string|int $uid, string|int|null $server = null): array;
}
