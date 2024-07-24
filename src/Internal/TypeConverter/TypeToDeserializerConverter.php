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

namespace Kafkiansky\Prototype\Internal\TypeConverter;

use Kafkiansky\Prototype\Internal\Reflection\Direction;
use Kafkiansky\Prototype\Internal\Wire\BoolType;
use Kafkiansky\Prototype\Internal\Wire\DeserializeScalarProperty;
use Kafkiansky\Prototype\Internal\Wire\DoubleType;
use Kafkiansky\Prototype\Internal\Wire\FixedInt32Type;
use Kafkiansky\Prototype\Internal\Wire\FixedInt64Type;
use Kafkiansky\Prototype\Internal\Wire\FixedUint32Type;
use Kafkiansky\Prototype\Internal\Wire\FixedUint64Type;
use Kafkiansky\Prototype\Internal\Wire\FloatType;
use Kafkiansky\Prototype\Internal\Wire\PropertyDeserializer;
use Kafkiansky\Prototype\Internal\Wire\StringType;
use Kafkiansky\Prototype\Internal\Wire\VarintType;
use Kafkiansky\Prototype\Internal\Wire\VaruintType;
use Kafkiansky\Prototype\PrototypeException;
use Kafkiansky\Prototype\Type;

/**
 * @internal
 * @psalm-internal Kafkiansky\Prototype
 * @pure
 */
final class TypeToDeserializerConverter
{
    /**
     * @psalm-param class-string|enum-string|interface-string $type
     * @throws PrototypeException
     */
    public function typeStringToPropertyDeserializer(string $type): PropertyDeserializer
    {
        return typeStringToPropertyMarshaller($type, Direction::DESERIALIZE);
    }

    public function protobufTypeToPropertyDeserializer(Type $type): PropertyDeserializer
    {
        return new DeserializeScalarProperty(
            match ($type) {
                Type::bool => new BoolType(),
                Type::string => new StringType(),
                Type::float => new FloatType(),
                Type::double => new DoubleType(),
                Type::int32, Type::int64, Type::uint32, Type::uint64 => new VaruintType(),
                Type::sint32, Type::sint64 => new VarintType(),
                Type::fixed32 => new FixedUint32Type(),
                Type::fixed64 => new FixedUint64Type(),
                Type::sfixed32 => new FixedInt32Type(),
                Type::sfixed64 => new FixedInt64Type(),
            },
        );
    }
}
