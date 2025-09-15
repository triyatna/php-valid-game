<?php

declare(strict_types=1);

namespace Triyatna\PhpValidGame\Support;

final class Str
{
    public static function canonicalGame(string $name): string
    {
        $s = \mb_strtolower(\trim($name));
        // normalize separators
        $s = \preg_replace('/[^a-z0-9]+/i', '', $s) ?? $s;
        return $s;
    }
}
