<?php

declare(strict_types=1);

namespace Triyatna\PhpValidGame\DTO;

final class GameResult
{
    /**
     * Unified result envelope for all checks.
     *
     * - status:     true on success, false otherwise
     * - code:       machine-friendly code (OK, INVALID_INPUT, HTTP_ERROR, API_ERROR, NON_JSON, UNEXPECTED_FORMAT, EXCEPTION, UNKNOWN_GAME)
     * - message:    short human-readable message
     * - game:       canonical game code (e.g., "freefire")
     * - uid:        provided user id
     * - server:     provided server id/name (or extracted)
     * - nickname:   extracted nickname (if available)
     * - httpStatus: integer HTTP status if a request occurred
     * - timestamp:  ISO 8601
     * - meta:       array of extra diagnostics (only set if debug=true)
     */
    public function __construct(
        public bool $status,
        public string $code,
        public ?string $message = null,
        public ?string $game = null,
        public string|int|null $uid = null,
        public string|int|null $server = null,
        public ?string $nickname = null,
        public ?int $httpStatus = null,
        public ?string $timestamp = null,
        /** @var array<string,mixed>|null */
        public ?array $meta = null
    ) {}

    /** @return array<string,mixed> */
    public function toArray(): array
    {
        return [
            'status'     => $this->status,
            'code'       => $this->code,
            'message'    => $this->message,
            'game'       => $this->game,
            'uid'        => $this->uid,
            'server'     => $this->server,
            'nickname'   => $this->nickname,
            'httpStatus' => $this->httpStatus,
            'timestamp'  => $this->timestamp ?? (new \DateTimeImmutable('now'))->format(\DateTimeInterface::ATOM),
            'meta'       => $this->meta,
        ];
    }

    /** @param array<string,mixed>|null $meta */
    public static function make(
        bool $status,
        string $code,
        ?string $message = null,
        ?string $game = null,
        string|int|null $uid = null,
        string|int|null $server = null,
        ?string $nickname = null,
        ?int $httpStatus = null,
        ?array $meta = null
    ): self {
        return new self(
            status: $status,
            code: $code,
            message: $message,
            game: $game,
            uid: $uid,
            server: $server,
            nickname: $nickname,
            httpStatus: $httpStatus,
            timestamp: (new \DateTimeImmutable('now'))->format(\DateTimeInterface::ATOM),
            meta: $meta
        );
    }
}
