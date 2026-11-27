<?php

declare(strict_types=1);

use Brimmar\PhpOption\None;
use Brimmar\PhpOption\Some;
use Brimmar\PhpResult\Err;
use Brimmar\PhpResult\Ok;
use Brimmar\PhpResult\Result;

test('Ok is ok', function (): void {
    $ok = new Ok(42);
    expect($ok->isOk())->toBeTrue();
    expect($ok->isErr())->toBeFalse();
});

test('Err is err', function (): void {
    $err = new Err('error');
    expect($err->isErr())->toBeTrue();
    expect($err->isOk())->toBeFalse();
});

test('Ok unwrap returns value', function (): void {
    $ok = new Ok(42);
    expect($ok->unwrap())->toBe(42);
});

test('Err unwrap throws exception', function (): void {
    $err = new Err('error');
    expect(fn () => $err->unwrap())->toThrow(RuntimeException::class);
});

test('Err unwrap names a non-string error instead of failing to build the message', function (): void {
    expect(fn () => (new Err(['code' => 1]))->unwrap())->toThrow(RuntimeException::class, 'Called unwrap on an Err value: array');
    expect(fn () => (new Err(new stdClass))->expect('boom'))->toThrow(RuntimeException::class, 'boom: stdClass');
    expect(fn () => (new Ok([1]))->expectErr('boom'))->toThrow(RuntimeException::class, 'boom: array');
});

test('Ok expect returns value', function (): void {
    $ok = new Ok(42);
    expect($ok->expect('Should not throw'))->toBe(42);
});

test('Err expect throws exception with custom message', function (): void {
    $err = new Err('error');
    expect(fn () => $err->expect('Custom message'))
        ->toThrow(RuntimeException::class, 'Custom message: error');
});

test('Ok unwrapOr returns value', function (): void {
    $ok = new Ok(42);
    expect($ok->unwrapOr(10))->toBe(42);
});

test('Err unwrapOr returns default', function (): void {
    $err = new Err('error');
    expect($err->unwrapOr(10))->toBe(10);
});

test('Ok unwrapErr throws exception', function (): void {
    $ok = new Ok(42);
    expect(fn () => $ok->unwrapErr())->toThrow(RuntimeException::class);
});

test('Err unwrapErr returns error', function (): void {
    $err = new Err('error');
    expect($err->unwrapErr())->toBe('error');
});

test('Ok map applies function', function (): void {
    $ok = new Ok(42);
    $mapped = $ok->map(fn ($x) => $x * 2);
    expect($mapped)->toBeInstanceOf(Ok::class);
    expect($mapped->unwrap())->toBe(84);
});

test('Err map returns Err', function (): void {
    $err = new Err('error');
    $mapped = $err->map(fn ($x) => $x * 2);
    expect($mapped)->toBeInstanceOf(Err::class);
    expect($mapped->unwrapErr())->toBe('error');
});

test('Ok mapErr returns Ok', function (): void {
    $ok = new Ok(42);
    $mapped = $ok->mapErr(fn ($e) => 'new '.$e);
    expect($mapped)->toBeInstanceOf(Ok::class);
    expect($mapped->unwrap())->toBe(42);
});

test('Err mapErr applies function', function (): void {
    $err = new Err('error');
    $mapped = $err->mapErr(fn ($e) => 'new '.$e);
    expect($mapped)->toBeInstanceOf(Err::class);
    expect($mapped->unwrapErr())->toBe('new error');
});

test('Ok match calls ok function', function (): void {
    $ok = new Ok(42);
    $result = $ok->match(
        Ok: fn ($x) => "Value is $x",
        Err: fn ($e) => "Error: $e",
    );
    expect($result)->toBe('Value is 42');
});

test('Err match calls err function', function (): void {
    $err = new Err('oops');
    $result = $err->match(
        Ok: fn ($x) => "Value is $x",
        Err: fn ($e) => "Error: $e",
    );
    expect($result)->toBe('Error: oops');
});

// isOkAnd and isErrAnd tests
test('Ok isOkAnd returns true for matching predicate', function (): void {
    $ok = new Ok(42);
    expect($ok->isOkAnd(fn ($x) => $x > 40))->toBeTrue();
    expect($ok->isOkAnd(fn ($x) => $x < 40))->toBeFalse();
});

test('Err isOkAnd always returns false', function (): void {
    $err = new Err('error');
    expect($err->isOkAnd(fn ($x) => true))->toBeFalse();
});

test('Ok isErrAnd always returns false', function (): void {
    $ok = new Ok(42);
    expect($ok->isErrAnd(fn ($e) => true))->toBeFalse();
});

test('Err isErrAnd returns true for matching predicate', function (): void {
    $err = new Err('error');
    expect($err->isErrAnd(fn ($e) => $e === 'error'))->toBeTrue();
    expect($err->isErrAnd(fn ($e) => $e === 'different error'))->toBeFalse();
});

// ok and err tests
test('Ok ok returns Some', function (): void {
    $ok = new Ok(42);
    $some = $ok->ok();
    expect($some)->toBeInstanceOf(Some::class);
    expect($some->unwrap())->toBe(42);
});

test('Err ok returns None', function (): void {
    $err = new Err('error');
    $none = $err->ok();
    expect($none)->toBeInstanceOf(None::class);
});

test('Ok err returns None', function (): void {
    $ok = new Ok(42);
    $none = $ok->err();
    expect($none)->toBeInstanceOf(None::class);
});

test('Err err returns Some', function (): void {
    $err = new Err('error');
    $some = $err->err();
    expect($some)->toBeInstanceOf(Some::class);
    expect($some->unwrap())->toBe('error');
});

// flatten tests
test('Ok flatten with nested Ok returns inner Ok', function (): void {
    $ok = new Ok(new Ok(42));
    $flattened = $ok->flatten();
    expect($flattened)->toBeInstanceOf(Ok::class);
    expect($flattened->unwrap())->toBe(42);
});

test('Ok flatten without nested Result returns original Ok', function (): void {
    $ok = new Ok(42);
    $flattened = $ok->flatten();
    expect($flattened)->toBe($ok);
});

test('Err flatten returns original Err', function (): void {
    $err = new Err('error');
    $flattened = $err->flatten();
    expect($flattened)->toBe($err);
});

test('Err flatten keeps a Result stored as the error', function (): void {
    $err = new Err(new Ok(1));
    expect($err->flatten())->toBe($err);
});

// intoErr and intoOk tests
test('Ok intoOk returns value', function (): void {
    $ok = new Ok(42);
    expect($ok->intoOk())->toBe(42);
});

test('Ok intoErr throws exception', function (): void {
    $ok = new Ok(42);
    expect(fn () => $ok->intoErr())->toThrow(RuntimeException::class);
});

test('Err intoErr returns error', function (): void {
    $err = new Err('error');
    expect($err->intoErr())->toBe('error');
});

test('Err intoOk throws exception', function (): void {
    $err = new Err('error');
    expect(fn () => $err->intoOk())->toThrow(RuntimeException::class);
});

// iter tests
test('Ok iter returns iterator with value', function (): void {
    $ok = new Ok(42);
    $iter = $ok->iter();
    expect($iter)->toBeInstanceOf(Iterator::class);
    expect(iterator_to_array($iter))->toBe([42]);
    expect(iterator_to_array((new Ok([1, 2]))->iter()))->toBe([[1, 2]]);
    expect(iterator_to_array((new Ok([]))->iter()))->toBe([[]]);
});

test('Err iter returns empty iterator', function (): void {
    $err = new Err('error');
    $iter = $err->iter();
    expect($iter)->toBeInstanceOf(Iterator::class);
    expect(iterator_to_array($iter))->toBe([]);
});

// unwrapOrElse tests
test('Ok unwrapOrElse returns value', function (): void {
    $ok = new Ok(42);
    expect($ok->unwrapOrElse(fn ($e) => 0))->toBe(42);
});

test('Err unwrapOrElse calls function', function (): void {
    $err = new Err('error');
    expect($err->unwrapOrElse(fn ($e) => strlen($e)))->toBe(5);
});

// mapOr and mapOrElse tests
test('Ok mapOr applies map function', function (): void {
    $ok = new Ok(42);
    expect($ok->mapOr(0, fn ($x) => $x * 2))->toBe(84);
});

test('Err mapOr returns default', function (): void {
    $err = new Err('error');
    expect($err->mapOr(0, fn ($x) => $x * 2))->toBe(0);
});

test('Ok mapOrElse applies map function', function (): void {
    $ok = new Ok(42);
    expect($ok->mapOrElse(fn ($e) => 0, fn ($x) => $x * 2))->toBe(84);
});

test('Err mapOrElse applies default function', function (): void {
    $err = new Err('error');
    expect($err->mapOrElse(fn ($e) => strlen($e), fn ($x) => $x * 2))->toBe(5);
});

// inspect and inspectErr tests
test('Ok inspect calls function and returns self', function (): void {
    $ok = new Ok(42);
    $called = false;
    $result = $ok->inspect(function ($x) use (&$called): void {
        $called = true;
        expect($x)->toBe(42);
    });
    expect($called)->toBeTrue();
    expect($result)->toBe($ok);
});

test('Err inspect does not call function and returns self', function (): void {
    $err = new Err('error');
    $called = false;
    $result = $err->inspect(function ($x) use (&$called): void {
        $called = true;
    });
    expect($called)->toBeFalse();
    expect($result)->toBe($err);
});

test('Ok inspectErr does not call function and returns self', function (): void {
    $ok = new Ok(42);
    $called = false;
    $result = $ok->inspectErr(function ($e) use (&$called): void {
        $called = true;
    });
    expect($called)->toBeFalse();
    expect($result)->toBe($ok);
});

test('Err inspectErr calls function and returns self', function (): void {
    $err = new Err('error');
    $called = false;
    $result = $err->inspectErr(function ($e) use (&$called): void {
        $called = true;
        expect($e)->toBe('error');
    });
    expect($called)->toBeTrue();
    expect($result)->toBe($err);
});

// and and andThen tests
test('Ok and returns second Result', function (): void {
    $ok1 = new Ok(42);
    $ok2 = new Ok(84);
    $err = new Err('error');
    expect($ok1->and($ok2))->toBe($ok2);
    expect($ok1->and($err))->toBe($err);
});

test('Err and returns Err', function (): void {
    $err = new Err('error');
    $ok = new Ok(42);
    expect($err->and($ok))->toBe($err);
    expect($err->and(new Err('other error')))->toBe($err);
});

test('Ok andThen applies function', function (): void {
    $ok = new Ok(42);
    $result = $ok->andThen(fn ($x) => new Ok($x * 2));
    expect($result)->toBeInstanceOf(Ok::class);
    expect($result->unwrap())->toBe(84);
});

test('Err andThen returns Err', function (): void {
    $err = new Err('error');
    $result = $err->andThen(fn ($x) => new Ok($x * 2));
    expect($result)->toBe($err);
});

// or and orElse tests
test('Ok or returns Ok', function (): void {
    $ok = new Ok(42);
    $other = new Ok(84);
    expect($ok->or($other))->toBe($ok);
});

test('Err or returns second Result', function (): void {
    $err = new Err('error');
    $ok = new Ok(42);
    $otherErr = new Err('other error');
    expect($err->or($ok))->toBe($ok);
    expect($err->or($otherErr))->toBe($otherErr);
});

test('Ok orElse returns Ok', function (): void {
    $ok = new Ok(42);
    $result = $ok->orElse(fn ($e) => new Ok(0));
    expect($result)->toBe($ok);
});

test('Err orElse applies function', function (): void {
    $err = new Err('error');
    $result = $err->orElse(fn ($e) => new Ok(strlen($e)));
    expect($result)->toBeInstanceOf(Ok::class);
    expect($result->unwrap())->toBe(5);
});

// transpose tests
test('Ok transpose with Some returns Some of Ok', function (): void {
    $ok = new Ok(new Some(42));
    $result = $ok->transpose();
    expect($result)->toBeInstanceOf(Some::class);
    $inner = $result->unwrap();
    expect($inner)->toBeInstanceOf(Ok::class);
    expect($inner->unwrap())->toBe(42);
});

test('Ok transpose with None returns None', function (): void {
    $ok = new Ok(new None);
    $result = $ok->transpose();
    expect($result)->toBeInstanceOf(None::class);
});

test('Err transpose returns Some of Err', function (): void {
    $err = new Err('error');
    $result = $err->transpose();
    expect($result)->toBeInstanceOf(Some::class);
    $inner = $result->unwrap();
    expect($inner)->toBeInstanceOf(Err::class);
    expect($inner->unwrapErr())->toBe('error');
});

// exceptions in: Result::try
test('try returns the value in Ok', function (): void {
    expect(Result::try(fn () => 42)->unwrap())->toBe(42);
});

test('try turns a thrown exception into Err holding that exception', function (): void {
    $exception = new RuntimeException('boom');
    $result = Result::try(fn () => throw $exception);

    expect($result)->toBeInstanceOf(Err::class);
    expect($result->unwrapErr())->toBe($exception);
});

test('try lets an Error through unless it is listed', function (): void {
    expect(fn () => Result::try(fn () => throw new TypeError('bug')))->toThrow(TypeError::class);
    expect(Result::try(fn () => throw new TypeError('bug'), Throwable::class)->isErr())->toBeTrue();
});

test('try catches only the listed classes and their subclasses', function (): void {
    $decode = fn (string $json): mixed => json_decode($json, flags: JSON_THROW_ON_ERROR);

    expect(Result::try(fn () => $decode('{'), JsonException::class)->unwrapErr())->toBeInstanceOf(JsonException::class);
    expect(Result::try(fn () => throw new LengthException('long'), LogicException::class)->isErr())->toBeTrue();
    expect(Result::try(fn () => throw new DomainException('no'), JsonException::class, DomainException::class)->isErr())->toBeTrue();
    expect(fn () => Result::try(fn () => throw new RuntimeException('other'), JsonException::class, DomainException::class))
        ->toThrow(RuntimeException::class, 'other');
});

// early return: Result::gen
test('gen hands each Ok value back and wraps the return value in Ok', function (): void {
    $result = Result::gen(function () {
        $a = yield new Ok(40);
        $b = yield new Ok(2);

        return $a + $b;
    });

    expect($result->unwrap())->toBe(42);
});

test('gen returns the first Err and runs nothing after it', function (): void {
    $err = new Err('stop');
    $reached = false;
    $cleaned = false;

    $result = Result::gen(function () use ($err, &$reached, &$cleaned) {
        try {
            $a = yield new Ok(1);
            yield $err;
            $reached = true;

            return $a;
        } finally {
            $cleaned = true;
        }
    });

    expect($result)->toBe($err);
    expect($reached)->toBeFalse();
    expect($cleaned)->toBeTrue();
});

test('gen without a yield returns Ok', function (): void {
    $result = Result::gen(function () {
        return 'done';
        yield;
    });

    expect($result->unwrap())->toBe('done');
});

test('gen takes typed steps written as yield from step()', function (): void {
    $result = Result::gen(function () {
        $a = yield from (new Ok(40))->step();
        $b = yield from (new Ok(2))->step();

        return $a + $b;
    });

    expect($result->unwrap())->toBe(42);
});

test('step() ends gen with the Err and runs nothing after it', function (): void {
    $err = new Err('stop');
    $reached = false;

    $result = Result::gen(function () use ($err, &$reached) {
        $a = yield from (new Ok(1))->step();
        yield from $err->step();
        $reached = true;

        return $a;
    });

    expect($result)->toBe($err);
    expect($reached)->toBeFalse();
});

test('step() says how to use it when something other than gen resumes it', function (): void {
    $steps = (new Err('stop'))->step();
    $steps->current();

    expect(fn () => $steps->next())->toThrow(LogicException::class, 'yield from $result->step()');
});

// exceptions out: unwrapOrThrow
test('unwrapOrThrow returns the Ok value', function (): void {
    expect((new Ok(42))->unwrapOrThrow())->toBe(42);
});

test('unwrapOrThrow throws the error itself when it is a Throwable', function (): void {
    $exception = new DomainException('not found');

    try {
        (new Err($exception))->unwrapOrThrow();
    } catch (DomainException $caught) {
        expect($caught)->toBe($exception);

        return;
    }

    test()->fail('unwrapOrThrow did not throw');
});

test('unwrapOrThrow throws a RuntimeException for an error that is not a Throwable', function (): void {
    expect(fn () => (new Err('boom'))->unwrapOrThrow())
        ->toThrow(RuntimeException::class, 'Called unwrapOrThrow on an Err value: boom');
});

test('unwrap and expect keep a Throwable error as the previous exception', function (): void {
    $cause = new LogicException('bad state');

    try {
        (new Err($cause))->unwrap();
    } catch (RuntimeException $e) {
        expect($e->getMessage())->toBe('Called unwrap on an Err value: LogicException: bad state');
        expect($e->getPrevious())->toBe($cause);
    }

    try {
        (new Err($cause))->expect('loading config');
    } catch (RuntimeException $e) {
        expect($e->getMessage())->toBe('loading config: LogicException: bad state');
        expect($e->getPrevious())->toBe($cause);
    }

    expect(fn () => (new Err('plain'))->unwrap())->toThrow(fn (RuntimeException $e) => expect($e->getPrevious())->toBeNull());
});
