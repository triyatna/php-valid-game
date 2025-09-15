<?php

declare(strict_types=1);

namespace Triyatna\PhpValidGame\Contracts;

interface PageResolverInterface
{
    /**
     * Build payload by scraping the game page path.
     *
     * @param string $pathSlug e.g. "dazz-live"
     * @param string|int $uid
     * @param string|int|null $server
     * @param int|null $pricePointId optional override (if multiple price points exist)
     * @param float|int|null $price  optional override price (e.g., 10000 or 10000.0)
     * @return array<string,mixed> form payload for initPayment
     */
    public function resolveFromPath(
        string $pathSlug,
        string|int $uid,
        string|int|null $server = null,
        ?int $pricePointId = null,
        float|int|null $price = null
    ): array;
}
