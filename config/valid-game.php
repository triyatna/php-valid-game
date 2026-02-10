<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Preferred Provider
    |--------------------------------------------------------------------------
    |
    | The primary provider to use for game ID validation.
    | Available: "codashop", "gopaygames"
    |
    | Default: "codashop"
    |
    */
    'preferred_provider' => env('VALID_GAME_PROVIDER', 'codashop'),

    /*
    |--------------------------------------------------------------------------
    | Fallback
    |--------------------------------------------------------------------------
    |
    | When true, if the preferred provider fails or doesn't support the game,
    | the next available provider will be tried automatically.
    |
    */
    'fallback' => env('VALID_GAME_FALLBACK', true),

    /*
    |--------------------------------------------------------------------------
    | HTTP Proxy
    |--------------------------------------------------------------------------
    |
    | Optional proxy URL for outgoing HTTP requests.
    | Format: "http://user:pass@host:port"
    |
    */
    'proxy' => env('VALID_GAME_PROXY'),

    /*
    |--------------------------------------------------------------------------
    | Debug Mode
    |--------------------------------------------------------------------------
    |
    | When true, raw response data and extra diagnostics will be included
    | in the result's `meta` field and logged via PSR-3 logger.
    |
    */
    'debug' => env('VALID_GAME_DEBUG', false),

    /*
    |--------------------------------------------------------------------------
    | HTTP Timeout
    |--------------------------------------------------------------------------
    |
    | Timeout in seconds for outgoing HTTP requests.
    |
    */
    'timeout' => env('VALID_GAME_TIMEOUT', 15),
];
