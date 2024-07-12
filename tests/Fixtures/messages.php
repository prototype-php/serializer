<?php

declare(strict_types=1);

namespace Kafkiansky\Prototype\Tests\Fixtures;

use Kafkiansky\Prototype\Field;
use Kafkiansky\Prototype\Scalar;
use Kafkiansky\Prototype\Type;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::IS_REPEATABLE)]
final class ProtobufMessage
{
    /**
     * @param non-empty-string $path
     * @param non-empty-string $constructorFunction
     */
    public function __construct(
        public readonly string $path,
        public readonly string $constructorFunction,
    ) {}
}

enum CompressionType: int
{
    case NONE = 0;
    case GZIP = 1;
    case LZ4 = 2;
}

#[ProtobufMessage(path: 'resources/message_with_enum_gzip.bin', constructorFunction: 'withGzipCompression')]
#[ProtobufMessage(path: 'resources/message_with_enum_lz4.bin', constructorFunction: 'withLZ4Compression')]
#[ProtobufMessage(path: 'resources/message_with_enum_none.bin', constructorFunction: 'withNoneCompression')]
final class MessageWithEnum
{
    public function __construct(
        public readonly CompressionType $type,
    ) {}

    public static function withGzipCompression(): self
    {
        return new self(CompressionType::GZIP);
    }

    public static function withLZ4Compression(): self
    {
        return new self(CompressionType::LZ4);
    }

    public static function withNoneCompression(): self
    {
        return new self(CompressionType::NONE);
    }
}

enum Corpus: int
{
    case CORPUS_UNSPECIFIED = 0;
    case CORPUS_UNIVERSAL = 1;
    case CORPUS_WEB = 2;
    case CORPUS_IMAGES = 3;
    case CORPUS_LOCAL = 4;
    case CORPUS_NEWS = 5;
    case CORPUS_PRODUCTS = 6;
    case CORPUS_VIDEO = 7;
}

#[ProtobufMessage(path: 'resources/search_request.bin', constructorFunction: 'webCorpus')]
final class SearchRequest
{
    public function __construct(
        public readonly string $query,
        #[Scalar(Type::int32)]
        public readonly int $pageNumber,
        #[Scalar(Type::int32)]
        public readonly int $resultsPerPage,
        public readonly Corpus $corpus,
    ) {
    }

    public static function webCorpus(): self
    {
        return new self(
            '?php+protobuf',
            100,
            10,
            Corpus::CORPUS_WEB,
        );
    }
}

final class SearchResult
{
    /**
     * @param list<string> $snippets
     */
    public function __construct(
        public readonly string $url,
        public readonly string $title,
        public readonly array $snippets,
    ) {}
}

#[ProtobufMessage(path: 'resources/search_response.bin', constructorFunction: 'twoResults')]
final class SearchResponse
{
    /**
     * @param list<SearchResult> $results
     */
    public function __construct(
        public readonly array $results,
        #[Scalar(Type::fixed32)]
        public readonly int $total,
    ) {}

    public static function twoResults(): self
    {
        return new self(
            [
                new SearchResult(
                    url: 'https://google.com/?protobuf',
                    title: 'Protobuf is a google implementation of binary serialization format',
                    snippets: ['grpc', 'protobuf'],
                ),
                new SearchResult(
                    url: 'https://google.com/?php+protobuf',
                    title: 'A modern strictly typed full-featured library for protobuf serialization without an inheritance.',
                    snippets: ['grpc', 'protobuf', 'php'],
                ),
            ],
            2,
        );
    }
}

enum PhoneType: int
{
    case MOBILE = 0;
    case HOME = 1;
    case WORK = 2;
}

final class PhoneNumber
{
    public function __construct(
        #[Field(num: 2)]
        public readonly PhoneType $type,
        public readonly string $number,
    ) {}
}

#[ProtobufMessage(path: 'resources/person.bin', constructorFunction: 'withDifferentOrder')]
final class Person
{
    /**
     * @param array<string, string> $info
     */
    public function __construct(
        public readonly string $name,
        #[Scalar(Type::sfixed32)]
        public readonly int $id,
        public readonly string $email,
        public readonly PhoneNumber $phone,
        public readonly array $info,
    ) {}

    public static function withDifferentOrder(): self
    {
        return new self(
            'John Doe',
            -200,
            'johndoe@gmail.com',
            new PhoneNumber(
                PhoneType::MOBILE,
                '7900000000',
            ),
            ['sex' => 'male'],
        );
    }
}

final class Skype
{
    public function __construct(
        public readonly string $id,
    ) {}
}

final class Email
{
    public function __construct(
        public readonly string $address,
    ) {}
}

final class Address
{
    public function __construct(
        public readonly string $street,
        public readonly string $city,
        public readonly string $state,
        public readonly string $zipCode,
    ) {}
}

final class Job
{
    public function __construct(
        public readonly string $title,
        public readonly string $company,
        public readonly string $startDate,
        public readonly string $endDate,
    ) {}
}

#[ProtobufMessage(path: 'resources/candidate.bin', constructorFunction: 'default')]
final class Candidate
{
    /**
     * @param array<string, Address> $addresses
     * @param list<PhoneNumber> $phones
     * @param list<Job> $previousJobs
     */
    public function __construct(
        #[Scalar(Type::int32)]
        public readonly int $id,
        public readonly string $name,
        public readonly string $email,
        public readonly array $addresses,
        public readonly array $phones,
        public readonly Skype|Email|null $contact,
        public array $previousJobs,
    ) {}

    public static function default(): self
    {
        return new self(
            1,
            'John Doe',
            'john.doe@example.com',
            [
                'home' => new Address(
                    street: '123 Main St',
                    city: 'Hometown',
                    state: 'CA',
                    zipCode: '12345',
                ),
                'work' => new Address(
                    street: '456 Business Rd',
                    city: 'Big City',
                    state: 'NY',
                    zipCode: '67890',
                ),
            ],
            [
                new PhoneNumber(PhoneType::MOBILE, '555-1234'),
                new PhoneNumber(PhoneType::HOME, '555-5678'),
            ],
            new Skype('john.doe.skype'),
            [
                new Job(
                    title: 'Software Engineer',
                    company: 'TechCorp',
                    startDate: '2015-06-01',
                    endDate: '2018-08-15',
                ),
                new Job(
                    title: 'Senior Developer',
                    company: 'DevCompany',
                    startDate: '2018-09-01',
                    endDate: '2021-12-31',
                ),
            ],
        );
    }
}

#[ProtobufMessage(path: 'resources/timestamp.bin', constructorFunction: 'default')]
final class MessageWithDateTimeInterface
{
    public function __construct(
        public readonly ?\DateTimeInterface $scheduled = null,
    ) {}

    public static function default(): self
    {
        $time = \DateTimeImmutable::createFromFormat('U.u', sprintf('%d.%d', 1720761326, 237536));
        \assert($time instanceof \DateTimeImmutable);

        return new self($time);
    }
}

#[ProtobufMessage(path: 'resources/timestamp.bin', constructorFunction: 'default')]
final class MessageWithDateTimeImmutable
{
    public function __construct(
        public readonly ?\DateTimeImmutable $scheduled = null,
    ) {}

    public static function default(): self
    {
        $time = \DateTimeImmutable::createFromFormat('U.u', sprintf('%d.%d', 1720761326, 237536));
        \assert($time instanceof \DateTimeImmutable);

        return new self($time);
    }
}

#[ProtobufMessage(path: 'resources/timestamp.bin', constructorFunction: 'default')]
final class MessageWithDateTime
{
    public function __construct(
        public readonly ?\DateTime $scheduled = null,
    ) {}

    public static function default(): self
    {
        $time = \DateTime::createFromFormat('U.u', sprintf('%d.%d', 1720761326, 237536));
        \assert($time instanceof \DateTime);

        return new self($time);
    }
}

#[ProtobufMessage(path: 'resources/struct.bin', constructorFunction: 'complex')]
final class Package
{
    /**
     * @param array<string, mixed> $options
     */
    public function __construct(
        public readonly string $name,
        public readonly array $options,
    ) {}

    public static function complex(): self
    {
        return new self(
            'kafkiansky/prototype',
            [
                'version' => 0.1,
                'released' => false,
                'tags' => ['php', 'protobuf'],
                'contributors' => [
                    'johndoe' => [
                        'role' => 'developer',
                        'years' => 28.0,
                        'male' => true,
                        'contacts' => [
                            'email' => 'johndoe@gmail.com',
                        ],
                    ],
                ],
                'releaseDate' => null,
                'package' => 'prototype',
            ],
        );
    }
}
