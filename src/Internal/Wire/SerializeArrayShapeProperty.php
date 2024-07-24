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
 * @template-implements PropertySerializer<array<non-empty-string, mixed>>
 */
final class SerializeArrayShapeProperty implements PropertySerializer
{
    /** @var array<non-empty-string, PropertySerializer<mixed>>  */
    private readonly array $serializers;

    /** @var array<non-empty-string, positive-int> */
    private readonly array $serializersNums;

    /**
     * @param array<non-empty-string, PropertySerializer<mixed>> $serializers
     */
    public function __construct(array $serializers)
    {
        $this->serializers = $serializers;
        $this->serializersNums = self::serializersToNums($serializers);
    }

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
        $tag->encode($buffer);

        $shapeBuffer = $buffer->clone();

        /** @psalm-suppress MixedAssignment */
        foreach ($value as $key => $val) {
            $num = $this->serializersNums[$key];
            $fieldTag = new Wire\Tag($num, $this->serializers[$key]->wireType());
            $fieldTag->encode($shapeBuffer);
            $this->serializers[$key]->serializeValue($shapeBuffer, $serializer, $val, $fieldTag);
        }

        if (!$shapeBuffer->isEmpty()) {
            $buffer
                ->writeVarUint($shapeBuffer->count())
                ->write($shapeBuffer->reset())
            ;
        }
    }

    public function wireType(): Wire\Type
    {
        return Wire\Type::BYTES;
    }

    /**
     * @param array<non-empty-string, PropertySerializer<mixed>> $serializers
     * @return array<non-empty-string, positive-int>
     */
    private static function serializersToNums(array $serializers): array
    {
        [$nums, $num] = [[], 0];

        foreach ($serializers as $fieldName => $_) {
            $nums[$fieldName] = ++$num;
        }

        return $nums;
    }
}
