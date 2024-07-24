<?php declare(strict_types=1);

use Kafkiansky\Prototype\ArrayShape;
use Kafkiansky\Prototype\Map;
use Kafkiansky\Prototype\Repeated;
use Kafkiansky\Prototype\Scalar;
use Kafkiansky\Prototype\Serializer;
use Kafkiansky\Prototype\Type;

require_once __DIR__.'/../vendor/autoload.php';

enum TopicType: int
{
    case DEFAULT = 0;
    case INTERNAL = 1;
}

final class Partition
{
    public function __construct(
        #[Scalar(Type::sfixed32)]
        public readonly int $number,
    ) {}
}

final class CreateTopicsRequest
{
    public function __construct(
        #[ArrayShape(['number' => Type::sfixed32])]
        public readonly array $partition = [],
        #[Repeated(Type::string)]
        public readonly array $topics = [],
        #[Map(keyType: Type::string, valueType: Type::string)]
        public readonly array $tags = [],
        public readonly TopicType $type = TopicType::DEFAULT,
    ) {}
}

final class MessageWithDateTime
{
    public function __construct(
        public readonly ?\DateTime $scheduled = null,
    ) {}

    public static function default(): self
    {
        $time = \DateTime::createFromFormat('U.u', sprintf('%d.%d', 1720761326, 237536));
        assert($time instanceof \DateTime);

        return new self($time);
    }
}

$serializer = new Serializer();
$buffer = $serializer->serialize(MessageWithDateTime::default());
dd($buffer);
