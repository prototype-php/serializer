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
use Kafkiansky\Prototype\Internal\Wire\Tag;
use Kafkiansky\Prototype\PrototypeException;
use Typhoon\Reflection\PropertyReflection;

/**
 * @api
 * @internal
 * @psalm-internal Kafkiansky\Prototype
 */
final class PropertyDescriptor
{
    public function __construct(
        private readonly PropertyReflection $property,
        private readonly PropertySetter $setter,
    ) {}

    /**
     * @throws Binary\BinaryException
     * @throws \ReflectionException
     * @throws PrototypeException
     */
    public function readValue(Binary\Buffer $buffer, WireSerializer $serializer, Tag $tag): void
    {
        $this->setter->readValue($buffer, $serializer, $tag);
    }

    /**
     * @template T of object
     * @param T $object
     */
    public function setValue(object $object): void
    {
        $this->setter->setValue(
            new SetProperty($object, $this->property),
        );
    }

    /**
     * @template T of object
     * @param T $object
     */
    public function isInitialized(object $object): bool
    {
        return $this->property->isInitialized($object);
    }
}
