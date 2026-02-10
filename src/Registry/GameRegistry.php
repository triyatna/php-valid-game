<?php

declare(strict_types=1);

namespace Triyatna\PhpValidGame\Registry;

use Triyatna\PhpValidGame\Support\Normalizer;

/**
 * Central registry of supported games.
 *
 * Stores canonical codes, aliases, Codashop payloads, GoPay codes,
 * server mappings, and nickname extraction hints.
 *
 * Extensible at runtime via register() and alias().
 */
final class GameRegistry
{
    /** @var array<string, array<string, mixed>> canonical => definition */
    private static array $games = [];

    /** @var array<string, string> alias => canonical */
    private static array $aliases = [];

    private static bool $initialized = false;

    /**
     * Bootstrap built-in game definitions.
     */
    public static function init(): void
    {
        if (self::$initialized) {
            return;
        }

        self::$initialized = true;

        // Helper: build common Codashop price-point fields
        $pp = static fn(string|int $id, string|int $price, int $variablePrice = 0): array => [
            'voucherPricePoint.id'            => (string) $id,
            'voucherPricePoint.price'         => (string) $price,
            'voucherPricePoint.variablePrice' => (string) $variablePrice,
        ];

        self::$games = [
            // ─────────────────────── 8 Ball Pool ───────────────────────
            '8ballpool' => [
                'label'          => '8 Ball Pool',
                'requiresZone'   => false,
                'gopayCode'      => null,
                'codashop'       => [
                    'typeName' => 'EIGHT_BALL_POOL',
                    'payload'  => static fn(string|int $uid, string|int|null $zone) => $pp(272564, '14000.0000') + [
                        'user.userId'     => $uid,
                        'user.zoneId'     => null,
                        'voucherTypeName' => 'EIGHT_BALL_POOL',
                        'shopLang'        => 'id_ID',
                    ],
                ],
                'nicknameFrom' => ['confirmationFields.roles.0.role', 'confirmationFields.username'],
            ],

            // ─────────────────────── Aether Gazer ───────────────────────
            'aethergazer' => [
                'label'          => 'Aether Gazer',
                'requiresZone'   => false,
                'gopayCode'      => null,
                'codashop'       => [
                    'typeName' => 'AETHER_GAZER',
                    'payload'  => static fn(string|int $uid, string|int|null $zone) => $pp('2', '16650.0') + [
                        'user.userId'     => $uid,
                        'user.zoneId'     => null,
                        'voucherTypeName' => '547-AETHER_GAZER',
                        'voucherTypeId'   => '524',
                        'gvtId'           => '691',
                        'lvtId'           => '11840',
                        'pcId'            => '906',
                        'shopLang'        => 'id_ID',
                    ],
                ],
                'nicknameFrom' => ['confirmationFields.username', 'confirmationFields.roles.0.role'],
            ],

            // ─────────────────────── Arena of Valor ───────────────────────
            'aov' => [
                'label'          => 'Arena of Valor',
                'requiresZone'   => false,
                'gopayCode'      => 'AOV',
                'codashop'       => [
                    'typeName' => 'ARENA_OF_VALOR',
                    'payload'  => static fn(string|int $uid, string|int|null $zone) => $pp('270294', '10000.0000') + [
                        'user.userId'     => $uid,
                        'user.zoneId'     => null,
                        'voucherTypeName' => 'AOV',
                        'shopLang'        => 'id_ID',
                    ],
                ],
                'nicknameFrom' => ['confirmationFields.roles.0.role', 'confirmationFields.username'],
                'serverFrom'   => 'confirmationFields.roles.0.server',
            ],

            // ─────────────────────── Auto Chess ───────────────────────
            'autochess' => [
                'label'          => 'Auto Chess',
                'requiresZone'   => false,
                'gopayCode'      => null,
                'codashop'       => [
                    'typeName' => 'AUTO_CHESS',
                    'payload'  => static fn(string|int $uid, string|int|null $zone) => [
                        'voucherPricePoint.id'            => '203879',
                        'voucherPricePoint.price'         => '150000.0000',
                        'voucherPricePoint.variablePrice' => '0',
                        'user.userId'                     => $uid,
                        'voucherTypeName'                 => 'AUTO_CHESS',
                        'shopLang'                        => 'id_ID',
                    ],
                ],
                'nicknameFrom' => ['confirmationFields.username'],
            ],

            // ─────────────────────── Azur Lane ───────────────────────
            'azurlane' => [
                'label'          => 'Azur Lane',
                'requiresZone'   => true,
                'gopayCode'      => null,
                'serverMap'      => [
                    'avrora'           => '1',
                    'lexington'        => '2',
                    'sandy'            => '3',
                    'washington'       => '4',
                    'amagi'            => '5',
                    'littleenterprise' => '6',
                ],
                'codashop' => [
                    'typeName' => 'AZUR_LANE',
                    'payload'  => static fn(string|int $uid, string|int|null $zone) => $pp('99665', '70000.0000') + [
                        'user.userId'     => $uid,
                        'user.zoneId'     => (string) $zone,
                        'voucherTypeName' => 'AZUR_LANE',
                        'shopLang'        => 'id_ID',
                    ],
                ],
                'nicknameFrom' => ['confirmationFields.username'],
            ],

            // ─────────────────────── Badlanders ───────────────────────
            'badlanders' => [
                'label'          => 'Badlanders',
                'requiresZone'   => true,
                'gopayCode'      => null,
                'serverMap'      => [
                    'global' => '11001',
                    'jf'     => '21004',
                ],
                'codashop' => [
                    'typeName' => 'BAD_LANDERS',
                    'payload'  => static fn(string|int $uid, string|int|null $zone) => [
                        'voucherPricePoint.id'            => '333121',
                        'voucherPricePoint.price'         => '2300.0000',
                        'voucherPricePoint.variablePrice' => '0',
                        'user.userId'                     => $uid,
                        'user.zoneId'                     => (string) $zone,
                        'voucherTypeName'                 => 'BAD_LANDERS',
                        'shopLang'                        => 'id_ID',
                    ],
                ],
                'nicknameFrom' => ['confirmationFields.username'],
            ],

            // ─────────────────────── BarbarQ ───────────────────────
            'barbarq' => [
                'label'          => 'BarbarQ',
                'requiresZone'   => false,
                'gopayCode'      => null,
                'codashop'       => [
                    'typeName' => 'BARBARQ',
                    'payload'  => static fn(string|int $uid, string|int|null $zone) => [
                        'voucherPricePoint.id'            => '5145',
                        'voucherPricePoint.price'         => '120000.0000',
                        'voucherPricePoint.variablePrice' => '0',
                        'user.userId'                     => $uid,
                        'voucherTypeName'                 => 'ELECSOUL',
                        'shopLang'                        => 'id_ID',
                    ],
                ],
                'nicknameFrom' => ['confirmationFields.apiResult'],
            ],

            // ─────────────────────── Basketrio ───────────────────────
            'basketrio' => [
                'label'          => 'Basketrio',
                'requiresZone'   => true,
                'gopayCode'      => null,
                'serverMap'      => [
                    'buzzerbeater' => '2',
                    '001'          => '3',
                    '002'          => '4',
                ],
                'codashop' => [
                    'typeName' => 'BASKETRIO',
                    'payload'  => static fn(string|int $uid, string|int|null $zone) => [
                        'voucherPricePoint.id'            => '147203',
                        'voucherPricePoint.price'         => '832500.0000',
                        'voucherPricePoint.variablePrice' => '0',
                        'user.userId'                     => $uid,
                        'user.zoneId'                     => (string) $zone,
                        'voucherTypeName'                 => 'BASKETRIO',
                        'shopLang'                        => 'id_ID',
                    ],
                ],
                'nicknameFrom' => ['confirmationFields.username'],
            ],

            // ─────────────────────── Call of Duty Mobile ───────────────────────
            'cod' => [
                'label'          => 'Call of Duty Mobile',
                'requiresZone'   => false,
                'gopayCode'      => 'CALL_OF_DUTY',
                'codashop'       => [
                    'typeName' => 'CALL_OF_DUTY',
                    'payload'  => static fn(string|int $uid, string|int|null $zone) => [
                        'voucherPricePoint.id'            => '270251',
                        'voucherPricePoint.price'         => '20000.0000',
                        'voucherPricePoint.variablePrice' => '0',
                        'user.userId'                     => $uid,
                        'user.zoneId'                     => null,
                        'voucherTypeName'                 => 'CALL_OF_DUTY',
                        'shopLang'                        => 'id_ID',
                    ],
                ],
                'nicknameFrom' => ['confirmationFields.roles.0.role', 'confirmationFields.username'],
            ],

            // ─────────────────────── Dragon City ───────────────────────
            'dragoncity' => [
                'label'          => 'Dragon City',
                'requiresZone'   => false,
                'gopayCode'      => null,
                'codashop'       => [
                    'typeName' => 'DRAGON_CITY',
                    'payload'  => static fn(string|int $uid, string|int|null $zone) => [
                        'voucherPricePoint.id'            => '254206',
                        'voucherPricePoint.price'         => '65000.0000',
                        'voucherPricePoint.variablePrice' => '0',
                        'user.userId'                     => $uid,
                        'user.zoneId'                     => null,
                        'voucherTypeName'                 => 'DRAGON_CITY',
                        'shopLang'                        => 'id_ID',
                    ],
                ],
                'nicknameFrom' => ['confirmationFields.username'],
            ],

            // ─────────────────────── Free Fire ───────────────────────
            'freefire' => [
                'label'          => 'Free Fire',
                'requiresZone'   => false,
                'gopayCode'      => 'FREEFIRE',
                'codashop'       => [
                    'typeName' => 'FREE_FIRE',
                    'payload'  => static fn(string|int $uid, string|int|null $zone) => [
                        'voucherPricePoint.id'            => '270288',
                        'voucherPricePoint.price'         => '200000.0000',
                        'voucherPricePoint.variablePrice' => '0',
                        'user.userId'                     => $uid,
                        'user.zoneId'                     => null,
                        'voucherTypeName'                 => 'FREEFIRE',
                        'shopLang'                        => 'id_ID',
                    ],
                ],
                'nicknameFrom' => ['confirmationFields.roles.0.role', 'confirmationFields.username'],
            ],

            // ─────────────────────── Genshin Impact ───────────────────────
            'genshinimpact' => [
                'label'          => 'Genshin Impact',
                'requiresZone'   => true,
                'gopayCode'      => 'GENSHIN_IMPACT',
                'serverMap'      => [
                    'os_usa'  => 'os_usa',
                    'os_euro' => 'os_euro',
                    'os_asia' => 'os_asia',
                    'os_cht'  => 'os_cht',
                ],
                'codashop' => [
                    'typeName' => 'GENSHIN_IMPACT',
                    'payload'  => static fn(string|int $uid, string|int|null $zone) => $pp('338498', '16500.0000') + [
                        'user.userId'     => $uid,
                        'user.zoneId'     => (string) $zone,
                        'voucherTypeName' => 'GENSHIN_IMPACT',
                        'shopLang'        => 'id_ID',
                    ],
                ],
                'nicknameFrom' => ['confirmationFields.username'],
            ],

            // ─────────────────────── Hago ───────────────────────
            'hago' => [
                'label'          => 'Hago',
                'requiresZone'   => false,
                'gopayCode'      => null,
                'codashop'       => [
                    'typeName' => 'HAGO',
                    'payload'  => static fn(string|int $uid, string|int|null $zone) => [
                        'voucherPricePoint.id'            => '272113',
                        'voucherPricePoint.price'         => '29700.0000',
                        'voucherPricePoint.variablePrice' => '0',
                        'user.userId'                     => $uid,
                        'user.zoneId'                     => null,
                        'voucherTypeName'                 => 'HAGO',
                        'shopLang'                        => 'id_ID',
                    ],
                ],
                'nicknameFrom' => ['confirmationFields.username'],
            ],

            // ─────────────────────── Honkai Star Rail ───────────────────────
            'honkaistarrail' => [
                'label'          => 'Honkai Star Rail',
                'requiresZone'   => true,
                'gopayCode'      => null,
                'serverMap'      => [
                    'os_usa'      => 'prod_official_usa',
                    'os_euro'     => 'prod_official_eur',
                    'os_asia'     => 'prod_official_asia',
                    'os_cht'      => 'prod_official_cht',
                ],
                'codashop' => [
                    'typeName' => 'HONKAI_STAR_RAIL',
                    'payload'  => static fn(string|int $uid, string|int|null $zone) => $pp('762498', '16500.0000') + [
                        'user.userId'     => $uid,
                        'user.zoneId'     => (string) $zone,
                        'voucherTypeName' => 'HONKAI_STAR_RAIL',
                        'shopLang'        => 'id_ID',
                    ],
                ],
                'nicknameFrom' => ['confirmationFields.username'],
            ],

            // ─────────────────────── Mobile Legends ───────────────────────
            'mobilelegends' => [
                'label'          => 'Mobile Legends',
                'requiresZone'   => true,
                'gopayCode'      => 'MOBILE_LEGENDS',
                'codashop'       => [
                    'typeName' => 'MOBILE_LEGENDS',
                    'payload'  => static fn(string|int $uid, string|int|null $zone) => [
                        'voucherPricePoint.id'            => '5199',
                        'voucherPricePoint.price'         => '68543.0000',
                        'voucherPricePoint.variablePrice' => '0',
                        'user.userId'                     => $uid,
                        'user.zoneId'                     => (string) $zone,
                        'voucherTypeName'                 => 'MOBILE_LEGENDS',
                        'shopLang'                        => 'id_ID',
                    ],
                ],
                'nicknameFrom' => ['confirmationFields.username'],
            ],

            // ─────────────────────── FC Mobile ───────────────────────
            'fcmobile' => [
                'label'          => 'FC Mobile',
                'requiresZone'   => false,
                'gopayCode'      => 'FC_MOBILE',
                'codashop'       => null,
                'nicknameFrom'   => [],
            ],

            // ─────────────────────── Honor of Kings ───────────────────────
            'hok' => [
                'label'          => 'Honor of Kings',
                'requiresZone'   => false,
                'gopayCode'      => 'HOK',
                'codashop'       => null,
                'nicknameFrom'   => [],
            ],

            // ─────────────────────── Magic Chess: Go Go ───────────────────────
            'magicchessgogo' => [
                'label'          => 'Magic Chess: Go Go',
                'requiresZone'   => true,
                'gopayCode'      => 'MAGIC_CHESS_GO_GO',
                'codashop'       => null,
                'nicknameFrom'   => [],
            ],

            // ─────────────────────── Point Blank ───────────────────────
            'pb' => [
                'label'          => 'Point Blank',
                'requiresZone'   => false,
                'gopayCode'      => null,
                'codashop'       => [
                    'typeName' => 'POINT_BLANK',
                    'payload'  => static fn(string|int $uid, string|int|null $zone) => [
                        'voucherPricePoint.id'            => '344845',
                        'voucherPricePoint.price'         => '11000.0000',
                        'voucherPricePoint.variablePrice' => '0',
                        'user.userId'                     => $uid,
                        'user.zoneId'                     => '0',
                        'voucherTypeName'                 => 'POINT_BLANK',
                        'shopLang'                        => 'id_ID',
                    ],
                ],
                'nicknameFrom' => ['confirmationFields.username'],
            ],

            // ─────────────────────── PUBG Mobile ───────────────────────
            'pubg' => [
                'label'          => 'PUBG Mobile',
                'requiresZone'   => false,
                'gopayCode'      => 'PUBG_ID',
                'codashop'       => null,
                'nicknameFrom'   => [],
            ],

            // ─────────────────────── VALORANT ───────────────────────
            'valorant' => [
                'label'          => 'VALORANT',
                'requiresZone'   => false,
                'gopayCode'      => 'VALORANT',
                'codashop'       => [
                    'typeName' => 'VALORANT',
                    'payload'  => static fn(string|int $uid, string|int|null $zone) => [
                        'voucherPricePoint.id'            => '950525',
                        'voucherPricePoint.price'         => '75000.0000',
                        'voucherPricePoint.variablePrice' => '0',
                        'userVariablePrice'               => '0',
                        'user.userId'                     => $uid,
                        'user.zoneId'                     => '0',
                        'voucherTypeName'                 => 'VALORANT',
                        'shopLang'                        => 'id_ID',
                    ],
                ],
                'nicknameFrom' => ['confirmationFields.username'],
            ],

            // ─────────────────────── Zenless Zone Zero ───────────────────────
            'zenlesszonezero' => [
                'label'          => 'Zenless Zone Zero',
                'requiresZone'   => true,
                'gopayCode'      => null,
                'serverMap'      => [
                    'os_usa'  => 'prod_gf_us',
                    'os_euro' => 'prod_gf_eu',
                    'os_asia' => 'prod_gf_jp',
                    'os_cht'  => 'prod_gf_sg',
                ],
                'codashop' => [
                    'typeName' => 'ZENLESS_ZONE_ZERO',
                    'payload'  => static fn(string|int $uid, string|int|null $zone) => $pp('1044968', '16500.0000') + [
                        'user.userId'     => $uid,
                        'user.zoneId'     => (string) $zone,
                        'voucherTypeName' => 'ZENLESS_ZONE_ZERO',
                        'shopLang'        => 'id_ID',
                    ],
                ],
                'nicknameFrom' => ['confirmationFields.username'],
            ],
        ];

        // ─────────────────────── Aliases ───────────────────────
        self::$aliases = [
            'ff'               => 'freefire',
            'garena'           => 'freefire',
            'ml'               => 'mobilelegends',
            'mobilelegend'     => 'mobilelegends',
            'mlbb'             => 'mobilelegends',
            'arenaofvalor'     => 'aov',
            'codm'             => 'cod',
            'callofduty'       => 'cod',
            'callofdutymobile' => 'cod',
            'pointblank'       => 'pb',
            'eightballpool'    => '8ballpool',
            'genshin'          => 'genshinimpact',
            'gi'               => 'genshinimpact',
            'hsr'              => 'honkaistarrail',
            'starrail'         => 'honkaistarrail',
            'zzz'              => 'zenlesszonezero',
            'val'              => 'valorant',
            'pubgmobile'       => 'pubg',
            'pubgm'            => 'pubg',
            'pubgid'           => 'pubg',
            'honorofkings'     => 'hok',
            'fcm'              => 'fcmobile',
            'efootball'        => 'fcmobile',
            'magicchess'       => 'magicchessgogo',
            'mcgg'             => 'magicchessgogo',
        ];
    }

    /**
     * Resolve any input (human name, alias, canonical code) to canonical code.
     */
    public static function resolveCanonical(string $input): ?string
    {
        self::init();

        $key = Normalizer::canonicalGame($input);

        // Direct match
        if (isset(self::$games[$key])) {
            return $key;
        }

        // Alias match
        if (isset(self::$aliases[$key])) {
            return self::$aliases[$key];
        }

        return null;
    }

    /**
     * Get the full definition for a canonical game code.
     *
     * @return array<string, mixed>|null
     */
    public static function get(string $canonical): ?array
    {
        self::init();

        return self::$games[$canonical] ?? null;
    }

    /**
     * Register a new game definition at runtime.
     *
     * @param array<string, mixed> $definition
     */
    public static function register(string $canonical, array $definition): void
    {
        self::init();

        self::$games[$canonical] = $definition;
    }

    /**
     * Register a new alias at runtime.
     */
    public static function alias(string $alias, string $canonical): void
    {
        self::init();

        self::$aliases[Normalizer::canonicalGame($alias)] = $canonical;
    }

    /**
     * Get all registered canonical game codes.
     *
     * @return list<string>
     */
    public static function allCodes(): array
    {
        self::init();

        return \array_keys(self::$games);
    }

    /**
     * Get all registered games with their labels.
     *
     * @return array<string, string> canonical => label
     */
    public static function allGames(): array
    {
        self::init();

        $result = [];
        foreach (self::$games as $code => $def) {
            $result[$code] = $def['label'] ?? $code;
        }

        return $result;
    }

    /**
     * Get a detailed list of all registered games.
     *
     * Returns an array of structured info for every game, including:
     * code, label, requiresZone, supported providers, aliases, and
     * available server map keys.
     *
     * @return list<array{code: string, label: string, requiresZone: bool, providers: list<string>, aliases: list<string>, servers: list<string>}>
     */
    public static function listGames(): array
    {
        self::init();

        // Build reverse alias map: canonical => [alias, ...]
        $aliasMap = [];
        foreach (self::$aliases as $alias => $canonical) {
            $aliasMap[$canonical][] = $alias;
        }

        $list = [];

        foreach (self::$games as $code => $def) {
            $providers = [];
            if (!empty($def['codashop'])) {
                $providers[] = 'codashop';
            }
            if (!empty($def['gopayCode'])) {
                $providers[] = 'gopaygames';
            }

            $servers = [];
            if (isset($def['serverMap']) && \is_array($def['serverMap'])) {
                $servers = \array_keys($def['serverMap']);
            }

            $list[] = [
                'code'         => $code,
                'label'        => $def['label'] ?? $code,
                'requiresZone' => (bool) ($def['requiresZone'] ?? false),
                'providers'    => $providers,
                'aliases'      => $aliasMap[$code] ?? [],
                'servers'      => $servers,
            ];
        }

        return $list;
    }

    /**
     * Check if a game supports a specific provider.
     */
    public static function hasProvider(string $canonical, string $providerKey): bool
    {
        $def = self::get($canonical);
        if ($def === null) {
            return false;
        }

        return match ($providerKey) {
            'codashop'   => !empty($def['codashop']),
            'gopaygames', 'gopay' => !empty($def['gopayCode']),
            default      => false,
        };
    }

    /**
     * Get all canonical codes that support a specific provider.
     *
     * @return list<string>
     */
    public static function gamesForProvider(string $providerKey): array
    {
        self::init();

        $result = [];
        foreach (self::$games as $code => $def) {
            if (self::hasProvider($code, $providerKey)) {
                $result[] = $code;
            }
        }

        return $result;
    }

    /**
     * Get all registered aliases mapped to their canonical codes.
     *
     * @return array<string, string> alias => canonical
     */
    public static function allAliases(): array
    {
        self::init();

        return self::$aliases;
    }

    /**
     * Search games by partial name/label/alias.
     *
     * @return array<string, string> canonical => label
     */
    public static function search(string $query): array
    {
        self::init();

        $needle = Normalizer::canonicalGame($query);
        $result = [];

        // Search in canonical codes and labels
        foreach (self::$games as $code => $def) {
            $label = Normalizer::canonicalGame($def['label'] ?? $code);
            if (\str_contains($code, $needle) || \str_contains($label, $needle)) {
                $result[$code] = $def['label'] ?? $code;
            }
        }

        // Search in aliases
        foreach (self::$aliases as $alias => $canonical) {
            if (\str_contains($alias, $needle) && !isset($result[$canonical])) {
                $def = self::$games[$canonical] ?? null;
                $result[$canonical] = $def['label'] ?? $canonical;
            }
        }

        return $result;
    }

    /**
     * Resolve a server name to its code using the serverMap.
     */
    public static function resolveServer(string $canonical, string $serverInput): string
    {
        $def = self::get($canonical);
        if (!$def || !isset($def['serverMap']) || !\is_array($def['serverMap'])) {
            return $serverInput;
        }

        $key = \mb_strtolower(\preg_replace('/\s+/', '', $serverInput) ?? $serverInput);

        return $def['serverMap'][$key] ?? $serverInput;
    }

    /**
     * Reset registry (mainly for testing).
     */
    public static function reset(): void
    {
        self::$games = [];
        self::$aliases = [];
        self::$initialized = false;
    }
}
