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

namespace Kafkiansky\Prototype\Internal\TypeConverter;

use Kafkiansky\Prototype\Exception\TypeIsNotSupported;
use Kafkiansky\Prototype\Internal\Wire\FixedInt32Type;
use Kafkiansky\Prototype\Internal\Wire\FixedInt64Type;
use Kafkiansky\Prototype\Internal\Wire\FixedUint32Type;
use Kafkiansky\Prototype\Internal\Wire\FixedUint64Type;
use Kafkiansky\Prototype\Internal\Wire\FloatType;
use Kafkiansky\Prototype\Internal\Wire\IntType;
use Kafkiansky\Prototype\Internal\Wire\StringType;
use Kafkiansky\Prototype\Internal\Wire\TypeReader;
use Kafkiansky\Prototype\Internal\Wire\TypeWriter;
use Kafkiansky\Prototype\Internal\Wire\ValueType;
use Kafkiansky\Prototype\Internal\Wire\VarintType;
use Typhoon\Type\Type;
use Typhoon\Type\Visitor\DefaultTypeVisitor;
use function Typhoon\Type\stringify;

/**
 * @internal
 * @psalm-internal Kafkiansky\Prototype
 * @template-extends DefaultTypeVisitor<TypeReader&TypeWriter>
 */
final class NativeTypeToProtobufTypeConverter extends DefaultTypeVisitor
{
    /**
     * {@inheritdoc}
     */
    public function string(Type $type): StringType
    {
        return new StringType();
    }

    /**
     * {@inheritdoc}
     */
    public function float(Type $type): FloatType
    {
        return new FloatType();
    }

    /**
     * {@inheritdoc}
     */
    public function mixed(Type $type): ValueType
    {
        return new ValueType();
    }

    /**
     * {@inheritdoc}
     */
    public function int(Type $type, ?int $min, ?int $max): IntType
    {
        if (-2147483648 === $min && 2147483647 === $max) {
            return new FixedInt32Type();
        } elseif (0 === $min && 4294967295 === $max) {
            return new FixedUint32Type();
        } elseif ((null === $min || \PHP_INT_MIN === $min) && (null === $max || \PHP_INT_MAX === $max)) {
            return new FixedInt64Type();
        } elseif (0 === $min && (null === $max || \PHP_INT_MAX === $max)) {
            return new FixedUint64Type();
        } else {
            return new VarintType();
        }
    }

    /**
     * @throws TypeIsNotSupported
     */
    protected function default(Type $type): never
    {
        throw new TypeIsNotSupported(stringify($type));
    }
}
