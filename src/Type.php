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

namespace Kafkiansky\Prototype;

// Type describes the more granular constraints of numeric types and others.
// Allows you to specify types that do not exist natively or are difficult to describe in phpdoc.
enum Type
{
    // Uses variable-length encoding.
    // Inefficient for encoding negative numbers – if your field is likely to have negative values, use sint32 instead.
    case int32;

    // Uses variable-length encoding.
    // Inefficient for encoding negative numbers – if your field is likely to have negative values, use sint64 instead.
    case int64;

    // Uses variable-length encoding.
    case uint32;

    // Uses variable-length encoding.
    case uint64;

    // Uses variable-length encoding. Signed int value.
    // These more efficiently encode negative numbers than regular int32s.
    case sint32;

    // Uses variable-length encoding. Signed int value.
    // These more efficiently encode negative numbers than regular int64s.
    case sint64;

    // Always four bytes.
    // More efficient than uint32 if values are often greater than 2^28.
    case fixed32;

    // Always eight bytes.
    // More efficient than uint64 if values are often greater than 2^56.
    case fixed64;

    // Always four bytes.
    case sfixed32;

    // Always four bytes.
    case sfixed64;

    // A string must always contain UTF-8 encoded or 7-bit ASCII text, and cannot be longer than 2^32.
    case string;

    // Always four bytes.
    case float;

    // Always eight bytes.
    case double;

    // Uses variable-length encoding.
    case bool;
}
