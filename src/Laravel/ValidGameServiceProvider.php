<?php

declare(strict_types=1);

namespace Triyatna\PhpValidGame\Laravel;

use Illuminate\Support\ServiceProvider;
use Psr\Log\LoggerInterface;
use Triyatna\PhpValidGame\Enums\Provider;
use Triyatna\PhpValidGame\ValidGameClient;

/**
 * Laravel auto-discovery service provider.
 *
 * Registers ValidGameClient as a singleton in the container,
 * publishes configuration, and provides sensible defaults.
 */
final class ValidGameServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/valid-game.php',
            'valid-game',
        );

        $this->app->singleton(ValidGameClient::class, function ($app) {
            /** @var array<string, mixed> $config */
            $config = $app['config']->get('valid-game', []);

            /** @var LoggerInterface|null $logger */
            $logger = $app->has('log') ? $app->make('log') : null;

            $preferred = match ($config['preferred_provider'] ?? 'codashop') {
                'gopaygames', 'gopay' => Provider::GOPAY_GAMES,
                default               => Provider::CODASHOP,
            };

            return new ValidGameClient(
                preferredProvider: $preferred,
                fallback: (bool) ($config['fallback'] ?? true),
                proxy: $config['proxy'] ?? null,
                debug: (bool) ($config['debug'] ?? false),
                logger: $logger,
                timeout: (int) ($config['timeout'] ?? 15),
            );
        });
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../../config/valid-game.php' => config_path('valid-game.php'),
            ], 'valid-game-config');
        }
    }
}
