<?php

declare(strict_types=1);

namespace Kafkiansky\Prototype\Internal\Type;

use Kafkiansky\Binary;
use Kafkiansky\Prototype\PrototypeException;
use Kafkiansky\Prototype\Internal\Wire;

/**
 * @internal
 * @psalm-internal Kafkiansky\Prototype
 * @psalm-consistent-constructor
 * @template T
 */
interface TypeSerializer
{
    /**
     * @param T $value
     * @throws Binary\BinaryException
     * @throws PrototypeException
     */
    public function writeTo(Binary\Buffer $buffer, mixed $value): void;

    /**
     * @return T
     * @throws Binary\BinaryException
     * @throws PrototypeException
     */
    public function readFrom(Binary\Buffer $buffer): mixed;

    /**
     * @return T
     */
    public function default(): mixed;

    public function wireType(): Wire\Type;
}
