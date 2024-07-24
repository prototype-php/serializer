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

/**
 * @internal
 * @psalm-internal Kafkiansky\Prototype
 * @psalm-import-type JSONValue from ValueType
 * @template-implements PropertyDeserializer<\Traversable<string, JSONValue>>
 */
final class DeserializeStructProperty implements PropertyDeserializer
{
    /** @var TypeReader<array<string, JSONValue>> */
    private readonly TypeReader $type;

    public function __construct()
    {
        $this->type = new ValueType();
    }

    /**
     * {@inheritdoc}
     */
    public function deserializeValue(Binary\Buffer $buffer, WireDeserializer $deserializer, Tag $tag): \Traversable
    {
        yield from $this->type->read(
            $buffer->split($buffer->consumeVarUint()),
        );
    }

    /**
     * {@inheritdoc}
     */
    public function default(): \Traversable
    {
        return new \ArrayIterator();
    }
}
