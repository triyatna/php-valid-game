<?php

declare(strict_types=1);

namespace Triyatna\PhpValidGame\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Triyatna\PhpValidGame\Support\Normalizer;

final class NormalizerTest extends TestCase
{
    public function testCanonicalGame(): void
    {
        $this->assertSame('freefire', Normalizer::canonicalGame('Free Fire'));
        $this->assertSame('mobilelegends', Normalizer::canonicalGame('Mobile Legends'));
        $this->assertSame('valorant', Normalizer::canonicalGame('VALORANT'));
        $this->assertSame('8ballpool', Normalizer::canonicalGame('8 Ball Pool'));
        $this->assertSame('callofdutymobile', Normalizer::canonicalGame('Call of Duty Mobile'));
    }

    public function testCanonicalGameStripsSpecialChars(): void
    {
        $this->assertSame('mobilelegends', Normalizer::canonicalGame('mobile-legends'));
        $this->assertSame('freefire', Normalizer::canonicalGame('free_fire'));
        $this->assertSame('freefire', Normalizer::canonicalGame('  Free  Fire  '));
    }

    public function testDotGetSimple(): void
    {
        $data = ['name' => 'test', 'nested' => ['value' => 'hello']];

        $this->assertSame('test', Normalizer::dotGet($data, 'name'));
        $this->assertSame('hello', Normalizer::dotGet($data, 'nested.value'));
    }

    public function testDotGetWithNumericIndex(): void
    {
        $data = [
            'confirmationFields' => [
                'roles' => [
                    ['role' => 'PlayerName', 'server' => 'Asia'],
                ],
            ],
        ];

        $this->assertSame('PlayerName', Normalizer::dotGet($data, 'confirmationFields.roles.0.role'));
        $this->assertSame('Asia', Normalizer::dotGet($data, 'confirmationFields.roles.0.server'));
    }

    public function testDotGetReturnsNullForMissing(): void
    {
        $data = ['a' => 'b'];

        $this->assertNull(Normalizer::dotGet($data, 'nonexistent'));
        $this->assertNull(Normalizer::dotGet($data, 'a.b.c'));
    }
}
