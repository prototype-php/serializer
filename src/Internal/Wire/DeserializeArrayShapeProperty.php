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
 * @template-implements PropertyDeserializer<\Traversable<non-empty-string, mixed>>
 */
final class DeserializeArrayShapeProperty implements PropertyDeserializer
{
    /** @var array<non-empty-string, PropertyDeserializer<mixed>>  */
    private readonly array $deserializers;

    /** @var array<positive-int, non-empty-string> */
    private readonly array $deserializerNums;

    /**
     * @param array<non-empty-string, PropertyDeserializer<mixed>> $deserializers
     */
    public function __construct(array $deserializers)
    {
        $this->deserializers = $deserializers;
        $this->deserializerNums = self::deserializersToNums($deserializers);
    }

    /**
     * {@inheritdoc}
     */
    public function deserializeValue(Binary\Buffer $buffer, WireDeserializer $deserializer, Wire\Tag $tag): \Traversable
    {
        $buffer = $buffer->split($buffer->consumeVarUint());

        while (!$buffer->isEmpty()) {
            $tag = Wire\Tag::decode($buffer);

            if (!isset($this->deserializerNums[$tag->num])) {
                Wire\discard($buffer, $tag);

                continue;
            }

            $fieldName = $this->deserializerNums[$tag->num];

            yield $fieldName => $this->deserializers[$fieldName]->deserializeValue($buffer, $deserializer, $tag);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function default(): \Traversable
    {
        return new \ArrayIterator();
    }

    /**
     * @param array<non-empty-string, PropertyDeserializer<mixed>> $deserializers
     * @return array<positive-int, non-empty-string>
     */
    private static function deserializersToNums(array $deserializers): array
    {
        [$nums, $num] = [[], 0];

        foreach ($deserializers as $fieldName => $_) {
            $nums[++$num] = $fieldName;
        }

        return $nums;
    }
}
