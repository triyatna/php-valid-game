<?php

declare(strict_types=1);

namespace Triyatna\PhpValidGame\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Triyatna\PhpValidGame\Registry\GameRegistry;

final class GameRegistryTest extends TestCase
{
    protected function setUp(): void
    {
        GameRegistry::reset();
    }

    public function testInitPopulatesGames(): void
    {
        GameRegistry::init();

        $this->assertNotEmpty(GameRegistry::allCodes());
        $this->assertContains('freefire', GameRegistry::allCodes());
        $this->assertContains('mobilelegends', GameRegistry::allCodes());
        $this->assertContains('valorant', GameRegistry::allCodes());
    }

    public function testResolveCanonicalDirectMatch(): void
    {
        $this->assertSame('freefire', GameRegistry::resolveCanonical('freefire'));
        $this->assertSame('mobilelegends', GameRegistry::resolveCanonical('mobilelegends'));
    }

    public function testResolveCanonicalAlias(): void
    {
        $this->assertSame('freefire', GameRegistry::resolveCanonical('ff'));
        $this->assertSame('mobilelegends', GameRegistry::resolveCanonical('ml'));
        $this->assertSame('mobilelegends', GameRegistry::resolveCanonical('mlbb'));
        $this->assertSame('cod', GameRegistry::resolveCanonical('codm'));
    }

    public function testResolveCanonicalHumanName(): void
    {
        $this->assertSame('freefire', GameRegistry::resolveCanonical('Free Fire'));
        $this->assertSame('mobilelegends', GameRegistry::resolveCanonical('Mobile Legends'));
        $this->assertSame('valorant', GameRegistry::resolveCanonical('VALORANT'));
    }

    public function testResolveCanonicalUnknownReturnsNull(): void
    {
        $this->assertNull(GameRegistry::resolveCanonical('nonexistentgame'));
    }

    public function testGetReturnsDefinition(): void
    {
        $def = GameRegistry::get('freefire');

        $this->assertNotNull($def);
        $this->assertSame('Free Fire', $def['label']);
        $this->assertFalse($def['requiresZone']);
        $this->assertSame('FREEFIRE', $def['gopayCode']);
    }

    public function testGetReturnsNullForUnknown(): void
    {
        $this->assertNull(GameRegistry::get('unknown'));
    }

    public function testRegisterCustomGame(): void
    {
        GameRegistry::register('customgame', [
            'label'        => 'Custom Game',
            'requiresZone' => false,
            'gopayCode'    => 'CUSTOM',
        ]);

        $this->assertSame('customgame', GameRegistry::resolveCanonical('customgame'));
        $this->assertNotNull(GameRegistry::get('customgame'));
    }

    public function testAliasRegistration(): void
    {
        GameRegistry::alias('myalias', 'freefire');
        $this->assertSame('freefire', GameRegistry::resolveCanonical('myalias'));
    }

    public function testResolveServer(): void
    {
        $this->assertSame('1', GameRegistry::resolveServer('azurlane', 'avrora'));
        $this->assertSame('2', GameRegistry::resolveServer('azurlane', 'lexington'));
        $this->assertSame('99', GameRegistry::resolveServer('azurlane', '99'));
    }

    public function testAllGamesReturnLabels(): void
    {
        $games = GameRegistry::allGames();

        $this->assertArrayHasKey('freefire', $games);
        $this->assertSame('Free Fire', $games['freefire']);
    }

    public function testRequiresZone(): void
    {
        $ml = GameRegistry::get('mobilelegends');
        $this->assertTrue($ml['requiresZone']);

        $ff = GameRegistry::get('freefire');
        $this->assertFalse($ff['requiresZone']);
    }

    // ── New helper tests ──

    public function testHasProviderCodashop(): void
    {
        $this->assertTrue(GameRegistry::hasProvider('freefire', 'codashop'));
        $this->assertFalse(GameRegistry::hasProvider('pubg', 'codashop')); // GoPay-only
    }

    public function testHasProviderGopay(): void
    {
        $this->assertTrue(GameRegistry::hasProvider('freefire', 'gopaygames'));
        $this->assertTrue(GameRegistry::hasProvider('pubg', 'gopaygames'));
        $this->assertFalse(GameRegistry::hasProvider('hago', 'gopaygames')); // Codashop-only
    }

    public function testHasProviderUnknownGame(): void
    {
        $this->assertFalse(GameRegistry::hasProvider('nonexistent', 'codashop'));
    }

    public function testGamesForProvider(): void
    {
        $codashop = GameRegistry::gamesForProvider('codashop');
        $this->assertContains('freefire', $codashop);
        $this->assertContains('mobilelegends', $codashop);
        $this->assertNotContains('pubg', $codashop);

        $gopay = GameRegistry::gamesForProvider('gopaygames');
        $this->assertContains('freefire', $gopay);
        $this->assertContains('pubg', $gopay);
        $this->assertNotContains('hago', $gopay);
    }

    public function testAllAliases(): void
    {
        $aliases = GameRegistry::allAliases();
        $this->assertArrayHasKey('ff', $aliases);
        $this->assertSame('freefire', $aliases['ff']);
        $this->assertArrayHasKey('ml', $aliases);
    }

    public function testSearchByPartialName(): void
    {
        $results = GameRegistry::search('fire');
        $this->assertArrayHasKey('freefire', $results);
        $this->assertSame('Free Fire', $results['freefire']);
    }

    public function testSearchByAlias(): void
    {
        $results = GameRegistry::search('mlbb');
        $this->assertArrayHasKey('mobilelegends', $results);
    }

    public function testSearchReturnsEmptyForNoMatch(): void
    {
        $results = GameRegistry::search('zzzznonexistent');
        $this->assertEmpty($results);
    }

    public function testNewGamesExist(): void
    {
        $this->assertNotNull(GameRegistry::get('pubg'));
        $this->assertNotNull(GameRegistry::get('hok'));
        $this->assertNotNull(GameRegistry::get('fcmobile'));
        $this->assertNotNull(GameRegistry::get('magicchessgogo'));
    }

    public function testNewGamesAliases(): void
    {
        $this->assertSame('pubg', GameRegistry::resolveCanonical('pubgmobile'));
        $this->assertSame('pubg', GameRegistry::resolveCanonical('pubgm'));
        $this->assertSame('hok', GameRegistry::resolveCanonical('honorofkings'));
        $this->assertSame('fcmobile', GameRegistry::resolveCanonical('fcm'));
        $this->assertSame('magicchessgogo', GameRegistry::resolveCanonical('magicchess'));
    }

    public function testGopayOnlyGamesHaveNullCodashop(): void
    {
        $pubg = GameRegistry::get('pubg');
        $this->assertNull($pubg['codashop']);
        $this->assertSame('PUBG_ID', $pubg['gopayCode']);

        $hok = GameRegistry::get('hok');
        $this->assertNull($hok['codashop']);
        $this->assertSame('HOK', $hok['gopayCode']);
    }

    // ── listGames() tests ──

    public function testListGamesReturnsAllGames(): void
    {
        $list = GameRegistry::listGames();

        $this->assertNotEmpty($list);
        $this->assertCount(\count(GameRegistry::allCodes()), $list);
    }

    public function testListGamesEntryStructure(): void
    {
        $list = GameRegistry::listGames();
        $first = $list[0];

        $this->assertArrayHasKey('code', $first);
        $this->assertArrayHasKey('label', $first);
        $this->assertArrayHasKey('requiresZone', $first);
        $this->assertArrayHasKey('providers', $first);
        $this->assertArrayHasKey('aliases', $first);
        $this->assertArrayHasKey('servers', $first);
        $this->assertIsBool($first['requiresZone']);
        $this->assertIsArray($first['providers']);
        $this->assertIsArray($first['aliases']);
        $this->assertIsArray($first['servers']);
    }

    public function testListGamesContainsFreefire(): void
    {
        $list = GameRegistry::listGames();

        $ff = null;
        foreach ($list as $entry) {
            if ($entry['code'] === 'freefire') {
                $ff = $entry;
                break;
            }
        }

        $this->assertNotNull($ff);
        $this->assertSame('Free Fire', $ff['label']);
        $this->assertFalse($ff['requiresZone']);
        $this->assertContains('codashop', $ff['providers']);
        $this->assertContains('gopaygames', $ff['providers']);
        $this->assertContains('ff', $ff['aliases']);
        $this->assertContains('garena', $ff['aliases']);
        $this->assertEmpty($ff['servers']);
    }

    public function testListGamesGopayOnlyHasNoCodeshop(): void
    {
        $list = GameRegistry::listGames();

        $pubg = null;
        foreach ($list as $entry) {
            if ($entry['code'] === 'pubg') {
                $pubg = $entry;
                break;
            }
        }

        $this->assertNotNull($pubg);
        $this->assertSame('PUBG Mobile', $pubg['label']);
        $this->assertNotContains('codashop', $pubg['providers']);
        $this->assertContains('gopaygames', $pubg['providers']);
    }

    public function testListGamesIncludesServers(): void
    {
        $list = GameRegistry::listGames();

        $azur = null;
        foreach ($list as $entry) {
            if ($entry['code'] === 'azurlane') {
                $azur = $entry;
                break;
            }
        }

        $this->assertNotNull($azur);
        $this->assertTrue($azur['requiresZone']);
        $this->assertNotEmpty($azur['servers']);
        $this->assertContains('avrora', $azur['servers']);
    }
}
