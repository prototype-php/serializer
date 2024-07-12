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

use Kafkiansky\Prototype\Field;
use Kafkiansky\Prototype\Internal\Type as ProtobufType;
use Kafkiansky\Prototype\Map;
use Kafkiansky\Prototype\Repeated;
use Kafkiansky\Prototype\Scalar;
use Kafkiansky\Prototype\Type as NativeType;
use Typhoon\Reflection\ClassReflection;
use Typhoon\Reflection\PropertyReflection;
use Kafkiansky\Prototype\Internal;

/**
 * @api
 * @internal
 * @psalm-internal Kafkiansky\Prototype
 */
final class ProtobufReflector
{
    /**
     * @template T of object
     * @param ClassReflection<T> $class
     * @return array<positive-int, PropertyDescriptor>
     */
    public function properties(ClassReflection $class): array
    {
        [$properties, $num] = [[], 0];

        foreach ($class->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
            [$field, $scalarType, $listType, $mapType] = [
                self::attribute($property, Field::class),
                self::attribute($property, Scalar::class),
                self::attribute($property, Repeated::class),
                self::attribute($property, Map::class),
            ];

            /** @var PropertySetter|PropertySetter[] $propertySetter */
            $propertySetter = $property->getTyphoonType()->accept(
                new PropertySetterResolver(
                    self::nativeTypeToProtobufType($scalarType?->type),
                    self::nativeTypeToProtobufType($listType?->type),
                    [self::nativeTypeToProtobufType($mapType?->keyType), self::nativeTypeToProtobufType($mapType?->valueType)],
                ),
            );

            $fieldNum = $field?->num ?: ++$num;

            // The oneof variants are passed as different fields of the protobuf message.
            // Since php has unions, we can combine different fields under one setter by artificially giving them some order,
            // which starts from the number of the field itself to N, where N is the number of union variants, not counting null.
            // Don't forget to subtract 1, since range is inclusive.
            if (\is_array($propertySetter)) {
                // How many variants union has without null.
                $variants = $property->getType()?->allowsNull() ? \count($propertySetter) - 1 : \count($propertySetter);

                // Since we're using preincrement, we've already incremented the field count,
                // so here we subtract one to add the number of union variants. Can we rewrite this?
                /** @psalm-var positive-int $num */
                $num += $variants - 1;

                /** @psalm-var positive-int[] */
                $fieldNum = range($fieldNum, $fieldNum + $variants - 1);

                $propertySetter = new OneOfProperty(
                    array_combine($fieldNum, array_filter($propertySetter, static fn (PropertySetter $setter): bool => !$setter instanceof NullProperty)),
                    $property->hasDefaultValue() ? $property->getDefaultValue() : null,
                );
            }

            if (!\is_array($fieldNum)) {
                $fieldNum = [$fieldNum];
            }

            foreach ($fieldNum as $n) {
                $properties[$n] = new PropertyDescriptor($property, $propertySetter);
            }
        }

        return $properties;
    }

    /**
     * @template TClass of object
     * @param class-string<TClass> $attributeClass
     * @return ?TClass
     */
    private static function attribute(PropertyReflection $property, string $attributeClass): ?object
    {
        $attributes = $property->getAttributes($attributeClass);

        return \count($attributes) > 0 ? $attributes[0]->newInstance() : null;
    }

    /**
     * @return ?ProtobufType\ProtobufType<mixed>
     */
    private static function nativeTypeToProtobufType(?NativeType $type = null): ?ProtobufType\ProtobufType
    {
        /** @var ?ProtobufType\ProtobufType<mixed> */
        return match ($type) {
            NativeType::fixed32 => new ProtobufType\FixedUint32Type(),
            NativeType::fixed64 => new ProtobufType\FixedUint64Type(),
            NativeType::sfixed32 => new ProtobufType\FixedInt32Type(),
            NativeType::sfixed64 => new ProtobufType\FixedInt64Type(),
            NativeType::int32, NativeType::int64, NativeType::uint32, NativeType::uint64 => new ProtobufType\VaruintType(),
            NativeType::sint32, NativeType::sint64 => new ProtobufType\VarintType(),
            default => null,
        };
    }
}
