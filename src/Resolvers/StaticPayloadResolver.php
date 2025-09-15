<?php

declare(strict_types=1);

namespace Triyatna\PhpValidGame\Resolvers;

use Triyatna\PhpValidGame\Contracts\PayloadResolverInterface;
use Triyatna\PhpValidGame\Exceptions\InvalidInputException;
use Triyatna\PhpValidGame\Registry\GameRegistry;

final class StaticPayloadResolver implements PayloadResolverInterface
{
    public function __construct()
    {
        GameRegistry::init();
    }

    /** @inheritDoc */
    public function resolve(string $canonicalGame, string|int $uid, string|int|null $server = null): array
    {
        $def = GameRegistry::def($canonicalGame);
        if (!$def) {
            throw new InvalidInputException("Unknown game: {$canonicalGame}");
        }

        if ($def['requiresServer'] ?? false) {
            if ($server === null || $server === '') {
                throw new InvalidInputException("Server/Zone is required for {$canonicalGame}");
            }

            // Map named server to code if mapping exists
            if (isset($def['serverMap']) && \is_array($def['serverMap'])) {
                $key = \mb_strtolower(\preg_replace('/\s+/', '', (string)$server));
                $code = $def['serverMap'][$key] ?? null;
                if ($code !== null) {
                    $server = $code;
                }
            }
        }

        /** @var callable $builder */
        $builder = $def['payload'];
        $payload = $builder($uid, $server);

        // strict sanity
        if (!\is_array($payload) || !isset($payload['voucherTypeName'])) {
            throw new \RuntimeException("Payload builder for {$canonicalGame} returned invalid payload.");
        }

        return $payload;
    }
}
