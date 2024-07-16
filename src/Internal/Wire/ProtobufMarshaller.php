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
use Kafkiansky\Prototype\Internal\Reflection;
use Kafkiansky\Prototype\PrototypeException;
use Typhoon\Reflection\TyphoonReflector;

/**
 * @api
 * @internal
 * @psalm-internal Kafkiansky\Prototype
 */
final class ProtobufDeserializer implements
    Reflection\WireDeserializer,
    Reflection\WireSerializer
{
    public function __construct(
        private readonly TyphoonReflector $classReflector,
        private readonly Reflection\ProtobufReflector $protobufReflector = new Reflection\ProtobufReflector(),
    ) {}

    /**
     * @template T of object
     * @param T $message
     * @throws \ReflectionException
     */
    public function serialize(object $message, Binary\Buffer $buffer): void
    {
        $class = $this->classReflector->reflectClass($message);

        $properties = $this->protobufReflector->properties($class);

        foreach ($properties as $num => $property) {
            /** @psalm-suppress MixedAssignment It is ok here. */
            $propertyValue = $property->value($message);

            if ($property->isNotEmpty($propertyValue)) {
                $tag = new Tag($num, $property->protobufType());
                $tag->encode($buffer);
                $property->encode($buffer, $this, $propertyValue);
            }
        }
    }

    /**
     * @template T of object
     * @param class-string<T> $messageType
     * @return T
     * @throws \ReflectionException
     * @throws PrototypeException
     * @throws Binary\BinaryException
     */
    public function deserialize(string $messageType, Binary\Buffer $buffer): object
    {
        $class = $this->classReflector->reflectClass($messageType);

        $object = $class->newInstanceWithoutConstructor();

        $properties = $this->protobufReflector->properties($class);

        while (!$buffer->isEmpty()) {
            $tag = Tag::decode($buffer);

            // Discard bytes for the unknown field.
            if (!isset($properties[$tag->num])) {
                discard($buffer, $tag);

                continue;
            }

            $properties[$tag->num]->readValue($buffer, $this, $tag);
        }

        foreach ($properties as $property) {
            if (!$property->isInitialized($object)) {
                $property->setValue($object);
            }
        }

        return $object;
    }
}
