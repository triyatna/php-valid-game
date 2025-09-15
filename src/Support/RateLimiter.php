<?php

declare(strict_types=1);

namespace Triyatna\ValidGame\Support;

final class RateLimiter
{
    private int $tokens;
    private float $last;

    public function __construct(private readonly int $capacity = 6, private readonly float $rps = 1.0)
    {
        $this->tokens = $capacity;
        $this->last = microtime(true);
    }

    public function take(int $cost = 1): bool
    {
        $now = microtime(true);
        $elapsed = $now - $this->last;
        $refill = (int)($elapsed * $this->rps);
        if ($refill > 0) {
            $this->tokens = min($this->capacity, $this->tokens + $refill);
            $this->last = $now;
        }
        if ($this->tokens >= $cost) {
            $this->tokens -= $cost;
            return true;
        }
        return false;
    }
}
