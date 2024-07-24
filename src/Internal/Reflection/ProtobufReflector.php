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

use Kafkiansky\Prototype\Exception\TooManyPropertyAttributes;
use Kafkiansky\Prototype\Field;
use Kafkiansky\Prototype\Internal;
use Kafkiansky\Prototype\Internal\TypeConverter\ConvertToPropertyDeserializer;
use Kafkiansky\Prototype\Internal\TypeConverter\ConvertToPropertySerializer;
use Kafkiansky\Prototype\Internal\TypeConverter\NativeTypeToPropertyMarshallerConverter;
use Kafkiansky\Prototype\Internal\TypeConverter\TypeToDeserializerConverter;
use Kafkiansky\Prototype\Internal\TypeConverter\TypeToSerializerConverter;
use Kafkiansky\Prototype\Internal\Wire\DeserializeUnionProperty;
use Kafkiansky\Prototype\Internal\Wire\PropertyDeserializer;
use Kafkiansky\Prototype\Internal\Wire\PropertySerializer;
use Kafkiansky\Prototype\Internal\Wire\SerializeUnionProperty;
use Kafkiansky\Prototype\PrototypeException;
use Typhoon\DeclarationId\AnonymousClassId;
use Typhoon\DeclarationId\NamedClassId;
use Typhoon\Reflection\AttributeReflection;
use Typhoon\Reflection\ClassReflection;
use Typhoon\Reflection\PropertyReflection;

/**
 * @api
 * @internal
 * @psalm-internal Kafkiansky\Prototype
 */
final class ProtobufReflector
{
    private readonly TypeToDeserializerConverter $typeToDeserializerConverter;
    private readonly TypeToSerializerConverter $typeToSerializerConverter;

    public function __construct()
    {
        $this->typeToDeserializerConverter = new TypeToDeserializerConverter();
        $this->typeToSerializerConverter = new TypeToSerializerConverter();
    }

    /**
     * @template T of object
     * @param ClassReflection<T, NamedClassId<class-string<T>>|AnonymousClassId<class-string<T>>> $class
     * @param Direction $direction
     * @psalm-return array<positive-int, $direction is Direction::DESERIALIZE ? PropertyDeserializeDescriptor : PropertySerializeDescriptor>
     * @throws PrototypeException
     */
    public function properties(ClassReflection $class, Direction $direction): array
    {
        [$properties, $num] = [[], 0];

        $classProperties = $class
            ->properties()
            ->filter(static fn (PropertyReflection $property): bool => $property->isPublic() && !$property->isStatic())
        ;

        foreach ($classProperties as $property) {
            /** @var list<ConvertToPropertySerializer|ConvertToPropertyDeserializer> $propertyMarshallers */
            $propertyMarshallers = $property
                ->attributes()
                ->filter(static fn (AttributeReflection $attribute): bool => $attribute->class()->isInstanceOf(match ($direction) {
                    Direction::DESERIALIZE => ConvertToPropertyDeserializer::class,
                    Direction::SERIALIZE => ConvertToPropertySerializer::class,
                }))
                ->map(static fn (AttributeReflection $attribute): object => $attribute->newInstance())
            ;

            if (\count($propertyMarshallers) > 1) {
                throw new TooManyPropertyAttributes($property->id->toString(), \count($propertyMarshallers));
            }

            /** @var ?Field $field */
            $field = $property
                ->attributes()
                ->filter(static fn (AttributeReflection $attribute): bool => $attribute->class()->isInstanceOf(Field::class))
                ->map(static fn (AttributeReflection $attribute): object => $attribute->newInstance())
                ->toList()[0] ?? null
            ;

            /** @var PropertyDeserializer|PropertySerializer|list<PropertyDeserializer|PropertySerializer> $propertyMarshaller */
            $propertyMarshaller = \count($propertyMarshallers) > 0
                ? match ($direction) {
                    Direction::DESERIALIZE => $propertyMarshallers[0]->convertToDeserializer($this->typeToDeserializerConverter),
                    Direction::SERIALIZE => $propertyMarshallers[0]->convertToSerializer($this->typeToSerializerConverter),
                }
                : $property->type()->accept(new NativeTypeToPropertyMarshallerConverter($direction))
            ;

            $fieldNum = $field?->num ?: ++$num;

            // Special case for `?Type` and `true|false` because it's not a true union for protobuf.
            if (\is_array($propertyMarshaller) && \count($propertyMarshaller) === 1) {
                $propertyMarshaller = $propertyMarshaller[0];
            }

            // The oneof variants are passed as different fields of the protobuf message.
            // Since php has unions, we can combine different fields under one setter by artificially giving them some order,
            // which starts from the number of the field itself to N, where N is the number of union variants, not counting null.
            // Don't forget to subtract 1, since range is inclusive.
            if (\is_array($propertyMarshaller)) {
                // How many variants union has.
                $variants = \count($propertyMarshaller);

                // Since we're using preincrement, we've already incremented the field count,
                // so here we subtract one to add the number of union variants. Can we rewrite this?
                /** @psalm-var positive-int $num */
                $num += $variants - 1;

                /** @psalm-var positive-int[] */
                $fieldNum = range($fieldNum, $fieldNum + $variants - 1);

                $propertyMarshaller = match ($direction) {
                    Direction::DESERIALIZE => new DeserializeUnionProperty(array_combine($fieldNum, $propertyMarshaller)),
                    Direction::SERIALIZE => new SerializeUnionProperty([]),
                };
            }

            if (!\is_array($fieldNum)) {
                $fieldNum = [$fieldNum];
            }

            foreach ($fieldNum as $n) {
                $properties[$n] = match ($direction) {
                    Direction::DESERIALIZE => new PropertyDeserializeDescriptor($property->toNativeReflection(), $propertyMarshaller),
                    Direction::SERIALIZE => new PropertySerializeDescriptor($property->toNativeReflection(), $propertyMarshaller),
                };
            }
        }

        return $properties;
    }
}
