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

namespace Prototype\Serializer\Internal\TypeConverter;

use Typhoon\DeclarationId\AliasId;
use Typhoon\Reflection\TyphoonReflector;
use Typhoon\Type\Type;
use Typhoon\Type\Visitor\DefaultTypeVisitor;

/**
 * @internal
 * @psalm-internal Prototype\Serializer
 * @template-extends DefaultTypeVisitor<bool>
 */
final class IsConstantEnum extends DefaultTypeVisitor
{
    public function __construct(
        private readonly TyphoonReflector $reflector,
    ) {}

    /**
     * {@inheritdoc}
     */
    public function alias(Type $type, AliasId $aliasId, array $typeArguments): mixed
    {
        return $this->reflector->reflect($aliasId)->type()->accept($this);
    }

    /**
     * {@inheritdoc}
     */
    public function intValue(Type $type, int $value): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function int(Type $type, Type $minType, Type $maxType): bool
    {
        $intValue = new ToIntValue($this->reflector);

        return $minType->accept($intValue) === $maxType->accept($intValue);
    }

    /**
     * {@inheritdoc}
     */
    public function union(Type $type, array $ofTypes): bool
    {
        foreach ($ofTypes as $ofType) {
            if (!$ofType->accept($this)) {
                return false;
            }
        }

        return true;
    }

    protected function default(Type $type): bool
    {
        return false;
    }
}
