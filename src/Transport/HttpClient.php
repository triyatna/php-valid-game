<?php

declare(strict_types=1);

namespace Triyatna\PhpValidGame\Transport;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerInterface;
use Triyatna\PhpValidGame\Exceptions\HttpException;

/**
 * Thin HTTP wrapper around Guzzle for making provider requests.
 */
final class HttpClient
{
    private Client $client;

    public function __construct(
        private readonly ?string $proxy = null,
        private readonly int $timeout = 15,
        private readonly bool $debug = false,
        private readonly ?LoggerInterface $logger = null,
    ) {
        $options = [
            'timeout' => $this->timeout,
            'allow_redirects' => true,
            'http_errors' => false,
        ];

        if ($this->proxy) {
            $options['proxy'] = $this->proxy;
        }

        $this->client = new Client($options);
    }

    /**
     * Send a POST request with form data (Codashop-style).
     *
     * @param string               $url
     * @param array<string,mixed>  $formParams
     * @param array<string,string> $headers
     * @return array{status:int, headers:array<string,mixed>, body:string}
     */
    public function postForm(string $url, array $formParams, array $headers = []): array
    {
        $defaultHeaders = [
            'User-Agent'      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36',
            'Accept'          => 'application/json',
            'Accept-Language' => 'id-ID',
            'Origin'          => 'https://www.codashop.com',
            'Referer'         => 'https://www.codashop.com/',
        ];

        return $this->send('POST', $url, [
            'form_params' => $formParams,
            'headers' => \array_merge($defaultHeaders, $headers),
        ]);
    }

    /**
     * Send a POST request with JSON body (GoPay Games-style).
     *
     * @param string              $url
     * @param array<string,mixed> $jsonBody
     * @param array<string,string> $headers
     * @return array{status:int, headers:array<string,mixed>, body:string}
     */
    public function postJson(string $url, array $jsonBody, array $headers = []): array
    {
        $defaultHeaders = [
            'User-Agent'   => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36',
            'Accept'       => 'application/json',
            'Content-Type' => 'application/json',
        ];

        return $this->send('POST', $url, [
            'json' => $jsonBody,
            'headers' => \array_merge($defaultHeaders, $headers),
        ]);
    }

    /**
     * @param array<string,mixed> $options
     * @return array{status:int, headers:array<string,mixed>, body:string}
     */
    private function send(string $method, string $url, array $options): array
    {
        $t0 = \microtime(true);

        try {
            $response = $this->client->request($method, $url, $options);
        } catch (GuzzleException $e) {
            $this->logger?->error('ValidGame HTTP exception', [
                'url'   => $url,
                'error' => $e->getMessage(),
            ]);

            throw new HttpException($e->getMessage(), previous: $e);
        }

        $elapsed = (int) \round((\microtime(true) - $t0) * 1000);
        $status  = $response->getStatusCode();
        $headers = $response->getHeaders();
        $body    = (string) $response->getBody();

        if ($this->debug) {
            $this->logger?->debug('ValidGame HTTP response', [
                'url'        => $url,
                'method'     => $method,
                'status'     => $status,
                'elapsed_ms' => $elapsed,
                'body_preview' => \mb_substr($body, 0, 2000),
            ]);
        }

        return [
            'status'  => $status,
            'headers' => $headers,
            'body'    => $body,
        ];
    }
}
