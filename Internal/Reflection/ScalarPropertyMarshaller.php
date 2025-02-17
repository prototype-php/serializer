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

namespace Prototype\Serializer\Internal\Reflection;

use Prototype\Byte;
use Prototype\Serializer\Internal\Label\Labels;
use Prototype\Serializer\Internal\Type\ProtobufType;
use Prototype\Serializer\Internal\Type\TypeSerializer;
use Prototype\Serializer\Internal\Wire;
use Typhoon\TypedMap\TypedMap;

/**
 * @internal
 * @psalm-internal Prototype\Serializer
 * @template T
 * @template-implements PropertyMarshaller<T>
 */
final class ScalarPropertyMarshaller implements PropertyMarshaller
{
    /**
     * @param TypeSerializer<T> $type
     */
    public function __construct(
        private readonly TypeSerializer $type,
    ) {}

    /**
     * {@inheritdoc}
     */
    public function deserializeValue(Byte\Reader $reader, Deserializer $deserializer, Wire\Tag $tag): mixed
    {
        return $this->type->readFrom($reader);
    }

    /**
     * {@inheritdoc}
     */
    public function serializeValue(Byte\Writer $writer, Serializer $serializer, mixed $value, Wire\Tag $tag): void
    {
        $this->type->writeTo($writer, $value);
    }

    public function matchValue(mixed $value): bool
    {
        $schemaType = $this->type->labels()[Labels::schemaType];

        return match ($schemaType) {
            ProtobufType::string,
            ProtobufType::bytes  => \is_string($value),
            ProtobufType::bool   => \is_bool($value),
            ProtobufType::float,
            ProtobufType::double => \is_float($value),
            default              => \is_int($value),
        };
    }

    /**
     * {@inheritdoc}
     */
    public function labels(): TypedMap
    {
        return $this->type
            ->labels()
            ->with(Labels::isEmpty, static fn (mixed $value): bool => 0 === $value
                || 0.0 === $value
                || false === $value
                || '' === $value,
            )
            ;
    }
}
