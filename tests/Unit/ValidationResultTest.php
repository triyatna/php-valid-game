<?php

declare(strict_types=1);

namespace Triyatna\PhpValidGame\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Triyatna\PhpValidGame\DTO\ValidationResult;
use Triyatna\PhpValidGame\Enums\StatusCode;

final class ValidationResultTest extends TestCase
{
    public function testMakeCreatesResult(): void
    {
        $result = ValidationResult::make(
            status: true,
            code: StatusCode::OK,
            message: 'Success',
            game: 'freefire',
            userId: '123',
            nickname: 'PlayerName',
            provider: 'codashop',
            httpStatus: 200,
        );

        $this->assertTrue($result->isValid());
        $this->assertSame(StatusCode::OK, $result->code);
        $this->assertSame('freefire', $result->game);
        $this->assertSame('123', $result->userId);
        $this->assertSame('PlayerName', $result->nickname);
        $this->assertSame('codashop', $result->provider);
        $this->assertSame('OK', $result->getCode());
        $this->assertSame('Success', $result->getMessage());
    }

    public function testToArrayContainsOnlyEssentialFields(): void
    {
        $result = ValidationResult::make(
            status: false,
            code: StatusCode::API_ERROR,
            message: 'Invalid user ID',
            game: 'freefire',
            userId: 'bad',
            httpStatus: 400,
            meta: ['debug' => 'data'],
        );

        $arr = $result->toArray();

        // Check top-level structure
        $this->assertArrayHasKey('status', $arr);
        $this->assertArrayHasKey('message', $arr);
        $this->assertArrayHasKey('data', $arr);

        // Check data object structure
        $this->assertArrayHasKey('game', $arr['data']);
        $this->assertArrayHasKey('nickname', $arr['data']);
        $this->assertArrayHasKey('country', $arr['data']);

        // Internal fields should NOT be in toArray()
        $this->assertArrayNotHasKey('code', $arr);
        $this->assertArrayNotHasKey('userId', $arr);
        $this->assertArrayNotHasKey('zoneId', $arr);
        $this->assertArrayNotHasKey('provider', $arr);
        $this->assertArrayNotHasKey('httpStatus', $arr);
        $this->assertArrayNotHasKey('timestamp', $arr);
        $this->assertArrayNotHasKey('meta', $arr);

        $this->assertFalse($arr['status']);
        $this->assertSame('Invalid user ID', $arr['message']);
        $this->assertSame('Free Fire', $arr['data']['game']);
        $this->assertSame('', $arr['data']['nickname']); // Empty for failed validation
        $this->assertSame('', $arr['data']['country']);
    }

    public function testDebugReturnsInternalFields(): void
    {
        $result = ValidationResult::make(
            status: true,
            code: StatusCode::OK,
            message: 'Success',
            httpStatus: 200,
            meta: ['raw' => 'response'],
        );

        $debug = $result->debug();

        $this->assertArrayHasKey('httpStatus', $debug);
        $this->assertArrayHasKey('timestamp', $debug);
        $this->assertArrayHasKey('meta', $debug);
        $this->assertSame(200, $debug['httpStatus']);
        $this->assertSame(['raw' => 'response'], $debug['meta']);
        $this->assertIsString($debug['timestamp']);
    }

    public function testToJsonReturnsValidJson(): void
    {
        $result = ValidationResult::make(
            status: true,
            code: StatusCode::OK,
            message: 'OK',
            game: 'freefire',
            nickname: 'PlayerName',
        );

        $json = $result->toJson();
        $decoded = json_decode($json, true);

        $this->assertNotNull($decoded);
        $this->assertSame(true, $decoded['status']);
        $this->assertSame('User ID is valid.', $decoded['message']);
        $this->assertArrayHasKey('data', $decoded);
        $this->assertSame('Free Fire', $decoded['data']['game']);
        $this->assertSame('PlayerName', $decoded['data']['nickname']);
        $this->assertSame('', $decoded['data']['country']);
    }

    public function testIsValidReturnsFalseOnError(): void
    {
        $result = ValidationResult::make(
            status: false,
            code: StatusCode::INVALID_INPUT,
            message: 'Missing user ID',
        );

        $this->assertFalse($result->isValid());
    }

    public function testMessageDefaultsToCodeValue(): void
    {
        $result = ValidationResult::make(
            status: false,
            code: StatusCode::UNKNOWN_GAME,
        );

        $this->assertSame('UNKNOWN_GAME', $result->message);
        $this->assertSame('UNKNOWN_GAME', $result->getMessage());
    }
}
