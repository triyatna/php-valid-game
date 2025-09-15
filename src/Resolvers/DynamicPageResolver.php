<?php

declare(strict_types=1);

namespace Triyatna\PhpValidGame\Resolvers;

use Psr\Log\LoggerInterface;
use Triyatna\PhpValidGame\Contracts\PageResolverInterface;
use Triyatna\PhpValidGame\Transport\BrowserClient;

final class DynamicPageResolver implements PageResolverInterface
{
    private BrowserClient $browser;

    public function __construct(
        ?BrowserClient $browser = null,
        private readonly ?LoggerInterface $logger = null,
        private readonly bool $debug = false
    ) {
        $this->browser = $browser ?? new BrowserClient(debug: $this->debug, logger: $this->logger);
    }

    public function resolveFromPath(
        string $pathSlug,
        string|int $uid,
        string|int|null $server = null,
        ?int $pricePointId = null,
        float|int|null $price = null
    ): array {
        $url = 'https://www.codashop.com/id-id/' . \ltrim($pathSlug, '/');
        $page = $this->browser->get($url);
        if (($page['status'] ?? 0) < 200 || ($page['status'] ?? 0) >= 300) {
            throw new \RuntimeException('SCRAPE_HTTP_ERROR: failed to fetch game page (status ' . $page['status'] . ')');
        }

        $html = $page['body'] ?? '';
        if ($html === '') {
            throw new \RuntimeException('SCRAPE_HTTP_ERROR: empty body');
        }

        // Extract helpers (regex tolerant for JSON-inlined data structures)
        $grab = static function (string $src, array $patterns): ?string {
            foreach ($patterns as $pat) {
                if (\preg_match($pat, $src, $m) === 1 && isset($m[1]) && $m[1] !== '') {
                    return \html_entity_decode($m[1], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                }
            }
            return null;
        };

        // JWT-ish tokens might contain dots and slashes etc.
        $voucherTypeName = $grab($html, [
            '/"voucherTypeName"\s*:\s*"([^"]+)"/i',
            "/voucherTypeName'\s*:\s*'([^']+)'/i",
        ]);

        $voucherTypeId = $grab($html, [
            '/"voucherTypeId"\s*:\s*"([^"]+)"/i',
            '/"voucherTypeId"\s*:\s*([0-9]+)/i',
        ]);

        $gvtId = $grab($html, [
            '/"gvtId"\s*:\s*"([^"]+)"/i',
            '/"gvtId"\s*:\s*([0-9]+)/i',
        ]);

        $lvtId = $grab($html, [
            '/"lvtId"\s*:\s*"([^"]+)"/i',
            '/"lvtId"\s*:\s*([0-9]+)/i',
        ]);

        $pcId = $grab($html, [
            '/"pcId"\s*:\s*"([^"]+)"/i',
            '/"pcId"\s*:\s*([0-9]+)/i',
        ]);

        $dynamicSkuToken = $grab($html, [
            '/"dynamicSkuToken"\s*:\s*"([^"]+)"/i',
            "/dynamicSkuToken'\s*:\s*'([^']+)'/i",
        ]);

        $pricePointDynamicSkuToken = $grab($html, [
            '/"pricePointDynamicSkuToken"\s*:\s*"([^"]+)"/i',
            "/pricePointDynamicSkuToken'\s*:\s*'([^']+)'/i",
        ]);

        // Try to pick a pricePoint id+price from the page if not provided.
        $detectedPricePointId = $grab($html, [
            '/"voucherPricePoint"\s*:\s*{\s*"id"\s*:\s*"?(?<id>[0-9]+)"?/i',
            '/"voucherPricePointList".*?"id"\s*:\s*"?(?<id>[0-9]+)"?/is',
        ]);
        $detectedPrice = $grab($html, [
            '/"voucherPricePoint"\s*:\s*{[^}]*"price"\s*:\s*"?(?<p>[0-9]+(?:\.[0-9]+)?)"?/i',
            '/"voucherPricePointList".*?"price"\s*:\s*"?(?<p>[0-9]+(?:\.[0-9]+)?)"?/is',
        ]);

        // Validate minimal fields
        $missing = [];
        foreach (
            [
                'voucherTypeName' => $voucherTypeName,
                'voucherTypeId'   => $voucherTypeId,
                'gvtId'           => $gvtId,
                'lvtId'           => $lvtId,
                'pcId'            => $pcId,
                'dynamicSkuToken' => $dynamicSkuToken,
                'pricePointDynamicSkuToken' => $pricePointDynamicSkuToken,
            ] as $k => $v
        ) {
            if ($v === null || $v === '') {
                $missing[] = $k;
            }
        }

        // If pricePointId/price not provided and not detected, default gracefully
        $ppId   = (string)($pricePointId ?? $detectedPricePointId ?? '1');
        $ppPrice = (string)($price         ?? $detectedPrice       ?? '10000');

        if (!empty($missing)) {
            // We still return a payload if you want to attempt posting, but usually better to throw:
            throw new \RuntimeException('SCRAPE_FIELD_MISSING: ' . \implode(',', $missing));
        }

        // Build final payload (filtered nulls)
        $payload = [
            'voucherPricePoint.id'          => $ppId,
            'voucherPricePoint.price'       => $ppPrice,
            'voucherPricePoint.variablePrice' => '0',
            'user.userId'                   => (string)$uid,
            'user.zoneId'                   => $server !== null && $server !== '' ? (string)$server : null,
            'voucherTypeName'               => $voucherTypeName,
            'voucherTypeId'                 => $voucherTypeId,
            'gvtId'                         => $gvtId,
            'lvtId'                         => $lvtId,
            'pcId'                          => $pcId,
            'shopLang'                      => 'id_ID',
            'absoluteUrl'                   => $url,
            'dynamicSkuToken'               => $dynamicSkuToken,
            'pricePointDynamicSkuToken'     => $pricePointDynamicSkuToken,
            // many optional keys on the page are not required by init endpoint; omit for minimal friction
        ];

        return \array_filter(
            $payload,
            static fn($v) => $v !== null
        );
    }
}
