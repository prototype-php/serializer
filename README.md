## Prototype

A modern strictly typed full-featured library for protobuf serialization without an inheritance.

## Installation

This package can be installed as a [Composer](https://getcomposer.org/) dependency.

```bash
composer require kafkiansky/prototype
```

## Features
- [x] `scalar (any type of numbers and floats, string, bool)`
- [x] `repeated`
- [x] `map`
- [x] `oneof`
- [x] `enum`
- [x] `message (recursive)`
- [x] `google.protobuf.Timestamp`

## Types mapping

| Protobuf Type | Native Type      | PHPDoc Type                    | Attribute Type                         | Requires typehint? |
|---------------|------------------|--------------------------------|----------------------------------------|--------------------|
|   `fixed32`   |       `int`      |      `int<0, 4294967295>`      |  `\Kafkiansky\Prototype\Type::fixed32` |       **Yes**      |
|   `fixed64`   |       `int`      |          `int<0, max>`         |  `\Kafkiansky\Prototype\Type::fixed64` |       **Yes**      |
|   `sfixed32`  |       `int`      | `int<-2147483648, 2147483647>` | `\Kafkiansky\Prototype\Type::sfixed32` |       **Yes**      |
|   `sfixed64`  |       `int`      |         `int<min, max>`        | `\Kafkiansky\Prototype\Type::sfixed64` |       **Yes**      |
|    `int32`    |       `int`      |                -               |   `\Kafkiansky\Prototype\Type::int32`  |       **No**       |
|    `uint32`   |       `int`      |                -               |  `\Kafkiansky\Prototype\Type::uint32`  |       **No**       |
|    `sint32`   |       `int`      |                -               |  `\Kafkiansky\Prototype\Type::sint32`  |       **Yes**      |
|    `int64`    |       `int`      |                -               |   `\Kafkiansky\Prototype\Type::int64`  |       **No**       |
|    `uint64`   |       `int`      |                -               |  `\Kafkiansky\Prototype\Type::uint64`  |       **No**       |
|    `sint64`   |       `int`      |                -               |  `\Kafkiansky\Prototype\Type::sint64`  |       **Yes**      |
|    `string`   |     `string`     |                -               |                    -                   |       **No**       |
|     `bool`    |      `bool`      |                -               |                    -                   |       **No**       |
|    `float`    |      `float`     |                -               |                    -                   |       **No**       |
|    `double`   |      `float`     |            `double`            |                    -                   |       **Yes**      |
|     `enum`    | `enum T: int {}` |                -               |                    -                   |       **No**       |


## Testing

``` bash
$ composer test
```  

## License

The MIT License (MIT). See [License File](LICENSE) for more information.
