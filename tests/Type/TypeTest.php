<?php

/**
 * MIT License
 * Copyright (c) 2024 kafkiansky.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

declare(strict_types=1);

namespace Kafkiansky\Prototype\Tests\Type;

use Kafkiansky\Binary\Buffer;
use Kafkiansky\Binary\Endianness;
use Kafkiansky\Prototype\Internal\Wire\TypeReader;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(\Kafkiansky\Prototype\Internal\Wire\BoolType::class)]
#[CoversClass(\Kafkiansky\Prototype\Internal\Wire\FloatType::class)]
#[CoversClass(\Kafkiansky\Prototype\Internal\Wire\DoubleType::class)]
#[CoversClass(\Kafkiansky\Prototype\Internal\Wire\FixedInt32Type::class)]
#[CoversClass(\Kafkiansky\Prototype\Internal\Wire\FixedUint32Type::class)]
#[CoversClass(\Kafkiansky\Prototype\Internal\Wire\FixedInt64Type::class)]
#[CoversClass(\Kafkiansky\Prototype\Internal\Wire\FixedUint64Type::class)]
#[CoversClass(\Kafkiansky\Prototype\Internal\Wire\StringType::class)]
#[CoversClass(\Kafkiansky\Prototype\Internal\Wire\VarintType::class)]
#[CoversClass(\Kafkiansky\Prototype\Internal\Wire\VaruintType::class)]
final class TypeTest extends TestCase
{
    /**
     * @return iterable<array-key, array{callable(Buffer): void, \Kafkiansky\Prototype\Internal\Wire\TypeReader, mixed}>
     */
    public static function fixtures(): iterable
    {
        yield 'true' => [
            static function (Buffer $buffer): void {
                $buffer->writeVarUint(1);
            },
            new \Kafkiansky\Prototype\Internal\Wire\BoolType(),
            true,
        ];

        yield 'false' => [
            static function (Buffer $buffer): void {
                $buffer->writeVarUint(0);
            },
            new \Kafkiansky\Prototype\Internal\Wire\BoolType(),
            false,
        ];

        yield 'varuint' => [
            static function (Buffer $buffer): void {
                $buffer->writeVarUint(100);
            },
            new \Kafkiansky\Prototype\Internal\Wire\VaruintType(),
            100,
        ];

        yield 'varint' => [
            static function (Buffer $buffer): void {
                $buffer->writeVarInt(-1024);
            },
            new \Kafkiansky\Prototype\Internal\Wire\VarintType(),
            -1024,
        ];

        yield 'float' => [
            static function (Buffer $buffer): void {
                $buffer->writeFloat(2.5);
            },
            new \Kafkiansky\Prototype\Internal\Wire\FloatType(),
            2.5,
        ];

        yield 'double' => [
            static function (Buffer $buffer): void {
                $buffer->writeDouble(10.25);
            },
            new \Kafkiansky\Prototype\Internal\Wire\DoubleType(),
            10.25,
        ];

        yield 'string' => [
            static function (Buffer $buffer): void {
                $buffer->writeVarUint(\strlen('String'))->write('String');
            },
            new \Kafkiansky\Prototype\Internal\Wire\StringType(),
            'String',
        ];

        yield 'fixed32' => [
            static function (Buffer $buffer): void {
                $buffer->writeUint32(200);
            },
            new \Kafkiansky\Prototype\Internal\Wire\FixedUint32Type(),
            200,
        ];

        yield 'sfixed32' => [
            static function (Buffer $buffer): void {
                $buffer->writeInt32(-200);
            },
            new \Kafkiansky\Prototype\Internal\Wire\FixedInt32Type(),
            -200,
        ];

        yield 'fixed64' => [
            static function (Buffer $buffer): void {
                $buffer->writeUint64(2048);
            },
            new \Kafkiansky\Prototype\Internal\Wire\FixedUint64Type(),
            2048,
        ];

        yield 'sfixed64' => [
            static function (Buffer $buffer): void {
                $buffer->writeInt64(-2048);
            },
            new \Kafkiansky\Prototype\Internal\Wire\FixedInt64Type(),
            -2048,
        ];
    }

    /**
     * @template T
     * @param callable(Buffer): void $writeToBuffer
     * @param TypeReader<T> $type
     */
    #[DataProvider('fixtures')]
    public function testTypeRead(callable $writeToBuffer, TypeReader $type, mixed $value): void
    {
        $buffer = Buffer::empty(Endianness::little());
        $writeToBuffer($buffer);
        self::assertSame($value, $type->read($buffer));
        self::assertTrue($buffer->isEmpty());
    }
}
