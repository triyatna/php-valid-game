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
        $this->assertSame(200, $result->httpStatus);
        $this->assertNotNull($result->timestamp);
    }

    public function testToArrayContainsAllFields(): void
    {
        $result = ValidationResult::make(
            status: false,
            code: StatusCode::API_ERROR,
            message: 'Invalid user ID',
            game: 'freefire',
            userId: 'bad',
        );

        $arr = $result->toArray();

        $this->assertArrayHasKey('status', $arr);
        $this->assertArrayHasKey('code', $arr);
        $this->assertArrayHasKey('message', $arr);
        $this->assertArrayHasKey('game', $arr);
        $this->assertArrayHasKey('userId', $arr);
        $this->assertArrayHasKey('zoneId', $arr);
        $this->assertArrayHasKey('nickname', $arr);
        $this->assertArrayHasKey('provider', $arr);
        $this->assertArrayHasKey('httpStatus', $arr);
        $this->assertArrayHasKey('timestamp', $arr);
        $this->assertArrayHasKey('meta', $arr);
        $this->assertFalse($arr['status']);
        $this->assertSame('API_ERROR', $arr['code']);
    }

    public function testToJsonReturnsValidJson(): void
    {
        $result = ValidationResult::make(
            status: true,
            code: StatusCode::OK,
            message: 'OK',
        );

        $json = $result->toJson();
        $decoded = json_decode($json, true);

        $this->assertNotNull($decoded);
        $this->assertSame(true, $decoded['status']);
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
}
