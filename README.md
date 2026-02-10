# PHP Valid Game

[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](LICENSE)

PHP package for validating game user IDs via **Codashop scraping** and **GoPay Games API**.

---

## Install

```bash
composer require triyatna/php-valid-game
```

Requires **PHP 8.1+** and works with Laravel, CodeIgniter, Symfony, Slim, or plain PHP.

---

## Features

- **Dual Provider** — Validates via Codashop initPayment scraping and GoPay Games API
- **Auto Fallback** — If the preferred provider fails, automatically tries the next one
- **Nickname Extraction** — Returns the player's in-game nickname when available
- **22 Games Supported** — Mobile Legends, Free Fire, Genshin Impact, VALORANT, PUBG Mobile, and more
- **Alias Resolution** — Accepts human names, aliases, and canonical codes (`ff`, `ml`, `mlbb`, etc.)
- **Magic Methods** — Call any game as a method: `$client->freefire('123')`, `$client->pubg('456')`
- **Smart Registry** — Search games, filter by provider, register custom games at runtime
- **Laravel Integration** — Auto-discovery service provider, facade, and publishable config
- **PSR-3 Logging** — Optional debug logging via any PSR-3 compatible logger
- **Proxy Support** — Route requests through HTTP proxy

---

## Supported Games

| Game                | Code              | Zone Required | Codashop | GoPay | Aliases                         |
| ------------------- | ----------------- | :-----------: | :------: | :---: | ------------------------------- |
| 8 Ball Pool         | `8ballpool`       |      No       |    ✓     |   —   | `eightballpool`                 |
| Aether Gazer        | `aethergazer`     |      No       |    ✓     |   —   |                                 |
| Arena of Valor      | `aov`             |      No       |    ✓     |   ✓   | `arenaofvalor`                  |
| Auto Chess          | `autochess`       |      No       |    ✓     |   —   |                                 |
| Azur Lane           | `azurlane`        |    **Yes**    |    ✓     |   —   |                                 |
| Badlanders          | `badlanders`      |    **Yes**    |    ✓     |   —   |                                 |
| BarbarQ             | `barbarq`         |      No       |    ✓     |   —   |                                 |
| Basketrio           | `basketrio`       |    **Yes**    |    ✓     |   —   |                                 |
| Call of Duty Mobile | `cod`             |      No       |    ✓     |   ✓   | `codm`, `callofduty`            |
| Dragon City         | `dragoncity`      |      No       |    ✓     |   —   |                                 |
| FC Mobile           | `fcmobile`        |      No       |    —     |   ✓   | `fcm`, `efootball`              |
| Free Fire           | `freefire`        |      No       |    ✓     |   ✓   | `ff`, `garena`                  |
| Genshin Impact      | `genshinimpact`   |    **Yes**    |    ✓     |   ✓   | `genshin`, `gi`                 |
| Hago                | `hago`            |      No       |    ✓     |   —   |                                 |
| Honkai Star Rail    | `honkaistarrail`  |    **Yes**    |    ✓     |   —   | `hsr`, `starrail`               |
| Honor of Kings      | `hok`             |      No       |    —     |   ✓   | `honorofkings`                  |
| Magic Chess: Go Go  | `magicchessgogo`  |    **Yes**    |    —     |   ✓   | `magicchess`, `mcgg`            |
| Mobile Legends      | `mobilelegends`   |    **Yes**    |    ✓     |   ✓   | `ml`, `mlbb`, `mobilelegend`    |
| Point Blank         | `pb`              |      No       |    ✓     |   —   | `pointblank`                    |
| PUBG Mobile         | `pubg`            |      No       |    —     |   ✓   | `pubgmobile`, `pubgm`, `pubgid` |
| VALORANT            | `valorant`        |      No       |    ✓     |   ✓   | `val`                           |
| Zenless Zone Zero   | `zenlesszonezero` |    **Yes**    |    ✓     |   —   | `zzz`                           |

---

## Usage

### Plain PHP

```php
<?php
require __DIR__ . '/vendor/autoload.php';

use Triyatna\PhpValidGame\ValidGameClient;
use Triyatna\PhpValidGame\Enums\Provider;

// Default: Codashop first, GoPay Games as fallback
$client = new ValidGameClient();

// Free Fire (no zone required)
$result = $client->freefire('123456789');

// Mobile Legends (zone required)
$result = $client->mobileLegends('123456789', '7890');

// New games (GoPay-only, also work via magic method)
$result = $client->pubg('123456789');
$result = $client->honorOfKings('123456789');
$result = $client->fcMobile('123456789');
$result = $client->magicChessGoGo('123456789', '7890');

// Generic check (accepts aliases)
$result = $client->check('ff', '123456789');
$result = $client->check('Mobile Legends', '123456', '7890');
$result = $client->check('Azur Lane', '12345', 'avrora');

// Force a specific provider
$result = $client->checkWith(Provider::GOPAY_GAMES, 'freefire', '123456789');

// Smart registry queries
$gopayGames = $client->gamesForProvider('gopaygames'); // all GoPay-supported
$matches    = $client->searchGames('mobile');           // fuzzy search

// List all available games (code, label, provider support, aliases, servers)
$allGames = $client->listGames();
foreach ($allGames as $game) {
    echo "{$game['code']} => {$game['label']}";
    echo " | Providers: " . implode(', ', $game['providers']);
    echo " | Zone: " . ($game['requiresZone'] ? 'Yes' : 'No');
    if (!empty($game['aliases'])) {
        echo " | Aliases: " . implode(', ', $game['aliases']);
    }
    if (!empty($game['servers'])) {
        echo " | Servers: " . implode(', ', $game['servers']);
    }
    echo PHP_EOL;
}

// Output
print_r($result->toArray());
echo $result->toJson();
echo $result->isValid() ? 'Valid!' : 'Invalid!';
echo $result->nickname; // Player's nickname
```

### Advanced Options

```php
use Triyatna\PhpValidGame\ValidGameClient;
use Triyatna\PhpValidGame\Enums\Provider;

$client = new ValidGameClient(
    preferredProvider: Provider::GOPAY_GAMES,  // Try GoPay first
    fallback: true,                            // Fall back to Codashop
    proxy: 'http://user:pass@host:port',       // HTTP proxy
    debug: true,                               // Include raw data in meta
    logger: $psrLogger,                        // PSR-3 logger
    timeout: 20,                               // HTTP timeout (seconds)
);
```

### Laravel 11+ (Auto-Discovery)

The service provider and facade are auto-discovered.

**Publish config (optional):**

```bash
php artisan vendor:publish --tag=valid-game-config
```

**Using the Facade:**

```php
use Triyatna\PhpValidGame\Laravel\Facades\ValidGame;

// Convenience helpers (magic methods work for any registered game)
$result = ValidGame::freefire('123456789');
$result = ValidGame::mobileLegends('123456', '7890');
$result = ValidGame::genshinImpact('800123456', 'os_asia');
$result = ValidGame::pubg('123456789');
$result = ValidGame::honorOfKings('123456789');

// Generic
$result = ValidGame::check('valorant', '99887766');
$result = ValidGame::check('Azur Lane', '112233', 'amagi');

// Smart queries
$gopayGames = ValidGame::gamesForProvider('gopaygames');
$matches    = ValidGame::searchGames('legend');

// List all games with full details
$allGames = ValidGame::listGames();
// Returns: [['code' => 'freefire', 'label' => 'Free Fire', 'providers' => ['codashop', 'gopaygames'], ...], ...]

return response()->json($result->toArray(), $result->isValid() ? 200 : 422);
```

**Environment variables:**

```env
VALID_GAME_PROVIDER=codashop    # codashop or gopaygames
VALID_GAME_FALLBACK=true
VALID_GAME_PROXY=
VALID_GAME_DEBUG=false
VALID_GAME_TIMEOUT=15
```

### CodeIgniter 4

```php
<?php
namespace App\Controllers;

use Triyatna\PhpValidGame\ValidGameClient;

class GameCheck extends BaseController
{
    public function freefire()
    {
        $client = new ValidGameClient();
        $result = $client->freefire($this->request->getGet('uid'));

        return $this->response
            ->setJSON($result->toArray())
            ->setStatusCode($result->isValid() ? 200 : 422);
    }

    public function check()
    {
        $client = new ValidGameClient();
        $result = $client->check(
            $this->request->getGet('game'),
            $this->request->getGet('uid'),
            $this->request->getGet('zone'),
        );

        return $this->response
            ->setJSON($result->toArray())
            ->setStatusCode($result->isValid() ? 200 : 422);
    }
}
```

---

## Extending the Registry

Register custom games or aliases at runtime:

```php
use Triyatna\PhpValidGame\Registry\GameRegistry;

// Register a new game
GameRegistry::register('mygame', [
    'label'        => 'My Game',
    'requiresZone' => false,
    'gopayCode'    => 'MY_GAME',
    'codashop'     => [
        'typeName' => 'MY_GAME',
        'payload'  => fn($uid, $zone) => [
            'voucherPricePoint.id'    => '999999',
            'voucherPricePoint.price' => '10000.0000',
            'user.userId'             => $uid,
            'voucherTypeName'         => 'MY_GAME',
            'shopLang'                => 'id_ID',
        ],
    ],
    'nicknameFrom' => ['confirmationFields.username'],
]);

// Register an alias
GameRegistry::alias('mg', 'mygame');
```

---

## Listing All Available Games

Use `listGames()` to get a structured list of every registered game:

```php
$client = new ValidGameClient();
$games  = $client->listGames();

print_r($games);
```

Each entry returns:

```php
[
    'code'         => 'freefire',          // Canonical game code
    'label'        => 'Free Fire',         // Human-readable name
    'requiresZone' => false,               // Whether zoneId is mandatory
    'providers'    => ['codashop', 'gopaygames'], // Supported providers
    'aliases'      => ['ff', 'garena'],    // Accepted aliases
    'servers'      => [],                  // Server map keys (if any)
]
```

For games with server maps (e.g., Azur Lane):

```php
[
    'code'         => 'azurlane',
    'label'        => 'Azur Lane',
    'requiresZone' => true,
    'providers'    => ['codashop'],
    'aliases'      => [],
    'servers'      => ['avrora', 'lexington', 'sandy', 'washington', 'amagi', 'littleenterprise'],
]
```

### Available Methods

| Method                             | Returns                 | Description                                 |
| ---------------------------------- | ----------------------- | ------------------------------------------- |
| `listGames()`                      | `array` of game details | Full structured list of all available games |
| `supportedGames()`                 | `string[]` of codes     | All canonical game codes                    |
| `supportedGamesWithLabels()`       | `array<code, label>`    | Code → label mapping                        |
| `gamesForProvider('codashop')`     | `string[]` of codes     | Games supporting a specific provider        |
| `searchGames('mobile')`            | `array<code, label>`    | Fuzzy search by name/alias                  |
| `check($game, $userId, $zoneId)`   | `ValidationResult`      | Validate with auto-provider                 |
| `checkWith($provider, $game, ...)` | `ValidationResult`      | Validate with a specific provider           |

---

## Result Format

Every call returns a `ValidationResult`:

```json
{
  "status": true,
  "code": "OK",
  "message": "User ID is valid.",
  "game": "freefire",
  "userId": "123456789",
  "zoneId": null,
  "nickname": "PlayerName",
  "provider": "codashop",
  "httpStatus": 200,
  "timestamp": "2025-09-16T12:34:56+00:00",
  "meta": null
}
```

### Error Codes

| Code                | Meaning                                                 |
| ------------------- | ------------------------------------------------------- |
| `OK`                | Validation successful, user ID is valid.                |
| `INVALID_INPUT`     | Missing `userId`, or required `zoneId` not provided.    |
| `UNKNOWN_GAME`      | Game not found in registry.                             |
| `HTTP_ERROR`        | Transport failure (network/proxy/DNS/timeout).          |
| `API_ERROR`         | Provider API returned an error (invalid user ID, etc.). |
| `NON_JSON`          | Response body empty or non-JSON.                        |
| `UNEXPECTED_FORMAT` | HTTP non-2xx or malformed success shape.                |
| `PROVIDER_ERROR`    | Provider failed or no provider supports the game.       |
| `EXCEPTION`         | Unexpected runtime error.                               |

---

## Testing

```bash
composer install
vendor/bin/phpunit
```

---

## License

**MIT** — see [LICENSE](LICENSE) for details.
