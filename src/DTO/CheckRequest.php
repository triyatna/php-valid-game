<?php

declare(strict_types=1);

namespace Triyatna\ValidGame\DTO;

final class CheckRequest
{
    /**
     * @param array<string, int|string|float|null> $extras
     */
    public function __construct(
        public readonly string $gameCode,
        public readonly string $userId,
        public readonly ?string $serverId = null,
        public readonly ?int $pricePointId = null,
        public readonly ?float $pricePointPrice = null,
        public readonly ?string $absoluteUrl = null,
        public readonly array $extras = []
    ) {}
}
