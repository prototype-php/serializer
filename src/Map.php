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

namespace Kafkiansky\Prototype;

use Kafkiansky\Prototype\Internal\TypeConverter\ConvertToPropertyDeserializer;
use Kafkiansky\Prototype\Internal\TypeConverter\ConvertToPropertySerializer;
use Kafkiansky\Prototype\Internal\TypeConverter\TypeToDeserializerConverter;
use Kafkiansky\Prototype\Internal\TypeConverter\TypeToSerializerConverter;
use Kafkiansky\Prototype\Internal\Wire\DeserializeHashTableProperty;
use Kafkiansky\Prototype\Internal\Wire\DeserializeScalarProperty;
use Kafkiansky\Prototype\Internal\Wire\DeserializeStructProperty;
use Kafkiansky\Prototype\Internal\Wire\PropertyDeserializer;
use Kafkiansky\Prototype\Internal\Wire\PropertySerializer;
use Kafkiansky\Prototype\Internal\Wire\SerializeHashTableProperty;
use Kafkiansky\Prototype\Internal\Wire\SerializeScalarProperty;
use Kafkiansky\Prototype\Internal\Wire\SerializeStructProperty;
use Kafkiansky\Prototype\Internal\Wire\StringType;

/**
 * @api
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
final class Map implements
    ConvertToPropertyDeserializer,
    ConvertToPropertySerializer
{
    /**
     * @psalm-param Type|class-string|enum-string|interface-string|null $valueType
     */
    public function __construct(
        public readonly ?Type $keyType = null,
        public readonly null|Type|string $valueType = null,
    ) {}

    /**
     * {@inheritdoc}
     */
    public function convertToDeserializer(TypeToDeserializerConverter $converter): PropertyDeserializer
    {
        /** @var PropertyDeserializer<array-key> $keyType */
        $keyType = null === $this->keyType ? new DeserializeScalarProperty(new StringType()) : $converter->protobufTypeToPropertyDeserializer($this->keyType);

        return new DeserializeHashTableProperty(
            $keyType,
            null === $this->valueType ? new DeserializeStructProperty() : match (true) {
                $this->valueType instanceof Type => $converter->protobufTypeToPropertyDeserializer($this->valueType),
                default => $converter->typeStringToPropertyDeserializer($this->valueType),
            },
        );
    }

    /**
     * {@inheritdoc}
     */
    public function convertToSerializer(TypeToSerializerConverter $converter): PropertySerializer
    {
        /** @var PropertySerializer<array-key> $keyType */
        $keyType = null === $this->keyType ? new SerializeScalarProperty(new StringType()) : $converter->protobufTypeToPropertySerializer($this->keyType);

        /** @psalm-suppress InvalidArgument */
        return new SerializeHashTableProperty(
            $keyType,
            null === $this->valueType ? new SerializeStructProperty() : match (true) {
                $this->valueType instanceof Type => $converter->protobufTypeToPropertySerializer($this->valueType),
                default => $converter->typeStringToPropertySerializer($this->valueType),
            },
        );
    }
}
