<?php

declare(strict_types=1);

namespace Triyatna\PhpValidGame\Enums;

/**
 * Machine-friendly status codes for validation results.
 */
enum StatusCode: string
{
    case OK                = 'OK';
    case INVALID_INPUT     = 'INVALID_INPUT';
    case UNKNOWN_GAME      = 'UNKNOWN_GAME';
    case HTTP_ERROR        = 'HTTP_ERROR';
    case API_ERROR         = 'API_ERROR';
    case NON_JSON          = 'NON_JSON';
    case UNEXPECTED_FORMAT = 'UNEXPECTED_FORMAT';
    case EXCEPTION         = 'EXCEPTION';
    case PROVIDER_ERROR    = 'PROVIDER_ERROR';
}
