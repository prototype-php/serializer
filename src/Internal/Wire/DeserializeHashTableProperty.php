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

/**
 * @internal
 * @psalm-internal Kafkiansky\Prototype
 * @template-covariant TKey of array-key
 * @template-covariant TValue
 * @template-implements PropertyDeserializer<\Traversable<TKey, TValue>>
 */
final class DeserializeHashTableProperty implements PropertyDeserializer
{
    /**
     * @param PropertyDeserializer<TKey>   $keyDeserializer
     * @param PropertyDeserializer<TValue> $valueDeserializer
     */
    public function __construct(
        private readonly PropertyDeserializer $keyDeserializer,
        private readonly PropertyDeserializer $valueDeserializer,
    ) {}

    /**
     * {@inheritdoc}
     */
    public function deserializeValue(Binary\Buffer $buffer, WireDeserializer $deserializer, Tag $tag): \Traversable
    {
        $buffer = $buffer->split($buffer->consumeVarUint());

        [$key, $value] = [null, null];

        while (!$buffer->isEmpty()) {
            $tag = Tag::decode($buffer);

            if ($tag->num === 1) {
                $key = $this->keyDeserializer->deserializeValue($buffer, $deserializer, $tag);
            } elseif ($tag->num === 2) {
                $value = $this->valueDeserializer->deserializeValue($buffer, $deserializer, $tag);
            }
        }

        if (null !== $key) {
            yield $key => $value;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function default(): \Traversable
    {
        return new \ArrayIterator();
    }
}
