<?php

declare(strict_types=1);

namespace Kafkiansky\Prototype\Internal\Type;

use Kafkiansky\Binary;
use Kafkiansky\Prototype\PrototypeException;
use Typhoon\TypedMap\TypedMap;

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

    public function labels(): TypedMap;
}
