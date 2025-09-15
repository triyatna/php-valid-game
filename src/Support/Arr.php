<?php

declare(strict_types=1);

namespace Triyatna\ValidGame\Support;

final class Arr
{
    public static function get(array $arr, string $path, mixed $default = null): mixed
    {
        $segments = explode('.', $path);
        foreach ($segments as $seg) {
            if (!is_array($arr) || !array_key_exists($seg, $arr)) return $default;
            $arr = $arr[$seg];
        }
        return $arr;
    }
}
