# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [4.0.0] - 2026-11-27

### Added
- `Result::try()` calls a closure and returns its value in `Ok`. A thrown exception of a listed class becomes `Err`, and any other exception is rethrown. With no class it catches `\Exception` and lets `\Error` through.
- `Option::fromNullable()` turns `null` into `None` and every other value into `Some`.
- `Result::unwrapOrThrow()` returns the `Ok` value. On `Err` it throws the error itself when the error is a `\Throwable`. Any other error is thrown inside a `RuntimeException`, the way `unwrap()` throws it.
- `Result::gen()` runs a generator in which each step is a Result. `Ok` hands its value back. The first `Err` stops the generator and is returned, like `?` in Rust.
- `Result::step()` is one step of `Result::gen()`, written `$value = yield from $result->step()`. PHPStan and an IDE know the type of `$value` with no extension. `$value = yield $result` runs the same way without a type.

### Changed
- `Result` and `Option` are abstract classes, so the static constructors live on the type you already import. `Brimmar\PhpResult\Interfaces\Result` is now `Brimmar\PhpResult\Result`. `Brimmar\PhpOption\Interfaces\Option` is now `Brimmar\PhpOption\Option`. Replace those two imports.
- The type parameters are covariant. `Result<int, NotFound>` is accepted where `Result<int, NotFound|Invalid>` is declared.
- `andThen()` and `and()` return `Result<U, E|F>`, so steps with different error types chain. `or()`, `orElse()` and `unwrapOrElse()` return both success types. Option's `or()`, `orElse()` and `xor()` do the same.
- `new Ok($value)` is `Ok<T, never>`, `new Err($error)` is `Err<never, E>`, and `new None` is `None<never>`. `isOk()`, `isErr()`, `isSome()` and `isNone()` narrow both branches to those types. `if ($result->isErr()) { return $result; }` now passes PHPStan in a function that returns another success type.
- `Err::unwrap()` and `Err::expect()` attach an error that is a `\Throwable` as the `previous` exception. The message names its class and message, for example `Called unwrap on an Err value: JsonException: Syntax error`. Before, the message held the whole stack trace as text.
- Composer refuses `brimmar/phpstan-rustlike-result-extension` and `brimmar/phpstan-rustlike-option-extension` below 3.0. Those releases look for the old interface names and would report nothing.

### Fixed
- `Err::unwrap()`, `Err::expect()` and `Ok::expectErr()` throw the `RuntimeException` when the value is an array or an object without `__toString()`. The message names its type, for example `Called unwrap on an Err value: array`. Before, building the message raised a PHP warning or `Error` first.
- `Some::unzip()` returns two `None`s when the value is an array without keys `0` and `1`. Before, it raised "Undefined array key".

## [3.0.0] - 2026-10-13

### Changed
- `Ok::iter()` and `Some::iter()` yield the contained value once. An array is one item. Walk the elements with `foreach ($option->unwrapOr([]) as $item)`.
- `Err::flatten()` returns that same `Err`. A `Result` stored as the error stays wrapped.

## [2.0.0] - 2026-10-12

### Changed
- Option and Result now live in this package. `Brimmar\PhpOption` is included, and `brimmar/phpoption` is archived.
- `ok()`, `err()`, `transpose()`, `okOr()`, and `okOrElse()` return the real types. The class-name arguments are gone.
- Requires PHP 8.5. Methods that return an Option or a Result are marked `#[\NoDiscard]`.
- `Result<T, E>` is the success-or-failure type. There is no Either. That matches Rust and Effect 4, which replaced Either with Result.

## [1.1.0] - 2025-12-04

### Changed
- Tighter type hints on `Ok`, `Err`, and the `Result` interface.
- `ok()`, `err()`, and `transpose()` take `null` as the default class name, so they work without the PhpOption package.
- More precise PHPDoc annotations for IDEs and static analysis.

### Fixed
- `transpose()` accepts `null` parameters.
- The return type annotations of `flatten()`, `inspect()`, and `inspectErr()` are correct.

## [1.0.0] - 2024-08-04

First release.

### Added
- `Result` interface with `Ok` and `Err` implementations.
- Generic types for PHPStan.
- Pest test suite.
- README with usage examples.

[4.0.0]: https://github.com/brimmar/phpresult/compare/v3.0.12...v4.0.0
[3.0.0]: https://github.com/brimmar/phpresult/compare/v2.0.0...v3.0.0
[2.0.0]: https://github.com/brimmar/phpresult/compare/v1.1.4...v2.0.0
[1.1.0]: https://github.com/brimmar/phpresult/compare/v1.0.0...v1.1.0
[1.0.0]: https://github.com/brimmar/phpresult/releases/tag/v1.0.0
