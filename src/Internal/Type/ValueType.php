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
use Kafkiansky\Prototype\Exception\PropertyNumberIsInvalid;
use Kafkiansky\Prototype\Exception\TypeWasNotExpected;
use Kafkiansky\Prototype\Internal\Wire\Tag;
use Kafkiansky\Prototype\Internal\Wire\Type;
use Kafkiansky\Prototype\PrototypeException;

/**
 * @internal
 * @psalm-internal Kafkiansky\Prototype
 * @psalm-type ScalarValue = string|double|bool|null
 * @psalm-type StructValue = array<string, ScalarValue>
 * @psalm-type Value       = ScalarValue|StructValue
 * @psalm-type ListValue   = Value[]
 * @psalm-type JSONValue   = Value|StructValue|ListValue
 * @template-implements ProtobufType<array<string, JSONValue>>
 */
final class ValueType implements ProtobufType
{
    private const NULL_TYPE = 1;
    private const NUMBER_TYPE = 2;
    private const STRING_TYPE = 3;
    private const BOOL_TYPE = 4;
    private const STRUCT_TYPE = 5;
    private const LIST_TYPE = 6;

    /**
     * @var array{
     *     1: callable(Binary\Buffer): null,
     *     2: callable(Binary\Buffer): double,
     *     3: callable(Binary\Buffer): string,
     *     4: callable(Binary\Buffer): bool,
     *     5: callable(Binary\Buffer): array<string, JSONValue>,
     *     6: callable(Binary\Buffer): JSONValue[],
     * }
     */
    private readonly array $types;

    public function __construct()
    {
        $this->types = [
            self::NULL_TYPE   => $this->readNull(...),
            self::NUMBER_TYPE => $this->readNumber(...),
            self::STRING_TYPE => $this->readString(...),
            self::BOOL_TYPE   => $this->readBool(...),
            self::STRUCT_TYPE => $this->readStruct(...),
            self::LIST_TYPE   => $this->readList(...),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function read(Binary\Buffer $buffer): array
    {
        $values = [];

        while (!$buffer->isEmpty()) {
            // Tag for google.protobuf.Value. Always BYTES.
            if (($type = Tag::decode($buffer)->type) !== Type::BYTES) {
                throw new TypeWasNotExpected($type->name);
            }

            // Single key pair of map<string, google.protobuf.Value>.
            $mapKeyValueBuffer = $buffer->split($buffer->consumeVarUint());

            // Tag for key from map<string, google.protobuf.Value>. Always BYTES (string).
            if (($type = Tag::decode($mapKeyValueBuffer)->type) !== Type::BYTES) {
                throw new TypeWasNotExpected($type->name);
            }

            $key = $mapKeyValueBuffer->consume($mapKeyValueBuffer->consumeVarUint());

            $values[$key] = $this->readValue($mapKeyValueBuffer);
        }

        return $values;
    }

    /**
     * {@inheritdoc}
     */
    public function default(): array
    {
        return [];
    }

    /**
     * @return null
     * @throws Binary\BinaryException
     * @throws PrototypeException
     */
    private function readNull(Binary\Buffer $buffer): mixed
    {
        // Null values always zero.
        (new VaruintType())->read($buffer);

        return null;
    }

    /**
     * @throws Binary\BinaryException
     * @throws PrototypeException
     */
    private function readNumber(Binary\Buffer $buffer): float
    {
        return (new DoubleType())->read($buffer);
    }

    /**
     * @throws Binary\BinaryException
     * @throws PrototypeException
     */
    private function readString(Binary\Buffer $buffer): string
    {
        return (new StringType())->read($buffer);
    }

    /**
     * @throws Binary\BinaryException
     * @throws PrototypeException
     */
    private function readBool(Binary\Buffer $buffer): bool
    {
        return (new BoolType())->read($buffer);
    }

    /**
     * @return list<JSONValue>
     * @throws Binary\BinaryException
     * @throws PrototypeException
     */
    private function readList(Binary\Buffer $buffer): array
    {
        $buffer = $buffer->split($buffer->consumeVarUint());

        $list = [];

        while (!$buffer->isEmpty()) {
            $list[] = $this->readValue($buffer);
        }

        return $list;
    }

    /**
     * @return JSONValue
     * @throws Binary\BinaryException
     * @throws PrototypeException
     */
    private function readValue(Binary\Buffer $buffer): mixed
    {
        // Tag for google.protobuf.Value. Always BYTES.
        if (($type = Tag::decode($buffer)->type) !== Type::BYTES) {
            throw new TypeWasNotExpected($type->name);
        }

        // Value as google.protobuf.Value.
        $valueBuffer = $buffer->split($buffer->consumeVarUint());
        $valueBufferTag = Tag::decode($valueBuffer);

        if ($valueBufferTag->num > self::LIST_TYPE) {
            throw new PropertyNumberIsInvalid($valueBufferTag->num);
        }

        /** @var JSONValue */
        return $this->types[$valueBufferTag->num]($valueBuffer);
    }

    /**
     * @return array<string, JSONValue>
     * @throws Binary\BinaryException
     * @throws PrototypeException
     */
    private function readStruct(Binary\Buffer $buffer): array
    {
        return $this->read(
            $buffer->split($buffer->consumeVarUint()),
        );
    }
}
