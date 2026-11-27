<?php

declare(strict_types=1);

use Brimmar\PhpOption\None;
use Brimmar\PhpOption\Option;
use Brimmar\PhpOption\Some;
use Brimmar\PhpResult\Err;
use Brimmar\PhpResult\Ok;

function some($value): Option
{
    return new Some($value);
}

function none(): Option
{
    return new None;
}

test('isSome returns true for Some and false for None', function (): void {
    expect(some(42)->isSome())->toBeTrue();
    expect(none()->isSome())->toBeFalse();
});

test('isNone returns true for None and false for Some', function (): void {
    expect(some(42)->isNone())->toBeFalse();
    expect(none()->isNone())->toBeTrue();
});

test('unwrap returns the value for Some and throws for None', function (): void {
    expect(some(42)->unwrap())->toBe(42);
    expect(fn () => none()->unwrap())->toThrow(RuntimeException::class, 'Called unwrap on a None value');
});

test('expect returns the value for Some and throws with custom message for None', function (): void {
    expect(some(42)->expect('Custom error'))->toBe(42);
    expect(fn () => none()->expect('Custom error'))->toThrow(RuntimeException::class, 'Custom error');
});

test('unwrapOr returns the value for Some and default for None', function (): void {
    expect(some(42)->unwrapOr(0))->toBe(42);
    expect(none()->unwrapOr(0))->toBe(0);
});

test('unwrapOrElse returns the value for Some and calls the function for None', function (): void {
    expect(some(42)->unwrapOrElse(fn () => 0))->toBe(42);
    expect(none()->unwrapOrElse(fn () => 0))->toBe(0);
});

test('map applies the function for Some and returns None for None', function (): void {
    $double = fn ($x) => $x * 2;
    expect(some(21)->map($double)->unwrap())->toBe(42);
    expect(none()->map($double))->toBeInstanceOf(None::class);
});

test('mapOr applies the function for Some and returns default for None', function (): void {
    $double = fn ($x) => $x * 2;
    expect(some(21)->mapOr(0, $double))->toBe(42);
    expect(none()->mapOr(0, $double))->toBe(0);
});

test('mapOrElse applies the function for Some and calls default function for None', function (): void {
    $double = fn ($x) => $x * 2;
    $default = fn () => 0;
    expect(some(21)->mapOrElse($default, $double))->toBe(42);
    expect(none()->mapOrElse($default, $double))->toBe(0);
});

test('and returns the second option if self is Some, otherwise None', function (): void {
    expect(some(2)->and(some(3)))->toEqual(some(3));
    expect(some(2)->and(none()))->toEqual(none());
    expect(none()->and(some(3)))->toEqual(none());
});

test('andThen returns the result of calling f if self is Some, otherwise None', function (): void {
    $square = fn ($x) => some($x * $x);
    expect(some(2)->andThen($square))->toEqual(some(4));
    expect(none()->andThen($square))->toEqual(none());
});

test('filter returns Some if the predicate returns true, otherwise None', function (): void {
    $isEven = fn ($x) => $x % 2 === 0;
    expect(some(2)->filter($isEven))->toEqual(some(2));
    expect(some(3)->filter($isEven))->toEqual(none());
    expect(none()->filter($isEven))->toEqual(none());
});

test('or returns self if it is Some, otherwise returns optb', function (): void {
    expect(some(2)->or(none()))->toEqual(some(2));
    expect(none()->or(some(3)))->toEqual(some(3));
    expect(none()->or(none()))->toEqual(none());
});

test('orElse returns self if it is Some, otherwise calls f', function (): void {
    $nobody = fn () => none();
    $somebody = fn () => some(3);
    expect(some(2)->orElse($somebody))->toEqual(some(2));
    expect(none()->orElse($somebody))->toEqual(some(3));
    expect(none()->orElse($nobody))->toEqual(none());
});

test('xor returns Some if exactly one of self and opt is Some, otherwise None', function (): void {
    expect(some(2)->xor(none()))->toEqual(some(2));
    expect(none()->xor(some(3)))->toEqual(some(3));
    expect(some(2)->xor(some(3)))->toEqual(none());
    expect(none()->xor(none()))->toEqual(none());
});

test('zip combines two Options into one Option with a tuple of both values', function (): void {
    expect(some(1)->zip(some(2)))->toEqual(some([1, 2]));
    expect(some(1)->zip(none()))->toEqual(none());
    expect(none()->zip(some(2)))->toEqual(none());
});

test('zipWith combines two Options into one Option using a specified function', function (): void {
    $add = fn ($x, $y) => $x + $y;
    expect(some(1)->zipWith(some(2), $add))->toEqual(some(3));
    expect(some(1)->zipWith(none(), $add))->toEqual(none());
    expect(none()->zipWith(some(2), $add))->toEqual(none());
});

test('flatten removes one level of nesting', function (): void {
    expect(some(some(1))->flatten())->toEqual(some(1));
    expect(some(none())->flatten())->toEqual(none());
    expect(none()->flatten())->toEqual(none());
});

test('transpose exchanges Option and Result', function (): void {
    expect(some(new Ok(1))->transpose())->toEqual(new Ok(some(1)));
    expect(some(new Err('error'))->transpose())->toEqual(new Err('error'));
    expect(none()->transpose())->toEqual(new Ok(none()));
});

test('match executes the correct function based on the Option type', function (): void {
    $matchSome = fn ($x) => "Value is $x";
    $matchNone = fn () => 'No value';

    expect(
        some(1)->match(
            Some: $matchSome,
            None: $matchNone,
        ),
    )
        ->toBe('Value is 1');
    expect(
        none()->match(
            Some: $matchSome,
            None: $matchNone,
        ),
    )
        ->toBe('No value');
});

test('okOr transforms Some into Ok and None into Err with a provided error value', function (): void {
    expect(some(1)->okOr('error'))->toBeInstanceOf(Ok::class);
    expect(some(1)->okOr('error')->unwrap())->toBe(1);
    expect(none()->okOr('error'))->toBeInstanceOf(Err::class);
    expect(none()->okOr('error')->unwrapErr())->toBe('error');
});

test('okOrElse transforms Some into Ok and None into Err with a provided error function', function (): void {
    $errorFn = fn () => 'error';
    expect(some(1)->okOrElse($errorFn))->toBeInstanceOf(Ok::class);
    expect(some(1)->okOrElse($errorFn)->unwrap())->toBe(1);
    expect(none()->okOrElse($errorFn))->toBeInstanceOf(Err::class);
    expect(none()->okOrElse($errorFn)->unwrapErr())->toBe('error');
});

test('iter returns an iterator with one element for Some and empty for None', function (): void {
    expect(iterator_to_array(some(42)->iter()))->toBe([42]);
    expect(iterator_to_array(some([1, 2])->iter()))->toBe([[1, 2]]);
    expect(iterator_to_array(some([])->iter()))->toBe([[]]);
    expect(iterator_to_array(none()->iter()))->toBe([]);
});

test('inspect calls the provided closure if the option is Some', function (): void {
    $called = false;
    $inspect = function ($value) use (&$called): void {
        $called = true;
        expect($value)->toBe(42);
    };

    expect(some(42)->inspect($inspect)->unwrap())->toBe(42);
    expect($called)->toBeTrue();

    $called = false;
    expect(none()->inspect($inspect))->toBeInstanceOf(None::class);
    expect($called)->toBeFalse();
});

test('unzip of a Some that does not hold a pair returns two Nones', function (): void {
    [$a, $b] = some(['x' => 1])->unzip();
    expect($a)->toBeInstanceOf(None::class);
    expect($b)->toBeInstanceOf(None::class);
});

// null in and out
test('fromNullable turns null into None and every other value into Some', function (): void {
    expect(Option::fromNullable(null))->toBeInstanceOf(None::class);
    expect(Option::fromNullable(42)->unwrap())->toBe(42);

    foreach ([0, '', false, []] as $falsy) {
        expect(Option::fromNullable($falsy)->unwrap())->toBe($falsy);
    }
});

test('a nullable goes through Option and Result and comes back as null or an exception', function (): void {
    $notFound = new DomainException('no user');

    expect(Option::fromNullable(null)->unwrapOr(null))->toBeNull();
    expect(Option::fromNullable('ana')->okOr($notFound)->unwrapOrThrow())->toBe('ana');
    expect(fn () => Option::fromNullable(null)->okOr($notFound)->unwrapOrThrow())->toThrow(DomainException::class, 'no user');
});
