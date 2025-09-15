<?php

declare(strict_types=1);

namespace Triyatna\ValidGame\Laravel\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Triyatna\ValidGame\DTO\CheckResult check(string $gameCode, string $userId, ?string $serverId = null, array $options = [])
 */
final class ValidGame extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Triyatna\ValidGame\Validator::class;
    }
}
