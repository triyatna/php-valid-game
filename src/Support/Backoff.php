<?php

declare(strict_types=1);

namespace Triyatna\ValidGame\Support;

final class Backoff
{
    public static function sequence(int $retries, float $baseMs = 200.0, float $factor = 2.0): array
    {
        $delays = [];
        $ms = $baseMs;
        for ($i = 0; $i < $retries; $i++) {
            $delays[] = (int)$ms;
            $ms *= $factor;
        }
        return $delays;
    }

    public static function sleepMs(int $ms): void
    {
        usleep(max(0, $ms) * 1000);
    }
}
