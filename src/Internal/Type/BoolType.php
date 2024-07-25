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

use Kafkiansky\Binary;
use Kafkiansky\Prototype\Internal\Wire\Type;

/**
 * @internal
 * @psalm-internal Kafkiansky\Prototype
 * @psalm-consistent-constructor
 * @template-implements TypeSerializer<bool>
 */
final class BoolType implements TypeSerializer
{
    /**
     * {@inheritdoc}
     */
    public function readFrom(Binary\Buffer $buffer): bool
    {
        return 0 !== $buffer->consumeVarUint();
    }

    /**
     * {@inheritdoc}
     */
    public function writeTo(Binary\Buffer $buffer, mixed $value): void
    {
        $buffer->writeVarUint((int)$value);
    }

    /**
     * {@inheritdoc}
     */
    public function default(): bool
    {
        return false;
    }

    public function wireType(): Type
    {
        return Type::VARINT;
    }
}
