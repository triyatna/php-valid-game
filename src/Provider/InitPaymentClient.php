<?php

declare(strict_types=1);

namespace Triyatna\ValidGame\Provider;

use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;
use Triyatna\ValidGame\Contracts\HttpClientInterface;

final class InitPaymentClient
{
    public const BASE_URI = 'https://order-sg.codashop.com/initPayment.action';

    public function __construct(
        private readonly HttpClientInterface $http,
        private readonly ?LoggerInterface $logger = null,
        private readonly ?string $proxy = null,
        private readonly bool $debug = false,
        private readonly float $timeout = 15.0
    ) {}

    /**
     * @param array<string, string|int|float|null> $payload
     * @return array{status:int,json:?array,raw:string}
     */
    public function post(array $payload): array
    {
        $headers = [
            'User-Agent'      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/127.0.0.0 Safari/537.36',
            'Accept'          => 'application/json',
            'Accept-Language' => 'id-ID',
            'Origin'          => 'https://www.codashop.com',
            'Referer'         => 'https://www.codashop.com/',
        ];

        // Required identifiers + sane defaults
        $payload += [
            'deviceId'                        => $payload['deviceId']      ?? Uuid::uuid4()->toString(),
            'userSessionId'                   => $payload['userSessionId'] ?? Uuid::uuid4()->toString(),
            'checkoutId'                      => $payload['checkoutId']    ?? Uuid::uuid4()->toString(),
            'userVariablePrice'               => $payload['userVariablePrice'] ?? 0,
            'isRiskCheckingEnabled'           => $payload['isRiskCheckingEnabled'] ?? false,
            'userCustomCommerceEmailConsent'  => $payload['userCustomCommerceEmailConsent'] ?? false,
            'userEmailConsent'                => $payload['userEmailConsent'] ?? false,
            'userMarketingConsent'            => $payload['userMarketingConsent'] ?? false,
            'userMobileConsent'               => $payload['userMobileConsent'] ?? false,
        ];

        $this->logger?->info('ValidGame: sending request', ['keys' => array_keys($payload)]);

        $resp = $this->http->postForm(self::BASE_URI, $headers, $payload, [
            'proxy'   => $this->proxy,
            'timeout' => $this->timeout,
            'debug'   => $this->debug,
        ]);

        $json = null;
        $decoded = json_decode($resp['body'], true);
        if (is_array($decoded)) {
            $json = $decoded;
        }

        if ($this->debug) {
            $this->logger?->debug('ValidGame: provider response', [
                'status' => $resp['status'],
                'sample' => substr($resp['body'], 0, 1024),
            ]);
        }

        return ['status' => $resp['status'], 'json' => $json, 'raw' => $resp['body']];
    }
}
