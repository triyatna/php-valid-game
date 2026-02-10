<?php

declare(strict_types=1);

namespace Triyatna\PhpValidGame\DTO;

use Triyatna\PhpValidGame\Enums\StatusCode;
use Triyatna\PhpValidGame\Registry\GameRegistry;

/**
 * Unified validation result envelope.
 *
 * Returned from every validation attempt, regardless of the provider used.
 * Provides a clean, professional API with essential information.
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
        // Internal/debug fields (not included in public API)
        private readonly ?int $httpStatus = null,
        private readonly ?string $timestamp = null,
        /** @var array<string,mixed>|null */
        private readonly ?array $meta = null
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
        ?array $meta = null
    ): self {
        return new self(
            status: $status,
            code: $code,
            message: $message ?? $code->value,
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
     * Convert to a clean array for JSON serialization.
     * Returns nested structure with status, message, and data object.
     *
     * @return array<string,mixed>
     */
    public function toArray(): array
    {
        $canonicalGame = $this->game ? GameRegistry::resolveCanonical($this->game) : null;
        $gameDefinition = $canonicalGame ? GameRegistry::get($canonicalGame) : null;
        $gameLabel = $gameDefinition['label'] ?? $this->game ?? '';

        return [
            'status' => $this->status,
            'message' => $this->status
                ? 'User ID is valid.'
                : ($this->message ?? $this->code->value),
            'data' => [
                'game' => $gameLabel,
                'nickname' => $this->status ? $this->nickname : '',
                'country' => '', // TODO: Implement country detection from zoneId (e.g., zoneId patterns for different countries)
            ],
        ];
    }

    /**
     * Convert to JSON string with clean, professional format.
     */
    public function toJson(int $flags = JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE): string
    {
        return (string) json_encode($this->toArray(), $flags);
    }

    /**
     * Get debug information (HTTP status, timestamp, raw data).
     * Only available when debug mode is enabled.
     *
     * @return array<string,mixed>
     */
    public function debug(): array
    {
        return [
            'httpStatus' => $this->httpStatus,
            'timestamp'  => $this->timestamp,
            'meta'       => $this->meta,
        ];
    }

    /**
     * Check if the validation was successful.
     */
    public function isValid(): bool
    {
        return $this->status;
    }

    /**
     * Get the status code as string.
     */
    public function getCode(): string
    {
        return $this->code->value;
    }

    /**
     * Get the human-readable message.
     */
    public function getMessage(): string
    {
        return $this->message ?? $this->code->value;
    }
}
