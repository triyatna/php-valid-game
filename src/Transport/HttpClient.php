<?php

declare(strict_types=1);

namespace Triyatna\PhpValidGame\Transport;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerInterface;
use Triyatna\PhpValidGame\Exceptions\HttpRequestException;

final class HttpClient
{
    private const DEFAULT_BASE_URI = 'https://order-sg.codashop.com';
    private const INIT_PATH = '/initPayment.action';

    private Client $client;

    public function __construct(
        ?string $proxy = null,
        private readonly bool $debug = false,
        private readonly ?LoggerInterface $logger = null,
        private readonly int $timeout = 12
    ) {
        $headers = [
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/127.0.0.0 Safari/537.36',
            'Accept-Language' => 'id-ID',
            'Origin' => 'https://www.codashop.com',
            'Referer' => 'https://www.codashop.com/',
            'Accept' => 'application/json',
        ];

        $options = [
            'base_uri' => self::DEFAULT_BASE_URI,
            'timeout'  => $this->timeout,
            'headers'  => $headers,
            'allow_redirects' => true,
        ];

        if ($proxy) {
            $options['proxy'] = $proxy;
        }

        $this->client = new Client($options);
    }

    /**
     * @param array<string,mixed> $formParams
     * @return array{status:int,headers:array<string,mixed>,body:string}
     */
    public function postInit(array $formParams): array
    {
        $t0 = \microtime(true);

        try {
            $resp = $this->client->post(self::INIT_PATH, [
                'form_params' => $formParams,
                'http_errors' => false,
            ]);
        } catch (GuzzleException $e) {
            $this->logger?->error('ValidGame HTTP exception', ['error' => $e->getMessage()]);
            throw new HttpRequestException($e->getMessage(), previous: $e);
        }

        $elapsed = (int)\round((\microtime(true) - $t0) * 1000);

        $status  = $resp->getStatusCode();
        $headers = $resp->getHeaders();
        $body    = (string)$resp->getBody();

        if ($this->debug) {
            $this->logger?->debug('ValidGame HTTP response', [
                'status' => $status,
                'elapsed_ms' => $elapsed,
                'headers' => $headers,
                'body_preview' => \mb_substr($body, 0, 2000),
            ]);
        }

        return ['status' => $status, 'headers' => $headers, 'body' => $body];
    }
}
