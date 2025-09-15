<?php

declare(strict_types=1);

namespace Triyatna\ValidGame\DTO;

final class PricePoint
{
    public function __construct(
        public readonly int $id,
        public readonly float $price
    ) {}
}
