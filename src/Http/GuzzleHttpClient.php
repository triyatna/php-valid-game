<?php

declare(strict_types=1);

namespace Triyatna\ValidGame\Http;

use GuzzleHttp\Client;
use Triyatna\ValidGame\Contracts\HttpClientInterface;

final class GuzzleHttpClient implements HttpClientInterface
{
    public function __construct(private readonly ?Client $client = null) {}

    public function postForm(string $url, array $headers, array $form, array $options = []): array
    {
        $client = $this->client ?? new Client();

        $resp = $client->post($url, [
            'headers'        => $headers,
            'form_params'    => $form,
            'timeout'        => $options['timeout'] ?? 15.0,
            'connect_timeout' => $options['connect_timeout'] ?? 5.0,
            'proxy'          => $options['proxy'] ?? null,
            'debug'          => $options['debug'] ?? false,
        ]);

        return [
            'status'  => $resp->getStatusCode(),
            'headers' => $resp->getHeaders(),
            'body'    => (string)$resp->getBody(),
        ];
    }

    public function get(string $url, array $headers = [], array $options = []): array
    {
        $client = $this->client ?? new Client();

        $resp = $client->get($url, [
            'headers'        => $headers,
            'timeout'        => $options['timeout'] ?? 15.0,
            'connect_timeout' => $options['connect_timeout'] ?? 5.0,
            'proxy'          => $options['proxy'] ?? null,
            'debug'          => $options['debug'] ?? false,
        ]);

        return [
            'status'  => $resp->getStatusCode(),
            'headers' => $resp->getHeaders(),
            'body'    => (string)$resp->getBody(),
        ];
    }
}
