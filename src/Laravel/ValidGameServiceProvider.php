<?php

declare(strict_types=1);

namespace Triyatna\ValidGame\Laravel;

use Illuminate\Support\ServiceProvider;
use Triyatna\ValidGame\Validator;

final class ValidGameServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Validator::class, fn() => new Validator());
    }
}
