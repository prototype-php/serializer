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

use Kafkiansky\Prototype\Internal\Reflection\Direction;
use Kafkiansky\Prototype\Internal\Wire\BoolType;
use Kafkiansky\Prototype\Internal\Wire\DeserializeArrayProperty;
use Kafkiansky\Prototype\Internal\Wire\DeserializeArrayShapeProperty;
use Kafkiansky\Prototype\Internal\Wire\DeserializeHashTableProperty;
use Kafkiansky\Prototype\Internal\Wire\DeserializeScalarProperty;
use Kafkiansky\Prototype\Internal\Wire\DeserializeStructProperty;
use Kafkiansky\Prototype\Internal\Wire\PropertyDeserializer;
use Kafkiansky\Prototype\Internal\Wire\PropertySerializer;
use Kafkiansky\Prototype\Internal\Wire\SerializeArrayProperty;
use Kafkiansky\Prototype\Internal\Wire\SerializeArrayShapeProperty;
use Kafkiansky\Prototype\Internal\Wire\SerializeHashTableProperty;
use Kafkiansky\Prototype\Internal\Wire\SerializeScalarProperty;
use Kafkiansky\Prototype\Internal\Wire\SerializeStructProperty;
use Kafkiansky\Prototype\PrototypeException;
use Typhoon\DeclarationId\NamedClassId;
use Typhoon\Type\ShapeElement;
use Typhoon\Type\Type;
use Typhoon\Type\Visitor\DefaultTypeVisitor;

/**
 * @internal
 * @psalm-internal Kafkiansky\Prototype
 * @template T of Direction
 * @template-extends DefaultTypeVisitor<T is Direction::DESERIALIZE ? PropertyDeserializer : PropertySerializer>
 */
final class NativeTypeToPropertyMarshallerConverter extends DefaultTypeVisitor
{
    public function __construct(
        private readonly Direction $direction,
        private readonly NativeTypeToProtobufTypeConverter $nativeTypeToProtobufTypeConverter = new NativeTypeToProtobufTypeConverter(),
    ) {}

    /**
     * {@inheritdoc}
     */
    public function list(Type $type, Type $valueType, array $elements): DeserializeArrayProperty|SerializeArrayProperty
    {
        return match ($this->direction) {
            Direction::DESERIALIZE => new DeserializeArrayProperty($valueType->accept($this)),
            Direction::SERIALIZE => new SerializeArrayProperty($valueType->accept($this)),
        };
    }

    /**
     * {@inheritdoc}
     */
    public function array(Type $type, Type $keyType, Type $valueType, array $elements): PropertySerializer|PropertyDeserializer
    {
        return match (true) {
            \count($elements) > 0 => match ($this->direction) {
                Direction::DESERIALIZE => new DeserializeArrayShapeProperty(
                        array_merge(
                            ...array_map(
                            function (int|string $name, ShapeElement $element): array {
                                /** @var array<non-empty-string, PropertyDeserializer<mixed>> */
                                return [(string) $name => $element->type->accept($this)];
                            },
                            array_keys($elements),
                            $elements,
                        ),
                    ),
                ),
                Direction::SERIALIZE => new SerializeArrayShapeProperty(
                        array_merge(
                            ...array_map(
                            function (int|string $name, ShapeElement $element): array {
                                /** @var array<non-empty-string, PropertySerializer<mixed>> */
                                return [(string) $name => $element->type->accept($this)];
                            },
                            array_keys($elements),
                            $elements,
                        ),
                    ),
                ),
            },
            $keyType->accept(new IsString()) && $valueType->accept(new IsMixed()) => match ($this->direction) {
                Direction::DESERIALIZE => new DeserializeStructProperty(),
                Direction::SERIALIZE => new SerializeStructProperty(),
            },
            default => match ($this->direction) {
                Direction::DESERIALIZE => new DeserializeHashTableProperty(
                    $keyType->accept(new ArrayKey($this->direction)),
                    $valueType->accept($this),
                ),
                Direction::SERIALIZE => new SerializeHashTableProperty(
                    $keyType->accept(new ArrayKey($this->direction)),
                    $valueType->accept($this),
                ),
            },
        };
    }

    /**
     * @throws PrototypeException
     */
    public function namedObject(Type $type, NamedClassId $classId, array $typeArguments): PropertyDeserializer|PropertySerializer
    {
        return typeStringToPropertyMarshaller($classId->name, $this->direction);
    }

    /**
     * {@inheritdoc}
     */
    public function union(Type $type, array $ofTypes): array
    {
        $hasBool = false;
        $propertyMarshallers = [];

        foreach ($ofTypes as $ofType) {
            if ($ofType->accept(new IsBool())) {
                // Special case to treat the true|false as a bool.
                $hasBool = true;
            } elseif (!$ofType->accept(new IsNull())) {
                $propertyMarshallers[] = $ofType->accept($this);
            }
        }

        if ($hasBool) {
            $propertyMarshallers[] = match ($this->direction) {
                Direction::DESERIALIZE => new DeserializeScalarProperty(new BoolType()),
                Direction::SERIALIZE => new SerializeScalarProperty(new BoolType()),
            };
        }

        return $propertyMarshallers;
    }

    /**
     * {@inheritdoc}
     */
    protected function default(Type $type): PropertyDeserializer|PropertySerializer
    {
        return match ($this->direction) {
            Direction::DESERIALIZE => new DeserializeScalarProperty($type->accept($this->nativeTypeToProtobufTypeConverter)),
            Direction::SERIALIZE => new SerializeScalarProperty($type->accept($this->nativeTypeToProtobufTypeConverter)),
        };
    }
}
