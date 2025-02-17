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
use Prototype\Serializer\Exception\EnumDoesNotContainVariant;
use Prototype\Serializer\Exception\EnumDoesNotContainZeroVariant;
use Prototype\Serializer\Internal\Label\Labels;
use Prototype\Serializer\Internal\Type\TypeSerializer;
use Prototype\Serializer\Internal\Type\VarintType;
use Prototype\Serializer\Internal\Wire;
use Typhoon\TypedMap\TypedMap;

/**
 * @internal
 * @psalm-internal Prototype\Serializer
 * @template-implements PropertyMarshaller<int>
 */
final class ConstantEnumPropertyMarshaller implements PropertyMarshaller
{
    /** @var TypeSerializer<int>  */
    private readonly TypeSerializer $type;

    /**
     * @param list<int> $variants
     * @throws EnumDoesNotContainZeroVariant
     */
    public function __construct(
        private readonly string $enumName,
        private readonly array $variants,
    ) {
        if (!\in_array(0, $this->variants, strict: true)) {
            throw new EnumDoesNotContainZeroVariant($this->enumName);
        }

        $this->type = new VarintType();
    }

    /**
     * {@inheritdoc}
     */
    public function serializeValue(Byte\Writer $writer, Serializer $serializer, mixed $value, Wire\Tag $tag): void
    {
        if (!\in_array($value, $this->variants, strict: true)) {
            throw new EnumDoesNotContainVariant($this->enumName, $value);
        }

        $this->type->writeTo($writer, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function deserializeValue(Byte\Reader $reader, Deserializer $deserializer, Wire\Tag $tag): mixed
    {
        $value = $this->type->readFrom($reader);

        if (!\in_array($value, $this->variants, strict: true)) {
            throw new EnumDoesNotContainVariant($this->enumName, $value);
        }

        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function matchValue(mixed $value): bool
    {
        return \in_array($value, $this->variants, true);
    }

    /**
     * {@inheritdoc}
     */
    public function labels(): TypedMap
    {
        return $this->type->labels()
            ->with(Labels::isEmpty, static fn (int $variant): bool => 0 === $variant)
            ;
    }
}
