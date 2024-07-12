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
use Kafkiansky\Prototype\Internal\Type\ProtobufType;
use Kafkiansky\Prototype\Internal\Type\ValueType;
use Kafkiansky\Prototype\Internal\Wire\Tag;

/**
 * @internal
 * @psalm-internal Kafkiansky\Prototype
 * @template-extends PropertySetter<array<string, mixed>>
 */
final class StructProperty extends PropertySetter
{
    /** @var ProtobufType<array<string, mixed>> */
    private readonly ProtobufType $type;

    public function __construct()
    {
        [$this->type, $this->value] = [
            new ValueType(),
            [],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function readValue(Binary\Buffer $buffer, WireSerializer $serializer, Tag $tag): array
    {
        return $this->value = $this->type->read(
            $buffer->split($buffer->consumeVarUint()),
        );
    }
}
