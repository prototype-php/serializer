<?php declare(strict_types=1);

use Kafkiansky\Prototype\Type;

require_once __DIR__.'/../vendor/autoload.php';

enum TagType: int
{
    case UNKNOWN = 0;
    case COMPLEX = 1;
}

final class Tag
{
    public function __construct(
        public readonly string $name,
        public readonly TagType $type,
    ) {}
}

final class Option
{
    public function __construct(
        public readonly string $value,
    ) {
    }
}

final class Kafka
{
    public function __construct(
        public readonly string $topic,
        #[\Kafkiansky\Prototype\Scalar(Type::sfixed32)]
        public readonly int $partition,
    ) {}
}

final class RabbitMQ
{
    public function __construct(
        public readonly string $exchange,
        public readonly string $routingKey,
    ) {}
}

final class Request
{
    /**
     * @param list<Tag> $tags
     * @param array<string, Option> $options
     */
    public function __construct(
        public readonly array $tags,
        #[\Kafkiansky\Prototype\Scalar(Type::fixed32)]
        public readonly int $id,
        public readonly array $options,
        public readonly null|Kafka|RabbitMQ $transport,
    ) {
    }
}

final class Task
{
    /**
     * @param array<string, mixed> $options
     */
    public function __construct(
        public readonly string $name,
        public readonly array $options,
    ) {
    }
}

final class User
{
    /**
     * @param array{name: string, blocked: bool, salary: float, fired: \DateTimeInterface} $info
     */
    public function __construct(
        #[\Kafkiansky\Prototype\Scalar(Type::int64)]
        public readonly int $id,
        public readonly array $info,
    ) {}
}

final class SerializeRequest
{
    /**
     * @param array<string, mixed> $values
     */
    public function __construct(
        public readonly array $values,
    ) {}
}

final class Info
{
    public function __construct(
        public readonly float $version,
    ) {}
}

enum LangType: int
{
    case UNKNOWN = 0;
    case COMPILED = 1;
    case INTERPRETED = 2;
}

final class Lang
{
    /**
     * @param list<string> $tags
     */
    public function __construct(
        public readonly array $tags = [],
    ) {
    }
}

$serializer = new \Kafkiansky\Prototype\Serializer();
$buffer = \Kafkiansky\Binary\Buffer::empty(\Kafkiansky\Binary\Endianness::little());
$list = new \Kafkiansky\Prototype\Internal\Wire\SerializeArrayProperty(
    new \Kafkiansky\Prototype\Internal\Wire\SerializeScalarProperty(new \Kafkiansky\Prototype\Internal\Wire\StringType()),
);

$list->serializeValue($buffer, $serializer, 'google', '');

$buffer = $serializer->serialize(
    new Lang(['google', 'proto']),
);


$bytes = $buffer->reset();
dump(bin2hex($bytes));

$buffer = \Kafkiansky\Binary\Buffer::fromString($bytes, \Kafkiansky\Binary\Endianness::little());

try {
    dd($serializer->deserialize($buffer, Lang::class), $buffer->count());
} catch (\Throwable $e) {
    dd($e->getMessage(), $buffer);
}
