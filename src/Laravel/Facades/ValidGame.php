<?php

declare(strict_types=1);

namespace Triyatna\PhpValidGame\Laravel\Facades;

use Illuminate\Support\Facades\Facade;
use Triyatna\PhpValidGame\DTO\ValidationResult;
use Triyatna\PhpValidGame\Enums\Provider;

/**
 * Laravel facade for ValidGameClient.
 *
 * @method static ValidationResult check(string $game, string|int|null $userId, string|int|null $zoneId = null)
 * @method static ValidationResult checkWith(Provider $provider, string $game, string|int|null $userId, string|int|null $zoneId = null)
 * @method static ValidationResult freefire(string|int $userId)
 * @method static ValidationResult mobileLegends(string|int $userId, string|int $zoneId)
 * @method static ValidationResult aov(string|int $userId)
 * @method static ValidationResult valorant(string|int $userId)
 * @method static ValidationResult genshinImpact(string|int $userId, string|int $zoneId)
 * @method static ValidationResult honkaiStarRail(string|int $userId, string|int $zoneId)
 * @method static ValidationResult cod(string|int $userId)
 * @method static ValidationResult pointBlank(string|int $userId)
 * @method static ValidationResult azurLane(string|int $userId, string $serverNameOrCode)
 * @method static ValidationResult autoChess(string|int $userId)
 * @method static ValidationResult hago(string|int $userId)
 * @method static ValidationResult dragonCity(string|int $userId)
 * @method static ValidationResult badLanders(string|int $userId, string $serverNameOrCode)
 * @method static ValidationResult barbarq(string|int $userId)
 * @method static ValidationResult basketrio(string|int $userId, string $serverNameOrCode)
 * @method static ValidationResult aetherGazer(string|int $userId)
 * @method static ValidationResult eightBallPool(string|int $userId)
 * @method static ValidationResult zenlessZoneZero(string|int $userId, string|int $zoneId)
 * @method static ValidationResult pubg(string|int $userId)
 * @method static ValidationResult honorOfKings(string|int $userId)
 * @method static ValidationResult fcMobile(string|int $userId)
 * @method static ValidationResult magicChessGoGo(string|int $userId, string|int $zoneId)
 * @method static list<string> supportedGames()
 * @method static array<string,string> supportedGamesWithLabels()
 * @method static list<array{code: string, label: string, requiresZone: bool, providers: list<string>, aliases: list<string>, servers: list<string>}> listGames()
 * @method static list<string> gamesForProvider(string $provider)
 * @method static array<string,string> searchGames(string $query)
 *
 * @see \Triyatna\PhpValidGame\ValidGameClient
 */
final class ValidGame extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Triyatna\PhpValidGame\ValidGameClient::class;
    }
}
