<?php

declare(strict_types=1);

namespace Triyatna\PhpValidGame\Registry;

/**
 * Central registry of supported games (canonical codes, aliases, payload blueprints, and server mappings).
 *
 * You can extend/override at runtime via register()/alias().
 */
final class GameRegistry
{
    /** @var array<string,array<string,mixed>> */
    private static array $games = [];

    /** @var array<string,string> alias => canonical */
    private static array $aliases = [];

    /**
     * Bootstrap with built-in games (converted from your service).
     * Canonical codes are simple: e.g. freefire, mobilelegends, aov, valorant, etc.
     */
    public static function init(): void
    {
        if (!empty(self::$games)) {
            return;
        }

        // Helpers
        $pp = fn(string|int $id, string|int $price, int $variablePrice = 0): array => [
            'voucherPricePoint.id' => (string)$id,
            'voucherPricePoint.price' => (string)$price,
            'voucherPricePoint.variablePrice' => $variablePrice,
        ];

        // Register (canonical => definition)
        self::$games = [
            '8ballpool' => [
                'typeName' => 'EIGHT_BALL_POOL',
                'requiresServer' => false,
                'payload' => function (string|int $uid, string|int|null $server) use ($pp): array {
                    return $pp(272564, '14000.0000') + [
                        'user.userId' => $uid,
                        'user.zoneId' => null,
                        'voucherTypeName' => 'EIGHT_BALL_POOL',
                        'shopLang' => 'id_ID',
                    ];
                },
                'nicknameFrom' => ['confirmationFields.roles.0.role', 'confirmationFields.username']
            ],

            'aethergazer' => [
                'typeName' => 'AETHER_GAZER',
                'requiresServer' => false,
                'payload' => function (string|int $uid, string|int|null $server) use ($pp): array {
                    return $pp('2', '16650.0') + [
                        'user.userId' => $uid,
                        'user.zoneId' => null,
                        'voucherTypeName' => '547-AETHER_GAZER',
                        'voucherTypeId' => '524',
                        'gvtId' => '691',
                        'lvtId' => '11840',
                        'pcId' => '906',
                        'shopLang' => 'id_ID',
                        // Tokens from your source service (may expire; keep here to preserve behavior)
                        'dynamicSkuToken' => 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJkeW5hbWljU2t1SW5mbyI6IntcInNrdUlkXCI6XCJjb20ueW9zdGFyLmFldGhlcmdhemVyLnNoaWZ0aW5nZmxvd2VyMVwiLFwiZXZlbnRQYWNrYWdlXCI6XCIwXCIsXCJkZW5vbUltYWdlVXJsXCI6XCJodHRwczovL2NkbjEuY29kYXNob3AuY29tL2ltYWdlcy81NDdfM2QyMTBiNzUtNTJkYi00YjUxLTgzMGYtZDYxMTFiNjFkNDQ5X0FFVEhFUiBHQVpFUl9pbWFnZS9Db2RhX0FHX1NLVWltYWdlcy82MC5wbmdcIixcImRlbm9tTmFtZVwiOlwiNjAgU2hpZnRpbmcgRmxvd2Vyc1wiLFwiZGVub21DYXRlZ29yeU5hbWVcIjpcIlNoaWZ0aW5nIEZsb3dlcnNcIixcInRhZ3NcIjpbXSxcImNvdW50cnkyTmFtZVwiOlwiSURcIixcImx2dElkXCI6MTE4NDAsXCJhZGRpdGlvbmFsSW5mb1wiOntcIkR5bmFtaWNTa3VQcm9tb0RldGFpbFwiOlwibnVsbFwifX0ifQ.eKiPyHwGZJUuUGGzwWiPiDuF6xC5G7_PWLn6TXVAKVs',
                        'pricePointDynamicSkuToken' => 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJkeW5hbWljU2t1SW5mbyI6IntcInBjSWRcIjo5MDYsXCJwcmljZVwiOjE2NjUwLjAsXCJjdXJyZW5jeVwiOlwiSURSXCIsXCJhcGlQcmljZVwiOjE2NjUwLjAsXCJhcGlQcmljZUN1cnJlbmN5XCI6XCJJRFJcIixcImRpc2NvdW50UHJpY2VcIjoxNjY1MC4wLFwicHJpY2VCZWZvcmVUYXhcIjoxNTAwMC4wLFwidGF4QW1vdW50XCI6MTY1MC4wLFwic2t1SWRcIjpcImNvbS55b3N0YXIuYWV0aGVyZ2F6ZXIuc2hpZnRpbmdmbG93ZXIxXCIsXCJsdnRJZFwiOjExODQwfSJ9.y89THkVNztOzAXS64nr9Rtamn3wbWLIYXeRWrZ9yMBc',
                    ];
                },
                'nicknameFrom' => ['confirmationFields.username', 'confirmationFields.roles.0.role']
            ],

            'aov' => [
                'typeName' => 'ARENA_OF_VALOR',
                'requiresServer' => false,
                'payload' => function (string|int $uid, string|int|null $server) use ($pp): array {
                    return $pp('270294', '10000.0000') + [
                        'user.userId' => $uid,
                        'user.zoneId' => null,
                        'voucherTypeName' => 'AOV',
                        'shopLang' => 'id_ID',
                    ];
                },
                'serverFrom' => 'confirmationFields.roles.0.server',
                'nicknameFrom' => ['confirmationFields.roles.0.role', 'confirmationFields.username']
            ],

            'autochess' => [
                'typeName' => 'AUTO_CHESS',
                'requiresServer' => false,
                'payload' => fn(string|int $uid, string|int|null $server) => [
                    'voucherPricePoint.id' => '203879',
                    'voucherPricePoint.price' => '150000.0000',
                    'voucherPricePoint.variablePrice' => '0',
                    'user.userId' => $uid,
                    'voucherTypeName' => 'AUTO_CHESS',
                    'shopLang' => 'id_ID',
                ],
                'nicknameFrom' => ['confirmationFields.username']
            ],

            'azurlane' => [
                'typeName' => 'AZUR_LANE',
                'requiresServer' => true,
                'serverMap' => [
                    'avrora' => '1',
                    'lexington' => '2',
                    'sandy' => '3',
                    'washington' => '4',
                    'amagi' => '5',
                    'littleenterprise' => '6',
                ],
                'payload' => function (string|int $uid, string|int|null $server) use ($pp): array {
                    $sid = \is_string($server) ? $server : (string)$server;
                    return $pp('99665', '70000.0000') + [
                        'user.userId' => $uid,
                        'user.zoneId' => $sid,
                        'voucherTypeName' => 'AZUR_LANE',
                        'shopLang' => 'id_ID',
                    ];
                },
                'nicknameFrom' => ['confirmationFields.username']
            ],

            'badlanders' => [
                'typeName' => 'BAD_LANDERS',
                'requiresServer' => true,
                'serverMap' => [
                    'global' => '11001',
                    'jf' => '21004',
                ],
                'payload' => function (string|int $uid, string|int|null $server): array {
                    $sid = \is_string($server) ? $server : (string)$server;
                    return [
                        'voucherPricePoint.id' => '333121',
                        'voucherPricePoint.price' => '2300.0000',
                        'voucherPricePoint.variablePrice' => '0',
                        'user.userId' => $uid,
                        'user.zoneId' => $sid,
                        'voucherTypeName' => 'BAD_LANDERS',
                        'shopLang' => 'id_ID',
                    ];
                },
                'nicknameFrom' => ['confirmationFields.username']
            ],

            'barbarq' => [
                'typeName' => 'BARBARQ',
                'requiresServer' => false,
                'payload' => fn(string|int $uid, string|int|null $server) => [
                    'voucherPricePoint.id' => '5145',
                    'voucherPricePoint.price' => '120000.0000',
                    'voucherPricePoint.variablePrice' => '0',
                    'user.userId' => $uid,
                    'voucherTypeName' => 'ELECSOUL',
                    'shopLang' => 'id_ID',
                ],
                'nicknameFrom' => ['confirmationFields.apiResult']
            ],

            'basketrio' => [
                'typeName' => 'BASKETRIO',
                'requiresServer' => true,
                'serverMap' => [
                    'buzzerbeater' => '2',
                    '001' => '3',
                    '002' => '4',
                ],
                'payload' => fn(string|int $uid, string|int|null $server) => [
                    'voucherPricePoint.id' => '147203',
                    'voucherPricePoint.price' => '832500.0000',
                    'voucherPricePoint.variablePrice' => '0',
                    'user.userId' => $uid,
                    'user.zoneId' => (string)$server,
                    'voucherTypeName' => 'BASKETRIO',
                    'shopLang' => 'id_ID',
                ],
                'nicknameFrom' => ['confirmationFields.username']
            ],

            'cod' => [
                'typeName' => 'CALL_OF_DUTY',
                'requiresServer' => false,
                'payload' => fn(string|int $uid, string|int|null $server) => [
                    'voucherPricePoint.id' => '270251',
                    'voucherPricePoint.price' => '20000.0000',
                    'voucherPricePoint.variablePrice' => '0',
                    'user.userId' => $uid,
                    'user.zoneId' => null,
                    'voucherTypeName' => 'CALL_OF_DUTY',
                    'shopLang' => 'id_ID',
                ],
                'nicknameFrom' => ['confirmationFields.roles.0.role', 'confirmationFields.username']
            ],

            'dragoncity' => [
                'typeName' => 'DRAGON_CITY',
                'requiresServer' => false,
                'payload' => fn(string|int $uid, string|int|null $server) => [
                    'voucherPricePoint.id' => '254206',
                    'voucherPricePoint.price' => '65000.0000',
                    'voucherPricePoint.variablePrice' => '0',
                    'user.userId' => $uid,
                    'user.zoneId' => null,
                    'voucherTypeName' => 'DRAGON_CITY',
                    'shopLang' => 'id_ID',
                ],
                'nicknameFrom' => ['confirmationFields.username']
            ],

            'freefire' => [
                'typeName' => 'FREE_FIRE',
                'requiresServer' => false,
                'payload' => fn(string|int $uid, string|int|null $server) => [
                    'voucherPricePoint.id' => '270288',
                    'voucherPricePoint.price' => '200000.0000',
                    'voucherPricePoint.variablePrice' => '0',
                    'user.userId' => $uid,
                    'user.zoneId' => null,
                    'voucherTypeName' => 'FREEFIRE',
                    'shopLang' => 'id_ID',
                ],
                'nicknameFrom' => ['confirmationFields.roles.0.role', 'confirmationFields.username']
            ],

            'hago' => [
                'typeName' => 'HAGO',
                'requiresServer' => false,
                'payload' => fn(string|int $uid, string|int|null $server) => [
                    'voucherPricePoint.id' => '272113',
                    'voucherPricePoint.price' => '29700.0000',
                    'voucherPricePoint.variablePrice' => '0',
                    'user.userId' => $uid,
                    'user.zoneId' => null,
                    'voucherTypeName' => 'HAGO',
                    'shopLang' => 'id_ID',
                ],
                'nicknameFrom' => ['confirmationFields.username']
            ],

            'mobilelegends' => [
                'typeName' => 'MOBILE_LEGENDS',
                'requiresServer' => true,
                'payload' => fn(string|int $uid, string|int|null $server) => [
                    'voucherPricePoint.id' => '5199',
                    'voucherPricePoint.price' => '68543.0000',
                    'voucherPricePoint.variablePrice' => '0',
                    'user.userId' => $uid,
                    'user.zoneId' => (string)$server,
                    'voucherTypeName' => 'MOBILE_LEGENDS',
                    'shopLang' => 'id_ID',
                ],
                'nicknameFrom' => ['confirmationFields.username']
            ],

            'pb' => [
                'typeName' => 'POINT_BLANK',
                'requiresServer' => false,
                'payload' => fn(string|int $uid, string|int|null $server) => [
                    'voucherPricePoint.id' => '344845',
                    'voucherPricePoint.price' => '11000.0000',
                    'voucherPricePoint.variablePrice' => '0',
                    'user.userId' => $uid,
                    'user.zoneId' => '0',
                    'voucherTypeName' => 'POINT_BLANK',
                    'shopLang' => 'id_ID',
                ],
                'nicknameFrom' => ['confirmationFields.username']
            ],

            'valorant' => [
                'typeName' => 'VALORANT',
                'requiresServer' => false,
                'payload' => fn(string|int $uid, string|int|null $server) => [
                    'voucherPricePoint.id' => '950525',
                    'voucherPricePoint.price' => '75000.0000',
                    'voucherPricePoint.variablePrice' => '0',
                    'userVariablePrice' => '0',
                    'user.userId' => $uid,
                    'user.zoneId' => '0',
                    'voucherTypeName' => 'VALORANT',
                    'shopLang' => 'id_ID',
                ],
                'nicknameFrom' => ['confirmationFields.username']
            ],
        ];

        // self::$aliases = [
        //     'eightballpool' => '8ballpool',
        //     'aov' => 'aov',
        //     'arenaofvalor' => 'aov',
        //     'ml' => 'mobilelegends',
        //     'mobilelegend' => 'mobilelegends',
        //     'pointblank' => 'pb',
        //     'ff' => 'freefire',
        //     'codm' => 'cod',
        //     'callofduty' => 'cod',
        // ];
    }

    public static function resolveCanonical(string $input): ?string
    {
        $key = \mb_strtolower(\preg_replace('/[^a-z0-9]+/i', '', $input) ?? $input);
        return self::$games[$key] ? $key : (self::$aliases[$key] ?? (self::$games[$key] ?? null));
    }

    /** @return array<string,mixed>|null */
    public static function def(string $canonical): ?array
    {
        return self::$games[$canonical] ?? null;
    }

    /** @param array<string,mixed> $definition */
    public static function register(string $canonical, array $definition): void
    {
        self::$games[$canonical] = $definition;
    }

    // public static function alias(string $alias, string $canonical): void
    // {
    //     self::$aliases[\mb_strtolower($alias)] = $canonical;
    // }
}
