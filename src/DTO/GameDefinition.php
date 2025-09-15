<?php

declare(strict_types=1);

namespace Triyatna\ValidGame\DTO;

final class GameDefinition
{
    /**
     * @param array<string, string> $tokens
     * @param array<string, int|string|float|null> $fixed
     * @param PricePoint[] $pricePoints
     */
    public function __construct(
        public readonly string $gameCode,
        public readonly ?string $absoluteUrl,
        public readonly array $tokens = [],
        public readonly array $fixed = [],
        public readonly array $pricePoints = []
    ) {}
}
