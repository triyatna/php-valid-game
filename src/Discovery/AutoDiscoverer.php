<?php

declare(strict_types=1);

namespace Triyatna\ValidGame\Discovery;

use Triyatna\ValidGame\Contracts\DiscoveryInterface;
use Triyatna\ValidGame\Contracts\HttpClientInterface;
use Triyatna\ValidGame\DTO\GameDefinition;
use Triyatna\ValidGame\DTO\PricePoint;
use Triyatna\ValidGame\Exceptions\DiscoveryException;

final class AutoDiscoverer implements DiscoveryInterface
{
    public function __construct(
        private readonly HttpClientInterface $http,
        private readonly ?string $proxy = null,
        private readonly float $timeout = 15.0,
        private readonly bool $debug = false
    ) {}

    public function discover(string $gameCode, ?string $absoluteUrl = null): ?GameDefinition
    {
        $slugOrPath = $this->normalizeToPathOrSlug($gameCode);
        $url = $absoluteUrl ?: "https://www.codashop.com/{$slugOrPath}";

        $resp = $this->http->get($url, [
            'User-Agent'      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/127.0.0.0 Safari/537.36',
            'Accept-Language' => 'id-ID',
        ], [
            'proxy' => $this->proxy,
            'timeout' => $this->timeout,
            'debug' => $this->debug,
        ]);

        if ($resp['status'] < 200 || $resp['status'] >= 400) {
            throw new DiscoveryException("Product page not reachable (HTTP {$resp['status']}).");
        }

        $html = $resp['body'];

        $tokens = $this->extractTokens($html);
        $fixed  = $this->extractFixed($html);
        $fixed['shopLang'] = $fixed['shopLang'] ?? 'id_ID';
        $fixed['pcId']     = $fixed['pcId'] ?? 227;

        $pps = $this->extractPricePoints($html);

        return new GameDefinition(
            gameCode: $this->normalizeSlug($gameCode),
            absoluteUrl: $url,
            tokens: $tokens,
            fixed: $fixed,
            pricePoints: $pps
        );
    }

    private function normalizeToPathOrSlug(string $code): string
    {
        $trim = ltrim(trim($code), '/');
        if (preg_match('~^[a-z]{2}-[a-z]{2}/~', $trim)) {
            return $trim;
        }
        return 'id-id/' . $this->normalizeSlug($code);
    }

    private function normalizeSlug(string $code): string
    {
        $slug = strtolower(trim($code));
        $slug = str_replace([' ', '_'], '-', $slug);
        return preg_replace('~[^a-z0-9\-]+~', '', $slug) ?? $slug;
    }

    /** @return array<string,string> */
    private function extractTokens(string $html): array
    {
        $tokens = [];
        foreach (['dynamicSkuToken', 'pricePointDynamicSkuToken', 'pricingEngineToken'] as $key) {
            $val = $this->extractQuotedValue($html, $key, 16);
            if ($val !== null) $tokens[$key] = $val;
        }
        return $tokens;
    }

    /** @return array<string, int|string|float|null> */
    private function extractFixed(string $html): array
    {
        $fixed = [];
        foreach (['voucherTypeId' => 'int', 'gvtId' => 'int', 'lvtId' => 'int', 'pcId' => 'int'] as $k => $t) {
            $v = $this->extractScalar($html, $k, $t);
            if ($v !== null) $fixed[$k] = $v;
        }
        $vtn = $this->extractScalar($html, 'voucherTypeName', 'string');
        if ($vtn !== null) $fixed['voucherTypeName'] = $vtn;

        $lang = $this->extractScalar($html, 'shopLang', 'string');
        if ($lang !== null) $fixed['shopLang'] = $lang;

        return $fixed;
    }

    /** @return PricePoint[] */
    private function extractPricePoints(string $html): array
    {
        $points = [];
        // Capture price points (id/price pairs appearing in nearby JSON on the page)
        $pattern = '~"id"\s*:\s*(\d{1,9})[^}]{0,250}?"price"\s*:\s*([0-9]+(?:\.[0-9]+)?)~i';
        if (preg_match_all($pattern, $html, $m, PREG_SET_ORDER)) {
            $seen = [];
            foreach ($m as $row) {
                $id = (int)$row[1];
                $price = (float)$row[2];
                if (!isset($seen[$id])) {
                    $seen[$id] = true;
                    $points[] = new PricePoint($id, $price);
                }
            }
        }
        usort($points, fn(PricePoint $a, PricePoint $b) => $a->price <=> $b->price);
        return $points;
    }

    private function extractQuotedValue(string $html, string $key, int $minLen = 1): ?string
    {
        $pattern = '~' . preg_quote($key, '~') . '\s*["\']?\s*[:=]\s*["\']([^"\']{' . $minLen . ',})["\']~i';
        return preg_match($pattern, $html, $m) ? $m[1] : null;
    }

    private function extractScalar(string $html, string $key, string $type): int|string|null
    {
        $num = '~' . preg_quote($key, '~') . '\s*["\']?\s*[:=]\s*([0-9]{1,12})~i';
        $str = '~' . preg_quote($key, '~') . '\s*["\']?\s*[:=]\s*["\']([^"\']{1,64})["\']~i';
        if ($type === 'int' && preg_match($num, $html, $m)) return (int)$m[1];
        if ($type === 'string' && preg_match($str, $html, $m)) return $m[1];
        return null;
    }
}
