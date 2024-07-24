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

namespace Kafkiansky\Prototype\Internal\Reflection;

use Kafkiansky\Binary\Buffer;
use Kafkiansky\Prototype\Internal\Wire\PropertySerializer;
use Kafkiansky\Prototype\Internal\Wire\Tag;
use Kafkiansky\Prototype\Internal\Wire\WireSerializer;
use Kafkiansky\Prototype\PrototypeException;
use Kafkiansky\Prototype\Internal\Wire\Type;

/**
 * @api
 * @internal
 * @template T
 * @psalm-internal Kafkiansky\Prototype
 */
final class PropertySerializeDescriptor
{
    /**
     * @param PropertySerializer<T> $serializer
     */
    public function __construct(
        private readonly \ReflectionProperty $property,
        private readonly PropertySerializer $serializer,
    ) {}

    /**
     * @template TClass of object
     * @param TClass $message
     * @return T
     */
    public function value(object $message): mixed
    {
        /** @var T */
        return $this->property->getValue($message);
    }

    /**
     * @param T $value
     * @throws PrototypeException
     */
    public function isNotEmpty(mixed $value): bool
    {
        return null !== $value && !$this->serializer->isEmpty($value);
    }

    public function protobufType(): Type
    {
        return $this->serializer->wireType();
    }

    /**
     * @param T $value
     * @throws PrototypeException
     * @throws \Kafkiansky\Binary\BinaryException
     * @throws \ReflectionException
     */
    public function encode(Buffer $buffer, WireSerializer $serializer, Tag $tag, mixed $value): void
    {
        $this->serializer->serializeValue($buffer, $serializer, $value, $tag);
    }
}
