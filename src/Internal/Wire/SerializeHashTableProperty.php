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

namespace Kafkiansky\Prototype\Internal\Wire;

use Kafkiansky\Binary;
use Kafkiansky\Prototype\Internal\Wire;

/**
 * @internal
 * @psalm-internal Kafkiansky\Prototype
 * @template TKey of array-key
 * @template TValue
 * @template-implements PropertySerializer<array<TKey, TValue>>
 */
final class SerializeHashTableProperty implements PropertySerializer
{
    /**
     * @param PropertySerializer<TKey>   $keySerializer
     * @param PropertySerializer<TValue> $valueSerializer
     */
    public function __construct(
        private readonly PropertySerializer $keySerializer,
        private readonly PropertySerializer $valueSerializer,
    ) {}

    /**
     * {@inheritdoc}
     */
    public function isEmpty(mixed $value): bool
    {
        return [] === $value;
    }

    /**
     * {@inheritdoc}
     */
    public function serializeValue(Binary\Buffer $buffer, WireSerializer $serializer, mixed $value, Wire\Tag $tag): void
    {
        foreach ($value as $key => $val) {
            $tag->encode($buffer);

            $mapKeyValueBuffer = $buffer->clone();

            $keyTag = new Wire\Tag(1, $this->keySerializer->wireType());
            $keyTag->encode($mapKeyValueBuffer);

            $this->keySerializer->serializeValue(
                $mapKeyValueBuffer,
                $serializer,
                $key,
                $keyTag,
            );

            $valueTag = new Wire\Tag(2, $this->valueSerializer->wireType());
            $valueTag->encode($mapKeyValueBuffer);

            $this->valueSerializer->serializeValue(
                $mapKeyValueBuffer,
                $serializer,
                $val,
                $valueTag,
            );

            $buffer
                ->writeVarUint($mapKeyValueBuffer->count())
                ->write($mapKeyValueBuffer->reset())
            ;
        }
    }

    public function wireType(): Wire\Type
    {
        return Wire\Type::BYTES;
    }
}
