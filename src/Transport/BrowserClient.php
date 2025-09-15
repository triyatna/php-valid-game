<?php

declare(strict_types=1);

namespace Triyatna\PhpValidGame\Transport;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerInterface;

final class BrowserClient
{
    private Client $client;

    public function __construct(
        ?string $proxy = null,
        private readonly bool $debug = false,
        private readonly ?LoggerInterface $logger = null,
        private readonly int $timeout = 12
    ) {
        $headers = [
            'User-Agent'        => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/127.0.0.0 Safari/537.36',
            'Accept-Language'   => 'id-ID',
            'Accept'            => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Cache-Control'     => 'no-cache',
            'Pragma'            => 'no-cache',
        ];

        $options = [
            'timeout'  => $this->timeout,
            'headers'  => $headers,
            'allow_redirects' => true,
        ];

        if ($proxy) {
            $options['proxy'] = $proxy;
        }

        $this->client = new Client($options);
    }

    /** @return array{status:int,headers:array<string,mixed>,body:string} */
    public function get(string $url): array
    {
        $t0 = \microtime(true);
        try {
            $resp = $this->client->get($url, ['http_errors' => false]);
        } catch (GuzzleException $e) {
            $this->logger?->error('BrowserClient GET exception', ['error' => $e->getMessage()]);
            return ['status' => 0, 'headers' => [], 'body' => ''];
        }

        $elapsed = (int)\round((\microtime(true) - $t0) * 1000);
        $status  = $resp->getStatusCode();
        $headers = $resp->getHeaders();
        $body    = (string)$resp->getBody();

        if ($this->debug) {
            $this->logger?->debug('BrowserClient GET', [
                'url' => $url,
                'status' => $status,
                'elapsed_ms' => $elapsed,
                'body_preview' => \mb_substr($body, 0, 1500),
            ]);
        }

        return ['status' => $status, 'headers' => $headers, 'body' => $body];
    }
}
