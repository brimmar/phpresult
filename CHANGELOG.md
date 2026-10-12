# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Changed
- Option and Result now live in this package. `Brimmar\PhpOption` is included, and `brimmar/phpoption` is archived.
- `ok()`, `err()`, `transpose()`, `okOr()`, and `okOrElse()` return the real types. The class-name arguments are gone.
- Requires PHP 8.5. Methods that return an Option or a Result are marked `#[\NoDiscard]`.
- `Result<T, E>` is the success-or-failure type. There is no Either. That matches Rust and Effect 4, which replaced Either with Result.

## [1.1.0] - 2025-12-04

### Changed
- Improved type hints and PHPStan compatibility across `Ok`, `Err`, and `Result` interface
- Updated `ok()`, `err()`, and `transpose()` methods to accept `null` as default parameter instead of hardcoded class names, providing better flexibility for users who don't use the PhpOption package
- Enhanced PHPDoc annotations for better IDE support and static analysis

### Fixed
- Fixed `transpose()` method to properly handle `null` parameters with null-safe checks
- Corrected return type annotations for `flatten()`, `inspect()`, and `inspectErr()` methods

## [1.0.0] - 2024-08-04

Initial release of PHP Result Type - A Rust-like Result Type for PHP.

### Added
- `Result` interface with `Ok` and `Err` implementations
- Comprehensive set of methods for error handling and value transformation
- Full PHPStan support with generic types
- Complete test coverage with Pest
- Documentation and usage examples

[Unreleased]: https://github.com/brimmar/phpresult/compare/v1.1.0...HEAD
[1.1.0]: https://github.com/brimmar/phpresult/compare/v1.0.0...v1.1.0
[1.0.0]: https://github.com/brimmar/phpresult/releases/tag/v1.0.0
