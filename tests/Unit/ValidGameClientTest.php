<?php

declare(strict_types=1);

namespace Triyatna\PhpValidGame\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Triyatna\PhpValidGame\Enums\StatusCode;
use Triyatna\PhpValidGame\Registry\GameRegistry;
use Triyatna\PhpValidGame\ValidGameClient;

final class ValidGameClientTest extends TestCase
{
    protected function setUp(): void
    {
        GameRegistry::reset();
    }

    public function testCheckRequiresUserId(): void
    {
        $client = new ValidGameClient();
        $result = $client->check('freefire', null);

        $this->assertFalse($result->isValid());
        $this->assertSame(StatusCode::INVALID_INPUT, $result->code);
        $this->assertStringContainsString('required', $result->message);
    }

    public function testCheckRequiresEmptyUserId(): void
    {
        $client = new ValidGameClient();
        $result = $client->check('freefire', '');

        $this->assertFalse($result->isValid());
        $this->assertSame(StatusCode::INVALID_INPUT, $result->code);
    }

    public function testCheckUnknownGame(): void
    {
        $client = new ValidGameClient();
        $result = $client->check('nonexistentgame123', '12345');

        $this->assertFalse($result->isValid());
        $this->assertSame(StatusCode::UNKNOWN_GAME, $result->code);
    }

    public function testCheckRequiresZoneForMobileLegends(): void
    {
        $client = new ValidGameClient();
        $result = $client->check('mobilelegends', '12345');

        $this->assertFalse($result->isValid());
        $this->assertSame(StatusCode::INVALID_INPUT, $result->code);
        $this->assertStringContainsString('Zone', $result->message);
    }

    public function testCheckAliasResolution(): void
    {
        $client = new ValidGameClient();

        // "ff" alias → requires network, so we just test it doesn't return UNKNOWN_GAME
        $result = $client->check('ff', '123456');
        $this->assertNotSame(StatusCode::UNKNOWN_GAME, $result->code);
        $this->assertSame('freefire', $result->game);
    }

    public function testSupportedGamesIsNotEmpty(): void
    {
        $client = new ValidGameClient();
        $games = $client->supportedGames();

        $this->assertNotEmpty($games);
        $this->assertContains('freefire', $games);
        $this->assertContains('mobilelegends', $games);
    }

    public function testSupportedGamesWithLabels(): void
    {
        $client = new ValidGameClient();
        $games = $client->supportedGamesWithLabels();

        $this->assertArrayHasKey('freefire', $games);
        $this->assertSame('Free Fire', $games['freefire']);
    }

    // ── __call magic method tests ──

    public function testMagicCallResolvesFreeFire(): void
    {
        $client = new ValidGameClient();

        // freefire() → resolves via __call to check('freefire', ...)
        $result = $client->freefire('123456');
        $this->assertSame('freefire', $result->game);
        $this->assertNotSame(StatusCode::UNKNOWN_GAME, $result->code);
    }

    public function testMagicCallResolvesCamelCase(): void
    {
        $client = new ValidGameClient();

        // mobileLegends() → resolves to 'mobilelegends', requires zone
        $result = $client->mobileLegends('123456');
        $this->assertSame('mobilelegends', $result->game);
        $this->assertSame(StatusCode::INVALID_INPUT, $result->code);
    }

    public function testMagicCallReturnsUnknownForBadMethod(): void
    {
        $client = new ValidGameClient();

        $result = $client->nonExistentGame123('12345');
        $this->assertFalse($result->isValid());
        $this->assertSame(StatusCode::UNKNOWN_GAME, $result->code);
    }

    public function testMagicCallForNewGames(): void
    {
        $client = new ValidGameClient();

        // pubg (GoPay-only game)
        $result = $client->pubg('123456');
        $this->assertSame('pubg', $result->game);
        $this->assertNotSame(StatusCode::UNKNOWN_GAME, $result->code);

        // hok (Honor of Kings)
        $result = $client->hok('123456');
        $this->assertSame('hok', $result->game);
        $this->assertNotSame(StatusCode::UNKNOWN_GAME, $result->code);

        // fcmobile (FC Mobile)
        $result = $client->fcmobile('123456');
        $this->assertSame('fcmobile', $result->game);
        $this->assertNotSame(StatusCode::UNKNOWN_GAME, $result->code);
    }

    // ── New convenience methods ──

    public function testGamesForProvider(): void
    {
        $client = new ValidGameClient();

        $codashopGames = $client->gamesForProvider('codashop');
        $this->assertContains('freefire', $codashopGames);
        $this->assertContains('mobilelegends', $codashopGames);
        $this->assertNotContains('pubg', $codashopGames); // GoPay-only

        $gopayGames = $client->gamesForProvider('gopaygames');
        $this->assertContains('freefire', $gopayGames);
        $this->assertContains('pubg', $gopayGames);
        $this->assertNotContains('hago', $gopayGames); // Codashop-only
    }

    public function testSearchGames(): void
    {
        $client = new ValidGameClient();

        $results = $client->searchGames('fire');
        $this->assertArrayHasKey('freefire', $results);
    }

    public function testListGames(): void
    {
        $client = new ValidGameClient();
        $list = $client->listGames();

        $this->assertNotEmpty($list);
        $this->assertArrayHasKey('code', $list[0]);
        $this->assertArrayHasKey('label', $list[0]);
        $this->assertArrayHasKey('providers', $list[0]);
        $this->assertArrayHasKey('aliases', $list[0]);

        $results = $client->searchGames('mobile');
        $this->assertArrayHasKey('mobilelegends', $results);
        $this->assertArrayHasKey('fcmobile', $results);
    }
}
