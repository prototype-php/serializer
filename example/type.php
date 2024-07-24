<?php declare(strict_types=1);

use Kafkiansky\Prototype\Exception\TypeIsNotSupported;
use Typhoon\Reflection\PropertyReflection;
use Typhoon\Type\Type;
use Typhoon\Type\Visitor\DefaultTypeVisitor;
use function Typhoon\Type\stringify;

require_once __DIR__ . '/../vendor/autoload.php';

$call = function (mixed $value): \Generator {
    if ($value instanceof \Traversable) {
        $value = iterator_to_array($value);
    }

    yield 'name' => $value;
};

dd(iterator_to_array($call((function (): \Generator {
    yield 1;
})())));

$g = function (): \Generator {
    yield 1 => 2;
};
$l = $g();
dd([...$l]);

final class Kek {
    public function __construct(
        public readonly null|string $name,
    )
    {
    }
}

$r = new ReflectionClass(Kek::class);
$object = $r->newInstanceWithoutConstructor();
dd($r->getProperty('name')->isInitialized($object));