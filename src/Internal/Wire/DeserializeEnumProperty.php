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
use Kafkiansky\Prototype\Exception\EnumDoesNotContainVariant;
use Kafkiansky\Prototype\Exception\EnumDoesNotContainZeroVariant;

/**
 * @internal
 * @psalm-internal Kafkiansky\Prototype
 * @template-covariant T of \BackedEnum
 * @template-implements PropertyDeserializer<T>
 */
final class DeserializeEnumProperty implements PropertyDeserializer
{
    /** @var TypeReader<int<0, max>>  */
    private readonly TypeReader $type;

    /**
     * @psalm-param enum-string<T> $enumName
     */
    public function __construct(
        private readonly string $enumName,
    ) {
        $this->type = new VaruintType();
    }

    /**
     * {@inheritdoc}
     */
    public function deserializeValue(Binary\Buffer $buffer, WireDeserializer $deserializer, Tag $tag): \BackedEnum
    {
        return $this->enumName::tryFrom($variant = $this->type->read($buffer)) ?: throw new EnumDoesNotContainVariant(
            $this->enumName,
            $variant,
        );
    }

    /**
     * {@inheritdoc}
     */
    public function default(): mixed
    {
        return $this->enumName::tryFrom(0) ?: throw new EnumDoesNotContainZeroVariant($this->enumName);
    }
}
