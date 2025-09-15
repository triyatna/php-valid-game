<?php

declare(strict_types=1);

namespace Triyatna\ValidGame\DTO;

final class CheckResult
{
    public function __construct(
        public readonly bool $ok,
        public readonly string $message,
        public readonly ?string $nickname = null,
        public readonly ?string $server = null,
        public readonly int $httpStatus = 0,
        /** @var array<string,mixed>|null */
        public readonly ?array $raw = null
    ) {}
}
