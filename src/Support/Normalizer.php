<?php

declare(strict_types=1);

namespace Triyatna\PhpValidGame\Support;

/**
 * String normalization utilities.
 */
final class Normalizer
{
    /**
     * Convert a game name/alias to canonical form.
     *
     * Strips all non-alphanumeric characters and lowercases.
     * e.g., "Mobile Legends" => "mobilelegends", "Free Fire" => "freefire"
     */
    public static function canonicalGame(string $name): string
    {
        $s = \mb_strtolower(\trim($name));

        return \preg_replace('/[^a-z0-9]+/', '', $s) ?? $s;
    }

    /**
     * Fetch a value from a nested array using dot notation.
     *
     * Supports numeric indices: "confirmationFields.roles.0.role"
     */
    public static function dotGet(array $source, string $path): mixed
    {
        $parts = \explode('.', $path);
        $current = $source;

        foreach ($parts as $part) {
            if (!\is_array($current)) {
                return null;
            }

            if (\ctype_digit($part)) {
                $current = $current[(int) $part] ?? null;
            } else {
                $current = $current[$part] ?? null;
            }
        }

        return $current;
    }
}
