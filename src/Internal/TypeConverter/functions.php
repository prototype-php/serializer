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

use Kafkiansky\Prototype\Exception\TypeIsNotSupported;
use Kafkiansky\Prototype\Internal\Reflection\Direction;
use Kafkiansky\Prototype\Internal\Wire\DeserializeDateIntervalProperty;
use Kafkiansky\Prototype\Internal\Wire\DeserializeDateTimeProperty;
use Kafkiansky\Prototype\Internal\Wire\DeserializeEnumProperty;
use Kafkiansky\Prototype\Internal\Wire\DeserializeObjectProperty;
use Kafkiansky\Prototype\Internal\Wire\PropertyDeserializer;
use Kafkiansky\Prototype\Internal\Wire\PropertySerializer;
use Kafkiansky\Prototype\Internal\Wire\SerializeDateIntervalProperty;
use Kafkiansky\Prototype\Internal\Wire\SerializeDateTimeProperty;
use Kafkiansky\Prototype\Internal\Wire\SerializeEnumProperty;
use Kafkiansky\Prototype\Internal\Wire\SerializeObjectProperty;
use Kafkiansky\Prototype\PrototypeException;

/**
 * @internal
 * @psalm-internal Kafkiansky\Prototype
 * @param non-empty-string $type
 * @param Direction $direction
 * @psalm-return ($direction is Direction::DESERIALIZE ? PropertyDeserializer : PropertySerializer)
 * @throws PrototypeException
 */
function typeStringToPropertyMarshaller(string $type, Direction $direction): PropertySerializer|PropertyDeserializer
{
    /** @psalm-suppress ArgumentTypeCoercion */
    return match (true) {
        enum_exists($type) => match ($direction) {
            Direction::DESERIALIZE => new DeserializeEnumProperty($type),
            Direction::SERIALIZE => new SerializeEnumProperty(),
        },
        instanceOfDateTime($type) => match ($direction) {
            Direction::DESERIALIZE => new DeserializeDateTimeProperty($type),
            Direction::SERIALIZE => new SerializeDateTimeProperty(),
        },
        isClassOf($type, \DateInterval::class) => match ($direction) {
            Direction::DESERIALIZE => new DeserializeDateIntervalProperty(),
            Direction::SERIALIZE => new SerializeDateIntervalProperty(),
        },
        class_exists($type) => match ($direction) {
            Direction::DESERIALIZE => new DeserializeObjectProperty($type),
            Direction::SERIALIZE => new SerializeObjectProperty(),
        },
        default => throw new TypeIsNotSupported($type),
    };
}

/**
 * @template T of object
 * @param class-string<T>|string $class
 * @param class-string<T> ...$of
 * @psalm-assert-if-true class-string<T> $class
 */
function isClassOf(string $class, string ...$of): bool
{
    if (class_exists($class) || interface_exists($class)) {
        foreach ($of as $it) {
            if (is_a($class, $it, allow_string: true)) {
                return true;
            }
        }
    } elseif (\count($implements = class_implements($class)) > 0) {
        foreach ($of as $it) {
            if (\in_array($it, $implements, strict: true)) {
                return true;
            }
        }
    }

    return false;
}

/**
 * @param string|class-string $class
 * @psalm-assert-if-true class-string<\DateTimeImmutable|\DateTime|\DateTimeInterface> $class
 */
function instanceOfDateTime(string $class): bool
{
    return isClassOf($class, \DateTimeInterface::class, \DateTimeImmutable::class, \DateTime::class);
}
