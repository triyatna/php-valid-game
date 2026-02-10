<?php

declare(strict_types=1);

namespace Triyatna\PhpValidGame\DTO;

use Triyatna\PhpValidGame\Enums\StatusCode;

/**
 * Unified validation result envelope.
 *
 * Returned from every validation attempt, regardless of the provider used.
 */
final class ValidationResult
{
    public function __construct(
        public readonly bool $status,
        public readonly StatusCode $code,
        public readonly ?string $message = null,
        public readonly ?string $game = null,
        public readonly string|int|null $userId = null,
        public readonly string|int|null $zoneId = null,
        public readonly ?string $nickname = null,
        public readonly ?string $provider = null,
        public readonly ?int $httpStatus = null,
        public readonly ?string $timestamp = null,
        /** @var array<string,mixed>|null */
        public readonly ?array $meta = null,
    ) {}

    /**
     * Named constructor for convenient creation.
     *
     * @param array<string,mixed>|null $meta
     */
    public static function make(
        bool $status,
        StatusCode $code,
        ?string $message = null,
        ?string $game = null,
        string|int|null $userId = null,
        string|int|null $zoneId = null,
        ?string $nickname = null,
        ?string $provider = null,
        ?int $httpStatus = null,
        ?array $meta = null,
    ): self {
        return new self(
            status: $status,
            code: $code,
            message: $message,
            game: $game,
            userId: $userId,
            zoneId: $zoneId,
            nickname: $nickname,
            provider: $provider,
            httpStatus: $httpStatus,
            timestamp: (new \DateTimeImmutable('now'))->format(\DateTimeInterface::ATOM),
            meta: $meta,
        );
    }

    /**
     * Convert to a plain array for JSON serialization.
     *
     * @return array<string,mixed>
     */
    public function toArray(): array
    {
        return [
            'status'     => $this->status,
            'code'       => $this->code->value,
            'message'    => $this->message,
            'game'       => $this->game,
            'userId'     => $this->userId,
            'zoneId'     => $this->zoneId,
            'nickname'   => $this->nickname,
            'provider'   => $this->provider,
            'httpStatus' => $this->httpStatus,
            'timestamp'  => $this->timestamp ?? (new \DateTimeImmutable('now'))->format(\DateTimeInterface::ATOM),
            'meta'       => $this->meta,
        ];
    }

    /**
     * Convert to JSON string.
     */
    public function toJson(int $flags = JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE): string
    {
        return (string) json_encode($this->toArray(), $flags);
    }

    /**
     * Check if the validation was successful.
     */
    public function isValid(): bool
    {
        return $this->status;
    }
}
