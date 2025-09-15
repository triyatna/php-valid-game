<?php

declare(strict_types=1);

namespace Triyatna\PhpValidGame\Laravel\Facades;

use Illuminate\Support\Facades\Facade;
use Triyatna\PhpValidGame\DTO\GameResult;

/**
 * @method static GameResult check(string $game, string|int|null $uid, string|int|null $server = null)
 * @method static GameResult freefire(string|int $uid)
 * @method static GameResult mobileLegends(string|int $uid, string|int $zoneId)
 * @method static GameResult aov(string|int $uid)
 * // ...others (see ValidGameClient)
 */
final class ValidGame extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Triyatna\PhpValidGame\ValidGameClient::class;
    }
}
