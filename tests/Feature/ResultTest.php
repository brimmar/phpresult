<?php

use Brimmar\PhpResult\Err;
use Brimmar\PhpResult\Ok;

class Some
{
    public function __construct(private mixed $value)
    {
    }

    public function unwrap()
    {
        return $this->value;
    }
}

class None
{
}

test('Ok is ok', function () {
    $ok = new Ok(42);
    expect($ok->isOk())->toBeTrue();
    expect($ok->isErr())->toBeFalse();
});

test('Err is err', function () {
    $err = new Err('error');
    expect($err->isErr())->toBeTrue();
    expect($err->isOk())->toBeFalse();
});

test('Ok unwrap returns value', function () {
    $ok = new Ok(42);
    expect($ok->unwrap())->toBe(42);
});

test('Err unwrap throws exception', function () {
    $err = new Err('error');
    expect(fn () => $err->unwrap())->toThrow(RuntimeException::class);
});

test('Ok expect returns value', function () {
    $ok = new Ok(42);
    expect($ok->expect('Should not throw'))->toBe(42);
});

test('Err expect throws exception with custom message', function () {
    $err = new Err('error');
    expect(fn () => $err->expect('Custom message'))
        ->toThrow(RuntimeException::class, 'Custom message: error');
});

test('Ok unwrapOr returns value', function () {
    $ok = new Ok(42);
    expect($ok->unwrapOr(10))->toBe(42);
});

test('Err unwrapOr returns default', function () {
    $err = new Err('error');
    expect($err->unwrapOr(10))->toBe(10);
});

test('Ok unwrapErr throws exception', function () {
    $ok = new Ok(42);
    expect(fn () => $ok->unwrapErr())->toThrow(RuntimeException::class);
});

test('Err unwrapErr returns error', function () {
    $err = new Err('error');
    expect($err->unwrapErr())->toBe('error');
});

test('Ok map applies function', function () {
    $ok = new Ok(42);
    $mapped = $ok->map(fn ($x) => $x * 2);
    expect($mapped)->toBeInstanceOf(Ok::class);
    expect($mapped->unwrap())->toBe(84);
});

test('Err map returns Err', function () {
    $err = new Err('error');
    $mapped = $err->map(fn ($x) => $x * 2);
    expect($mapped)->toBeInstanceOf(Err::class);
    expect($mapped->unwrapErr())->toBe('error');
});

test('Ok mapErr returns Ok', function () {
    $ok = new Ok(42);
    $mapped = $ok->mapErr(fn ($e) => 'new '.$e);
    expect($mapped)->toBeInstanceOf(Ok::class);
    expect($mapped->unwrap())->toBe(42);
});

test('Err mapErr applies function', function () {
    $err = new Err('error');
    $mapped = $err->mapErr(fn ($e) => 'new '.$e);
    expect($mapped)->toBeInstanceOf(Err::class);
    expect($mapped->unwrapErr())->toBe('new error');
});

test('Ok match calls ok function', function () {
    $ok = new Ok(42);
    $result = $ok->match(
        Ok: fn ($x) => "Value is $x",
        Err: fn ($e) => "Error: $e",
    );
    expect($result)->toBe('Value is 42');
});

test('Err match calls err function', function () {
    $err = new Err('oops');
    $result = $err->match(
        Ok: fn ($x) => "Value is $x",
        Err: fn ($e) => "Error: $e",
    );
    expect($result)->toBe('Error: oops');
});

// isOkAnd and isErrAnd tests
test('Ok isOkAnd returns true for matching predicate', function () {
    $ok = new Ok(42);
    expect($ok->isOkAnd(fn ($x) => $x > 40))->toBeTrue();
    expect($ok->isOkAnd(fn ($x) => $x < 40))->toBeFalse();
});

test('Err isOkAnd always returns false', function () {
    $err = new Err('error');
    expect($err->isOkAnd(fn ($x) => true))->toBeFalse();
});

test('Ok isErrAnd always returns false', function () {
    $ok = new Ok(42);
    expect($ok->isErrAnd(fn ($e) => true))->toBeFalse();
});

test('Err isErrAnd returns true for matching predicate', function () {
    $err = new Err('error');
    expect($err->isErrAnd(fn ($e) => $e === 'error'))->toBeTrue();
    expect($err->isErrAnd(fn ($e) => $e === 'different error'))->toBeFalse();
});

// ok and err tests
test('Ok ok returns Some', function () {
    $ok = new Ok(42);
    $some = $ok->ok(Some::class);
    expect($some)->toBeInstanceOf(Some::class);
    expect($some->unwrap())->toBe(42);
});

test('Err ok returns None', function () {
    $err = new Err('error');
    $none = $err->ok(None::class);
    expect($none)->toBeInstanceOf(None::class);
});

test('Ok err returns None', function () {
    $ok = new Ok(42);
    $none = $ok->err(None::class);
    expect($none)->toBeInstanceOf(None::class);
});

test('Err err returns Some', function () {
    $err = new Err('error');
    $some = $err->err(Some::class);
    expect($some)->toBeInstanceOf(Some::class);
    expect($some->unwrap())->toBe('error');
});

// flatten tests
test('Ok flatten with nested Ok returns inner Ok', function () {
    $ok = new Ok(new Ok(42));
    $flattened = $ok->flatten();
    expect($flattened)->toBeInstanceOf(Ok::class);
    expect($flattened->unwrap())->toBe(42);
});

test('Ok flatten without nested Result returns original Ok', function () {
    $ok = new Ok(42);
    $flattened = $ok->flatten();
    expect($flattened)->toBe($ok);
});

test('Err flatten returns original Err', function () {
    $err = new Err('error');
    $flattened = $err->flatten();
    expect($flattened)->toBe($err);
});

// intoErr and intoOk tests
test('Ok intoOk returns value', function () {
    $ok = new Ok(42);
    expect($ok->intoOk())->toBe(42);
});

test('Ok intoErr throws exception', function () {
    $ok = new Ok(42);
    expect(fn () => $ok->intoErr())->toThrow(RuntimeException::class);
});

test('Err intoErr returns error', function () {
    $err = new Err('error');
    expect($err->intoErr())->toBe('error');
});

test('Err intoOk throws exception', function () {
    $err = new Err('error');
    expect(fn () => $err->intoOk())->toThrow(RuntimeException::class);
});

// iter tests
test('Ok iter returns iterator with value', function () {
    $ok = new Ok(42);
    $iter = $ok->iter();
    expect($iter)->toBeInstanceOf(Iterator::class);
    expect(iterator_to_array($iter))->toBe([42]);
});

test('Err iter returns empty iterator', function () {
    $err = new Err('error');
    $iter = $err->iter();
    expect($iter)->toBeInstanceOf(Iterator::class);
    expect(iterator_to_array($iter))->toBe([]);
});

// unwrapOrElse tests
test('Ok unwrapOrElse returns value', function () {
    $ok = new Ok(42);
    expect($ok->unwrapOrElse(fn ($e) => 0))->toBe(42);
});

test('Err unwrapOrElse calls function', function () {
    $err = new Err('error');
    expect($err->unwrapOrElse(fn ($e) => strlen($e)))->toBe(5);
});

// mapOr and mapOrElse tests
test('Ok mapOr applies map function', function () {
    $ok = new Ok(42);
    expect($ok->mapOr(0, fn ($x) => $x * 2))->toBe(84);
});

test('Err mapOr returns default', function () {
    $err = new Err('error');
    expect($err->mapOr(0, fn ($x) => $x * 2))->toBe(0);
});

test('Ok mapOrElse applies map function', function () {
    $ok = new Ok(42);
    expect($ok->mapOrElse(fn ($e) => 0, fn ($x) => $x * 2))->toBe(84);
});

test('Err mapOrElse applies default function', function () {
    $err = new Err('error');
    expect($err->mapOrElse(fn ($e) => strlen($e), fn ($x) => $x * 2))->toBe(5);
});

// inspect and inspectErr tests
test('Ok inspect calls function and returns self', function () {
    $ok = new Ok(42);
    $called = false;
    $result = $ok->inspect(function ($x) use (&$called) {
        $called = true;
        expect($x)->toBe(42);
    });
    expect($called)->toBeTrue();
    expect($result)->toBe($ok);
});

test('Err inspect does not call function and returns self', function () {
    $err = new Err('error');
    $called = false;
    $result = $err->inspect(function ($x) use (&$called) {
        $called = true;
    });
    expect($called)->toBeFalse();
    expect($result)->toBe($err);
});

test('Ok inspectErr does not call function and returns self', function () {
    $ok = new Ok(42);
    $called = false;
    $result = $ok->inspectErr(function ($e) use (&$called) {
        $called = true;
    });
    expect($called)->toBeFalse();
    expect($result)->toBe($ok);
});

test('Err inspectErr calls function and returns self', function () {
    $err = new Err('error');
    $called = false;
    $result = $err->inspectErr(function ($e) use (&$called) {
        $called = true;
        expect($e)->toBe('error');
    });
    expect($called)->toBeTrue();
    expect($result)->toBe($err);
});

// and and andThen tests
test('Ok and returns second Result', function () {
    $ok1 = new Ok(42);
    $ok2 = new Ok(84);
    $err = new Err('error');
    expect($ok1->and($ok2))->toBe($ok2);
    expect($ok1->and($err))->toBe($err);
});

test('Err and returns Err', function () {
    $err = new Err('error');
    $ok = new Ok(42);
    expect($err->and($ok))->toBe($err);
    expect($err->and(new Err('other error')))->toBe($err);
});

test('Ok andThen applies function', function () {
    $ok = new Ok(42);
    $result = $ok->andThen(fn ($x) => new Ok($x * 2));
    expect($result)->toBeInstanceOf(Ok::class);
    expect($result->unwrap())->toBe(84);
});

test('Err andThen returns Err', function () {
    $err = new Err('error');
    $result = $err->andThen(fn ($x) => new Ok($x * 2));
    expect($result)->toBe($err);
});

// or and orElse tests
test('Ok or returns Ok', function () {
    $ok = new Ok(42);
    $other = new Ok(84);
    expect($ok->or($other))->toBe($ok);
});

test('Err or returns second Result', function () {
    $err = new Err('error');
    $ok = new Ok(42);
    $otherErr = new Err('other error');
    expect($err->or($ok))->toBe($ok);
    expect($err->or($otherErr))->toBe($otherErr);
});

test('Ok orElse returns Ok', function () {
    $ok = new Ok(42);
    $result = $ok->orElse(fn ($e) => new Ok(0));
    expect($result)->toBe($ok);
});

test('Err orElse applies function', function () {
    $err = new Err('error');
    $result = $err->orElse(fn ($e) => new Ok(strlen($e)));
    expect($result)->toBeInstanceOf(Ok::class);
    expect($result->unwrap())->toBe(5);
});

// transpose tests
test('Ok transpose with Some returns Some of Ok', function () {
    $ok = new Ok(new Some(42));
    $result = $ok->transpose(None::class, Some::class);
    expect($result)->toBeInstanceOf(Some::class);
    $inner = $result->unwrap();
    expect($inner)->toBeInstanceOf(Ok::class);
    expect($inner->unwrap())->toBe(42);
});

test('Ok transpose with None returns None', function () {
    $ok = new Ok(new None());
    $result = $ok->transpose(None::class, Some::class);
    expect($result)->toBeInstanceOf(None::class);
});

test('Err transpose returns Some of Err', function () {
    $err = new Err('error');
    $result = $err->transpose(None::class, Some::class);
    expect($result)->toBeInstanceOf(Some::class);
    $inner = $result->unwrap();
    expect($inner)->toBeInstanceOf(Err::class);
    expect($inner->unwrapErr())->toBe('error');
});
