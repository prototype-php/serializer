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

namespace Kafkiansky\Prototype\Internal\Type;

use Kafkiansky\Prototype\Exception\TypeIsNotSupported;
use Typhoon\Type\DefaultTypeVisitor;
use Typhoon\Type\Type;
use function Typhoon\TypeStringifier\stringify;

/**
 * @internal
 * @psalm-internal Kafkiansky\Prototype
 * @template-extends DefaultTypeVisitor<ProtobufType>
 */
final class ProtobufTypeResolver extends DefaultTypeVisitor
{
    /**
     * {@inheritdoc}
     */
    public function string(Type $self): StringType
    {
        return new StringType();
    }

    /**
     * {@inheritdoc}
     */
    public function bool(Type $self): BoolType
    {
        return new BoolType();
    }

    /**
     * {@inheritdoc}
     */
    public function float(Type $self): FloatType
    {
        return new FloatType();
    }

    /**
     * {@inheritdoc}
     */
    public function intRange(Type $self, ?int $min, ?int $max): IntType
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
     * {@inheritdoc}
     */
    public function int(Type $self): VaruintType
    {
        return new VaruintType();
    }

    /**
     * {@inheritdoc}
     */
    public function namedObject(Type $self, string $class, array $arguments): DoubleType
    {
        return match (true) {
            $class === 'double' => new DoubleType(),
            default => throw new TypeIsNotSupported(stringify($self)),
        };
    }

    /**
     * @throws TypeIsNotSupported
     */
    protected function default(Type $self): never
    {
        throw new TypeIsNotSupported(stringify($self));
    }
}
