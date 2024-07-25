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
final class ProtobufMarshaller implements
    Reflection\Serializer,
    Reflection\Deserializer
{
    public function __construct(
        private readonly TyphoonReflector $classReflector,
        private readonly Reflection\ProtobufReflector $protobufReflector = new Reflection\ProtobufReflector(),
    ) {}

    /**
     * @template T of object
     * @param T $message
     * @throws \ReflectionException
     * @throws PrototypeException
     * @throws Binary\BinaryException
     */
    public function serialize(object $message, Binary\Buffer $buffer): void
    {
        $class = $this->classReflector->reflectClass($message::class);

        $properties = $this->protobufReflector->propertySerializers($class);

        foreach ($properties as $num => $property) {
            /** @psalm-suppress MixedAssignment It is ok here. */
            $propertyValue = $property->value($message);

            if ($property->isNotEmpty($propertyValue)) {
                $tag = new Tag($num, $property->protobufType());
                $property->encode($propertyBuffer = $buffer->clone(), $this, $tag, $propertyValue);

                if (!$propertyBuffer->isEmpty()) {
                    if (!is_iterable($propertyValue)) {
                        $tag->encode($buffer);
                    }

                    $buffer->write($propertyBuffer->reset());
                }
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

        $object = $class->toNativeReflection()->newInstanceWithoutConstructor();

        $properties = $this->protobufReflector->propertyDeserializers($class);

        /** @psalm-var \WeakMap<Reflection\PropertyDeserializeDescriptor, ValueContext> $values */
        $values = new \WeakMap();

        while (!$buffer->isEmpty()) {
            $tag = Tag::decode($buffer);

            // Discard bytes for the unknown field.
            if (!isset($properties[$tag->num])) {
                discard($buffer, $tag);

                continue;
            }

            $property = $properties[$tag->num];
            $values[$property] ??= new ValueContext();
            /** @psalm-suppress MixedArgument */
            $values[$property]->setValue($property->readValue($buffer, $this, $tag));
        }

        foreach ($values as $property => $propertyValue) {
            $property->setValue($object, $propertyValue->getValue());
        }

        // Additional cycle to set default values.
        // We could do it in one loop, but we have unions that refer to the same `\ReflectionProperty`,
        // and if the required unit variant serialized in the protobuf is not the first one in the schema,
        // we will set it to a default value (which is null), which we cannot change later for `readonly` properties.
        foreach ($properties as $property) {
            if (!$property->isInitialized($object)) {
                $property->setValue($object, $property->default());
            }
        }

        return $object;
    }
}
