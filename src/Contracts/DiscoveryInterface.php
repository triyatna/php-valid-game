<?php

declare(strict_types=1);

namespace Triyatna\ValidGame\Contracts;

use Triyatna\ValidGame\DTO\GameDefinition;

interface DiscoveryInterface
{
    public function discover(string $gameCode, ?string $absoluteUrl = null): ?GameDefinition;
}
