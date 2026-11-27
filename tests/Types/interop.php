<?php

declare(strict_types=1);

/**
 * PHPStan reads this file during `composer analyse`. Every assertType() is checked there,
 * and every function has to pass level max. It is the check for the generics: how null,
 * exceptions, Option and Result convert into each other without an annotation.
 *
 * Nothing runs it.
 */

namespace Brimmar\PhpResult\Tests\Types;

use Brimmar\PhpOption\None;
use Brimmar\PhpOption\Option;
use Brimmar\PhpOption\Some;
use Brimmar\PhpResult\Err;
use Brimmar\PhpResult\Ok;
use Brimmar\PhpResult\Result;

use function PHPStan\Testing\assertType;

final class NotFound extends \Exception {}

final class Invalid extends \Exception {}

final readonly class User
{
    public function __construct(public string $name) {}
}

interface Users
{
    public function find(int $id): ?User;
}

/** @return Result<int, NotFound> */
function userId(string $name): Result
{
    return $name === '' ? new Err(new NotFound) : new Ok(strlen($name));
}

/** @return Result<string, Invalid> */
function label(int $id): Result
{
    return $id > 0 ? new Ok("#$id") : new Err(new Invalid);
}

function constructors(): void
{
    assertType('Brimmar\PhpResult\Ok<1, never>', new Ok(1));
    assertType("Brimmar\\PhpResult\\Err<never, 'missing'>", new Err('missing'));
    assertType('Brimmar\PhpOption\Some<1>', new Some(1));
    assertType('Brimmar\PhpOption\None<never>', new None);
}

function nullIn(Users $users): void
{
    assertType('Brimmar\PhpOption\Option<Brimmar\PhpResult\Tests\Types\User>', Option::fromNullable($users->find(1)));
    assertType(
        'Brimmar\PhpResult\Result<Brimmar\PhpResult\Tests\Types\User, Brimmar\PhpResult\Tests\Types\NotFound>',
        Option::fromNullable($users->find(1))->okOr(new NotFound),
    );
}

function nullOut(Users $users): void
{
    assertType('Brimmar\PhpResult\Tests\Types\User|null', Option::fromNullable($users->find(1))->unwrapOr(null));
    assertType('int|null', userId('ana')->unwrapOr(null));
}

function exceptionsIn(string $json): void
{
    assertType('Brimmar\PhpResult\Result<int<0, max>, Exception>', Result::try(fn () => strlen($json)));
    assertType('Brimmar\PhpResult\Result<int<0, max>, JsonException>', Result::try(fn () => strlen($json), \JsonException::class));
    assertType(
        'Brimmar\PhpResult\Result<int<0, max>, DomainException|JsonException>',
        Result::try(fn () => strlen($json), \JsonException::class, \DomainException::class),
    );
}

function exceptionsOut(): void
{
    assertType('int', userId('ana')->unwrapOrThrow());
    assertType('int', userId('ana')->unwrapOrElse(fn (NotFound $e) => throw $e));
}

/**
 * Steps with different error types chain, and the errors add up.
 *
 * @return Result<string, NotFound|Invalid>
 */
function chained(): Result
{
    $result = userId('ana')->andThen(fn (int $id) => label($id));
    assertType('Brimmar\PhpResult\Result<string, Brimmar\PhpResult\Tests\Types\Invalid|Brimmar\PhpResult\Tests\Types\NotFound>', $result);

    return $result;
}

/**
 * A narrower Result is returned where a wider one is declared.
 *
 * @return Result<string|int, NotFound|Invalid>
 */
function widened(): Result
{
    return label(1);
}

/**
 * An Err is returned early from a function with another success type.
 *
 * @return Result<string, NotFound|Invalid>
 */
function earlyReturn(): Result
{
    $id = userId('ana');
    if ($id->isErr()) {
        assertType('Brimmar\PhpResult\Err<never, Brimmar\PhpResult\Tests\Types\NotFound>', $id);

        return $id;
    }

    assertType('Brimmar\PhpResult\Ok<int, never>', $id);

    return label($id->unwrap());
}

/**
 * A None is returned early from a function with another Option type.
 *
 * @param  Option<int>  $id
 * @return Option<string>
 */
function earlyNone(Option $id): Option
{
    if ($id->isNone()) {
        assertType('Brimmar\PhpOption\None<never>', $id);

        return $id;
    }

    assertType('Brimmar\PhpOption\Some<int>', $id);

    return new Some("#{$id->unwrap()}");
}

/**
 * Result::gen() collects the error type of every step and the generator's return type.
 * A step written as yield from step() is typed with no extension.
 */
function generator(): void
{
    $result = Result::gen(function () {
        $id = yield from userId('ana')->step();
        assertType('int', $id);
        $label = yield from label($id)->step();
        assertType('string', $label);

        return [$id, $label];
    });

    assertType('Brimmar\PhpResult\Result<array{int, string}, Brimmar\PhpResult\Tests\Types\Invalid|Brimmar\PhpResult\Tests\Types\NotFound>', $result);
}

/**
 * A plain yield of a Result runs the same way. PHPStan reads its value as mixed.
 * brimmar/phpstan-rustlike-result-extension types it.
 */
function plainYield(): void
{
    $result = Result::gen(function () {
        yield userId('ana');
        yield label(1);

        return true;
    });

    assertType('Brimmar\PhpResult\Result<true, Brimmar\PhpResult\Tests\Types\Invalid|Brimmar\PhpResult\Tests\Types\NotFound>', $result);
}

function fallbacks(Users $users): void
{
    assertType('Brimmar\PhpResult\Result<int|string, Brimmar\PhpResult\Tests\Types\Invalid>', userId('ana')->or(label(1)));
    assertType("'none'|int", userId('ana')->unwrapOrElse(fn (NotFound $e) => 'none'));
    assertType('Brimmar\PhpOption\Option<0|Brimmar\PhpResult\Tests\Types\User>', Option::fromNullable($users->find(1))->or(new Some(0)));
}
