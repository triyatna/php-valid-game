<?php

declare(strict_types=1);

namespace Triyatna\ValidGame\Registry;

use Triyatna\ValidGame\DTO\GameDefinition;

final class GameRegistry
{
    /** @var array<string, GameDefinition> */
    private array $items = [];

    public function __construct()
    {
        // Minimal built-ins; tokens are discovered dynamically when needed
        $this->put(new GameDefinition(
            gameCode: 'mobile-legends',
            absoluteUrl: 'https://www.codashop.com/id-id/mobile-legends',
            tokens: [],
            fixed: ['voucherTypeName' => 'MOBILE_LEGENDS', 'pcId' => 227, 'shopLang' => 'id_ID']
        ));

        $this->put(new GameDefinition(
            gameCode: 'genshin-impact',
            absoluteUrl: 'https://www.codashop.com/id-id/genshin-impact',
            tokens: [],
            fixed: ['voucherTypeName' => 'GENSHIN_IMPACT', 'pcId' => 227, 'shopLang' => 'id_ID']
        ));

        $this->put(new GameDefinition(
            gameCode: 'free-fire',
            absoluteUrl: 'https://www.codashop.com/id-id/free-fire',
            tokens: [],
            fixed: ['voucherTypeName' => 'FREEFIRE', 'pcId' => 227, 'shopLang' => 'id_ID']
        ));
    }

    public function get(string $gameCode): ?GameDefinition
    {
        $key = $this->normalize($gameCode);
        return $this->items[$key] ?? null;
    }

    public function put(GameDefinition $def): void
    {
        $this->items[$this->normalize($def->gameCode)] = $def;
    }

    private function normalize(string $code): string
    {
        $slug = strtolower(trim($code));
        $slug = str_replace([' ', '_'], '-', $slug);
        return preg_replace('~[^a-z0-9\-]+~', '', $slug) ?? $slug;
    }
}
