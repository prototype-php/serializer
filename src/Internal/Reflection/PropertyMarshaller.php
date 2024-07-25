<?php

declare(strict_types=1);

namespace Kafkiansky\Prototype\Internal\Reflection;

use Kafkiansky\Binary;
use Kafkiansky\Prototype\Internal\Wire;
use Kafkiansky\Prototype\PrototypeException;

/**
 * @template T
 */
interface PropertyMarshaller
{
    /**
     * @param T $value
     * @throws Binary\BinaryException
     * @throws PrototypeException
     * @throws \ReflectionException
     */
    public function serializeValue(Binary\Buffer $buffer, Serializer $serializer, mixed $value, Wire\Tag $tag): void;

    /**
     * @return T
     * @throws Binary\BinaryException
     * @throws \ReflectionException
     * @throws PrototypeException
     */
    public function deserializeValue(Binary\Buffer $buffer, Deserializer $deserializer, Wire\Tag $tag): mixed;

    /**
     * @return ?T
     * @throws PrototypeException
     */
    public function default(): mixed;

    /**
     * @param T $value
     * @throws PrototypeException
     */
    public function isEmpty(mixed $value): bool;

    public function wireType(): Wire\Type;
}
