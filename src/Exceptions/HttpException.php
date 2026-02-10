<?php

declare(strict_types=1);

namespace Triyatna\PhpValidGame\Exceptions;

/**
 * Thrown on HTTP transport failures (network, DNS, proxy, timeout).
 */
final class HttpException extends \RuntimeException {}
