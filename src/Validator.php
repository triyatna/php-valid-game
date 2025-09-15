<?php

declare(strict_types=1);

namespace Triyatna\ValidGame;

use Psr\Log\LoggerInterface;
use Triyatna\ValidGame\Contracts\DiscoveryInterface;
use Triyatna\ValidGame\Contracts\HttpClientInterface;
use Triyatna\ValidGame\DTO\CheckRequest;
use Triyatna\ValidGame\DTO\CheckResult;
use Triyatna\ValidGame\DTO\GameDefinition;
use Triyatna\ValidGame\DTO\PricePoint;
use Triyatna\ValidGame\Exceptions\InvalidArgumentException;
use Triyatna\ValidGame\Exceptions\ProviderException;
use Triyatna\ValidGame\Http\GuzzleHttpClient;
use Triyatna\ValidGame\Provider\InitPaymentClient;
use Triyatna\ValidGame\Discovery\AutoDiscoverer;
use Triyatna\ValidGame\Registry\GameRegistry;
use Triyatna\ValidGame\Support\Arr;
use Triyatna\ValidGame\Support\Backoff;
use Triyatna\ValidGame\Support\OrderProfileEncoder;
use Triyatna\ValidGame\Support\RateLimiter;

final class Validator
{
    private readonly InitPaymentClient $provider;
    private readonly DiscoveryInterface $discoverer;
    private readonly GameRegistry $registry;
    private readonly RateLimiter $limiter;

    public function __construct(
        ?HttpClientInterface $http = null,
        ?DiscoveryInterface $discoverer = null,
        ?LoggerInterface $logger = null,
        ?string $proxy = null,
        bool $debug = false
    ) {
        $http ??= new GuzzleHttpClient();
        $this->provider    = new InitPaymentClient($http, $logger, $proxy, $debug);
        $this->discoverer  = $discoverer ?? new AutoDiscoverer($http, $proxy, 15.0, $debug);
        $this->registry    = new GameRegistry();
        $this->limiter     = new RateLimiter();
    }

    /**
     * @param array{
     *   pricePointId?: int,
     *   pricePointPrice?: float,
     *   absoluteUrl?: string,
     *   productPath?: string,
     *   autoPrice?: bool,
     *   priceStrategy?: 'lowest'|'highest'|'closest'|'preferIds'|'preferPrice',
     *   closestTarget?: float,
     *   preferIds?: int[],
     *   preferPrice?: float,
     *   extras?: array<string, int|string|float|null>
     * } $options
     */
    public static function check(string $gameCode, string $userId, ?string $serverId = null, array $options = []): CheckResult
    {
        // If productPath is supplied, convert to absoluteUrl
        if (!isset($options['absoluteUrl']) && isset($options['productPath']) && is_string($options['productPath'])) {
            $path = ltrim($options['productPath'], '/');
            $options['absoluteUrl'] = "https://www.codashop.com/{$path}";
        }

        return (new self())->checkInternal(new CheckRequest(
            gameCode: $gameCode,
            userId: $userId,
            serverId: $serverId,
            pricePointId: $options['pricePointId'] ?? null,
            pricePointPrice: $options['pricePointPrice'] ?? null,
            absoluteUrl: $options['absoluteUrl'] ?? null,
            extras: is_array($options['extras'] ?? null) ? $options['extras'] : []
        ), $options);
    }

    public function registerGame(GameDefinition $def): void
    {
        $this->registry->put($def);
    }

    public function checkInternal(CheckRequest $req, array $options = []): CheckResult
    {
        if ($req->userId === '') {
            throw new InvalidArgumentException('userId is required.');
        }

        if (!$this->limiter->take()) {
            return new CheckResult(false, 'Rate limit exceeded. Please retry shortly.');
        }

        // 1) Get or discover definition
        $def = $this->registry->get($req->gameCode);
        if ($def === null) {
            $abs = $req->absoluteUrl;
            $def = $this->discoverer->discover($req->gameCode, $abs);
            if ($def === null) {
                return new CheckResult(false, 'Game definition not found and discovery failed.');
            }
            $this->registry->put($def);
        }

        // 2) Decide price point (auto if not supplied)
        [$ppId, $ppPrice] = $this->decidePricePoint($req, $def, $options);

        // 3) Build payload
        $payload = $this->buildPayload($req, $def, $ppId, $ppPrice);

        // 4) Try POST with retries
        $delays = Backoff::sequence(2);
        $lastErr = null;
        $attempts = 1 + count($delays);

        for ($i = 0; $i < $attempts; $i++) {
            $resp = $this->provider->post($payload);
            $status = $resp['status'];
            $json = $resp['json'];

            if ($json !== null) {
                $errorCode = (string)($json['errorCode'] ?? '');
                $errorMsg  = (string)($json['errorMsg'] ?? '');

                if ($errorCode === '') {
                    $fields = (array)($json['confirmationFields'] ?? []);
                    $nickname = Arr::get($fields, 'username')
                        ?? Arr::get($fields, 'roles.0.role')
                        ?? Arr::get($fields, 'apiResult')
                        ?? null;
                    $server = Arr::get($fields, 'roles.0.server') ?? ($req->serverId ?? null);

                    return new CheckResult(true, 'OK', $nickname, $server, $status, $json);
                }
                $lastErr = new ProviderException($errorMsg !== '' ? $errorMsg : 'Provider returned an error.');
            } else {
                $lastErr = new ProviderException("Non-JSON response (HTTP {$status}).");
            }

            if ($i < count($delays)) {
                Backoff::sleepMs($delays[$i]);
            }
        }

        return new CheckResult(false, $lastErr?->getMessage() ?? 'Unknown error.');
    }

    /**
     * @return array{0:?int,1:?float}
     */
    private function decidePricePoint(CheckRequest $req, GameDefinition $def, array $options): array
    {
        $providedId = $req->pricePointId;
        $providedPrice = $req->pricePointPrice;

        // If caller already provided both, use them
        if ($providedId !== null && $providedPrice !== null) {
            return [$providedId, $providedPrice];
        }

        $auto = (bool)($options['autoPrice'] ?? true);
        if (!$auto && ($providedId !== null || $providedPrice !== null)) {
            return [$providedId, $providedPrice];
        }

        $pps = $def->pricePoints;
        if (empty($pps)) {
            return [$providedId, $providedPrice];
        }

        $strategy = (string)($options['priceStrategy'] ?? 'lowest');
        $pick = null;

        switch ($strategy) {
            case 'highest':
                $pick = end($pps); // sorted asc
                break;

            case 'closest':
                $target = (float)($options['closestTarget'] ?? 0.0);
                $pick = $this->closestTo($pps, $target);
                break;

            case 'preferIds':
                $ids = array_map('intval', $options['preferIds'] ?? []);
                $pick = $this->preferIds($pps, $ids) ?? $pps[0];
                break;

            case 'preferPrice':
                $p = isset($options['preferPrice']) ? (float)$options['preferPrice'] : null;
                $pick = ($p !== null) ? $this->closestTo($pps, $p) : $pps[0];
                break;

            case 'lowest':
            default:
                $pick = $pps[0];
        }

        return [$pick?->id, $pick?->price];
    }

    /** @param PricePoint[] $pps */
    private function closestTo(array $pps, float $target): ?PricePoint
    {
        $best = null;
        $bestDiff = PHP_FLOAT_MAX;
        foreach ($pps as $pp) {
            $diff = abs($pp->price - $target);
            if ($diff < $bestDiff) {
                $bestDiff = $diff;
                $best = $pp;
            }
        }
        return $best;
    }

    /** @param PricePoint[] $pps @param int[] $ids */
    private function preferIds(array $pps, array $ids): ?PricePoint
    {
        $set = array_flip($ids);
        foreach ($pps as $pp) {
            if (isset($set[$pp->id])) return $pp;
        }
        return null;
    }

    /**
     * @return array<string, string|int|float|null>
     */
    private function buildPayload(CheckRequest $req, GameDefinition $def, ?int $ppId, ?float $ppPrice): array
    {
        $payload = [
            'user.userId'                     => $req->userId,
            'user.zoneId'                     => $req->serverId,
            'voucherPricePoint.id'            => $ppId,
            'voucherPricePoint.price'         => $ppPrice,
            'voucherPricePoint.variablePrice' => 0,
            'pcId'                            => (int)($def->fixed['pcId'] ?? 227),
            'shopLang'                        => (string)($def->fixed['shopLang'] ?? 'id_ID'),
            'absoluteUrl'                     => $req->absoluteUrl ?: $def->absoluteUrl,
            'gvtId'                           => $def->fixed['gvtId'] ?? null,
            'lvtId'                           => $def->fixed['lvtId'] ?? null,
            'voucherTypeId'                   => $def->fixed['voucherTypeId'] ?? null,
            'voucherTypeName'                 => $def->fixed['voucherTypeName'] ?? null,
        ];

        // Mirror serverId to exUserInfo unless overridden
        if (!array_key_exists('exUserInfo', $req->extras) && $req->serverId !== null) {
            $payload['exUserInfo'] = $req->serverId;
        }

        // Include discovered tokens
        foreach ($def->tokens as $k => $v) $payload[$k] = $v;

        // Extras passthrough (e.g., 'n', 'order.data.profile', etc.)
        foreach ($req->extras as $k => $v) $payload[(string)$k] = $v;

        // Optional helper to auto-add an empty profile if requested
        if (!isset($payload['order.data.profile']) && ($req->extras['__withProfile'] ?? false)) {
            $payload['order.data.profile'] = OrderProfileEncoder::encode();
        }

        return $payload;
    }
}
