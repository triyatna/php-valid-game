<?php

declare(strict_types=1);

namespace Triyatna\PhpValidGame\Laravel;

use Illuminate\Support\ServiceProvider;
use Psr\Log\LoggerInterface;
use Triyatna\PhpValidGame\ValidGameClient;

final class ValidGameServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ValidGameClient::class, function ($app) {
            /** @var LoggerInterface|null $logger */
            $logger = $app->has('log') ? $app->make('log') : null;

            return new ValidGameClient(
                resolver: null,
                proxy: null,
                debug: false,
                logger: $logger,
                treatUnknownAsSuccess: false
            );
        });
    }
}
