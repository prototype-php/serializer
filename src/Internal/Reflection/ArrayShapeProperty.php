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
use Kafkiansky\Prototype\Internal\Wire;

/**
 * @internal
 * @psalm-internal Kafkiansky\Prototype
 * @template-extends PropertySetter<array<non-empty-string, mixed>>
 */
final class ArrayShapeProperty extends PropertySetter
{
    /** @var array<non-empty-string, PropertySetter<mixed>>  */
    private readonly array $setters;

    /** @var array<positive-int, non-empty-string> */
    private readonly array $setterNums;

    /**
     * @param array<non-empty-string, PropertySetter<mixed>> $setters
     */
    public function __construct(array $setters)
    {
        $this->setters = $setters;
        $this->setterNums = self::settersToNums($setters);
        $this->value = [];
    }

    /**
     * {@inheritdoc}
     */
    public function readValue(Binary\Buffer $buffer, WireSerializer $serializer, Wire\Tag $tag): array
    {
        $buffer = $buffer->split($buffer->consumeVarUint());

        while (!$buffer->isEmpty()) {
            $tag = Wire\Tag::decode($buffer);

            if (!isset($this->setterNums[$tag->num])) {
                Wire\discard($buffer, $tag);

                continue;
            }

            $fieldName = $this->setterNums[$tag->num];
            $this->value[$fieldName] = $this->setters[$fieldName]->readValue($buffer, $serializer, $tag);
        }

        /** @var array<non-empty-string, mixed> */
        return $this->value;
    }

    /**
     * @param array<non-empty-string, PropertySetter<mixed>> $setters
     * @return array<positive-int, non-empty-string>
     */
    private static function settersToNums(array $setters): array
    {
        [$nums, $num] = [[], 0];

        foreach ($setters as $fieldName => $_) {
            $nums[++$num] = $fieldName;
        }

        return $nums;
    }
}
