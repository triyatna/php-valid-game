<?php

declare(strict_types=1);

namespace Triyatna\PhpValidGame\Enums;

/**
 * Available validation provider identifiers.
 */
enum Provider: string
{
    case CODASHOP    = 'codashop';
    case GOPAY_GAMES = 'gopaygames';
}
