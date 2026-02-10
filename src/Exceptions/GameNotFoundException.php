<?php

declare(strict_types=1);

namespace Triyatna\PhpValidGame\Exceptions;

/**
 * Thrown when the game code is not found in the registry.
 */
final class GameNotFoundException extends \InvalidArgumentException {}
