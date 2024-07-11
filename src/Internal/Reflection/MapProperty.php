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

use Kafkiansky\Binary;
use Kafkiansky\Prototype\Internal\Wire\Tag;

/**
 * @internal
 * @psalm-internal Kafkiansky\Prototype
 * @template-covariant TKey of array-key
 * @template-covariant TValue
 * @template-extends PropertySetter<array<TKey, TValue>>
 */
final class MapProperty extends PropertySetter
{
    /**
     * @param PropertySetter<TKey>   $keyType
     * @param PropertySetter<TValue> $valueType
     */
    public function __construct(
        private readonly PropertySetter $keyType,
        private readonly PropertySetter $valueType,
    ) {
        $this->value = [];
    }

    /**
     * {@inheritdoc}
     */
    public function readValue(Binary\Buffer $buffer, WireSerializer $serializer, Tag $tag): array
    {
        $buffer = $buffer->split($buffer->consumeVarUint());

        [$key, $value] = [null, null];

        while (!$buffer->isEmpty()) {
            $tag = Tag::decode($buffer);

            if ($tag->num === 1) {
                $key = $this->keyType->readValue($buffer, $serializer, $tag);
            } elseif($tag->num === 2) {
                $value = $this->valueType->readValue($buffer, $serializer, $tag);
            }
        }

        if (null !== $key) {
            $this->value[$key] = $value;
        }

        return $this->value;
    }
}
