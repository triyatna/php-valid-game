# php-valid-game

Posts a safe form payload to a public order-init endpoint and extracts nickname/server when available.

---

## Install

```bash
composer require triyatna/php-valid-game
```

Works in $PHP and framework Laravel, CodeIgniter, Symfony, Slim, or plain PHP.

---

## Supported games

> **Canonical codes** are what you pass to `check($game, ...)`. Aliases like `ff`, `ml`, `arenaofvalor`, `codm` are also recognized.

| Game (human)          | Canonical code  | Server/Zone required | Notes                                                                                                        |
| --------------------- | --------------- | -------------------- | ------------------------------------------------------------------------------------------------------------ |
| 8 Ball Pool           | `8ballpool`     | No                   | —                                                                                                            |
| Aether Gazer          | `aethergazer`   | No                   | —                                                                                                            |
| Arena of Valor        | `aov`           | No                   | Server may be extracted from response                                                                        |
| Auto Chess            | `autochess`     | No                   | —                                                                                                            |
| Azur Lane             | `azurlane`      | **Yes**              | Name→code: `avrora`→`1`, `lexington`→`2`, `sandy`→`3`, `washington`→`4`, `amagi`→`5`, `littleenterprise`→`6` |
| Badlanders            | `badlanders`    | **Yes**              | Name→code: `global`→`11001`, `jf`→`21004`                                                                    |
| BarbarQ               | `barbarq`       | No                   | Nickname via `apiResult`                                                                                     |
| Basketrio             | `basketrio`     | **Yes**              | Name→code: `buzzerbeater`→`2`, `001`→`3`, `002`→`4`                                                          |
| Call of Duty (Mobile) | `cod`           | No                   | —                                                                                                            |
| Dragon City           | `dragoncity`    | No                   | —                                                                                                            |
| Free Fire             | `freefire`      | No                   | —                                                                                                            |
| Hago                  | `hago`          | No                   | —                                                                                                            |
| Mobile Legends        | `mobilelegends` | **Yes (Zone ID)**    | Provide numeric Zone ID                                                                                      |
| Point Blank           | `pb`            | No                   | Sends `zoneId=0`                                                                                             |
| VALORANT              | `valorant`      | No                   | Sends `zoneId=0`                                                                                             |

---

## Usage

### Laravel 12+ (helpers + generic)

Helpers (convenient methods per game):

```php
use Triyatna\PhpValidGame\Laravel\Facades\ValidGame;

// Free Fire (no server)
$res = ValidGame::freefire('123456789');

// Mobile Legends (needs Zone ID)
$res = ValidGame::mobileLegends('123456789', '7890');

// Azur Lane (server name or code accepted)
$res = ValidGame::azurLane('123456', 'avrora');

// Return JSON
return response()->json($res->toArray(), $res->status ? 200 : 422);
```

Generic (one endpoint handles all):

```php
use Triyatna\PhpValidGame\Laravel\Facades\ValidGame;

$res = ValidGame::check('aov', '99887766');          // canonical or alias or human name
$res = ValidGame::check('Azur Lane', '112233', 'amagi');

return response()->json($res->toArray(), $res->status ? 200 : 422);
```

> Prefer the Facade. You can also resolve the client directly: `app(\Triyatna\PhpValidGame\ValidGameClient::class)->check(...);`

---

### CodeIgniter 4 (helpers + generic)

```php
<?php
namespace App\Controllers;

use Triyatna\PhpValidGame\ValidGameClient;

class GameCheck extends BaseController
{
    public function freefire()
    {
        $client = new ValidGameClient();
        $res = $client->freefire('123456789');
        return $this->response->setJSON($res->toArray())
                              ->setStatusCode($res->status ? 200 : 422);
    }

    public function generic()
    {
        $client = new ValidGameClient();
        $res = $client->check('mobilelegends', '123456', '7890');
        return $this->response->setJSON($res->toArray())
                              ->setStatusCode($res->status ? 200 : 422);
    }
}
```

---

### Other frameworks / Plain PHP

```php
<?php
require __DIR__.'/vendor/autoload.php';

use Triyatna\PhpValidGame\ValidGameClient;

$client = new ValidGameClient();

// Helper
$r1 = $client->valorant('99887766');

// Generic
$r2 = $client->check('freefire', '123456789');
$r3 = $client->check('Azur Lane', '12345', 'avrora'); // name mapped → code

print_r($r1->toArray());
print_r($r2->toArray());
print_r($r3->toArray());
```

> Advanced options (optional): `new ValidGameClient(resolver:null, proxy:'http://user:pass@host:port', debug:true, logger:$psrLogger, treatUnknownAsSuccess:false);`

---

## Result format (success & errors)

Every call returns a `GameResult` you can `->toArray()`:

```json
{
  "status": true,
  "code": "OK",
  "message": "Success requesting to API.",
  "game": "freefire",
  "uid": "123456789",
  "server": null,
  "nickname": "PlayerName",
  "httpStatus": 200,
  "timestamp": "2025-09-16T12:34:56+00:00",
  "meta": null
}
```

**Error codes**

| Code                | Meaning                                                      |
| ------------------- | ------------------------------------------------------------ |
| `INVALID_INPUT`     | Missing `uid`, or required `server/zone` not provided.       |
| `UNKNOWN_GAME`      | Game not in registry (aliases & human names are normalized). |
| `HTTP_ERROR`        | Transport failure (network/proxy/DNS/etc.).                  |
| `API_ERROR`         | API responded with non-empty `errorCode`.                    |
| `NON_JSON`          | Response body empty/non-JSON.                                |
| `UNEXPECTED_FORMAT` | HTTP not 2xx or malformed success shape.                     |
| `EXCEPTION`         | Any unexpected runtime error (payload build, parsing, etc.). |

**Examples**

Invalid input (server required):

```json
{
  "status": false,
  "code": "INVALID_INPUT",
  "message": "Server/Zone is required for mobilelegends",
  "game": "mobilelegends",
  "uid": "123456",
  "server": null,
  "nickname": null,
  "httpStatus": null,
  "timestamp": "2025-09-16T12:34:56+00:00",
  "meta": null
}
```

API error:

```json
{
  "status": false,
  "code": "API_ERROR",
  "message": "Invalid user id",
  "game": "freefire",
  "uid": "bad-id",
  "server": null,
  "nickname": null,
  "httpStatus": 200,
  "timestamp": "2025-09-16T12:34:56+00:00",
  "meta": null
}
```

Transport error:

```json
{
  "status": false,
  "code": "HTTP_ERROR",
  "message": "cURL error 28: Operation timed out",
  "game": "aov",
  "uid": "123",
  "server": null,
  "nickname": null,
  "httpStatus": null,
  "timestamp": "2025-09-16T12:34:56+00:00",
  "meta": null
}
```

---

## License

**MIT** — do whatever you want, just keep the license.
