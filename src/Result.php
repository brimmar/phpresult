<?php

declare(strict_types=1);

namespace Brimmar\PhpResult;

use Brimmar\PhpOption\Option;
use Generator;
use Iterator;

/**
 * A value that has already succeeded or failed.
 *
 * `Ok` holds `T`. `Err` holds `E`. This is Rust's `Result<T, E>` and Effect 4's
 * `Result<A, E>` (the type that replaced `Either`). It is eager data. A program
 * that still has requirements, defects, or interruption is an `Effect`, and the
 * value you get after running one is an `Exit`.
 *
 * @template-covariant T
 * @template-covariant E
 */
abstract class Result
{
    /**
     * Calls `$fn` and returns its value in `Ok`. A thrown exception of a listed
     * class becomes `Err`. Anything else is rethrown.
     *
     * With no class listed it catches `\Exception`. An `\Error`, such as a
     * `TypeError`, is a bug in the code and keeps propagating.
     *
     * @template U
     * @template F of \Throwable
     *
     * @param  callable(): U  $fn
     * @param  class-string<F>  $catch
     * @param  class-string<F>  ...$orCatch
     * @return Result<U, F>
     */
    #[\NoDiscard('ignoring it drops a success or a failure')]
    public static function try(callable $fn, string $catch = \Exception::class, string ...$orCatch): self
    {
        try {
            return new Ok($fn());
        } catch (\Throwable $e) {
            foreach ([$catch, ...$orCatch] as $class) {
                if ($e instanceof $class) {
                    return new Err($e);
                }
            }

            throw $e;
        }
    }

    /**
     * Runs a generator where every step is a Result. `Ok` hands its value back
     * to the generator. The first `Err` stops the generator and is returned.
     * The generator's return value comes back in `Ok`.
     *
     * Write a step as `$value = yield from $result->step();`. PHPStan
     * and an IDE then know the type of `$value`. `$value = yield $result;` runs
     * the same way, but its value is `mixed` to anything that reads the code.
     *
     * @template U
     * @template F
     *
     * @param  callable(): Generator<mixed, Result<mixed, F>, mixed, U>  $fn
     * @return Result<U, F>
     */
    #[\NoDiscard('ignoring it drops a success or a failure')]
    public static function gen(callable $fn): self
    {
        $steps = $fn();
        while ($steps->valid()) {
            $step = $steps->current();
            if ($step->isErr()) {
                return $step;
            }

            $steps->send($step->unwrap());
        }

        return new Ok($steps->getReturn());
    }

    /**
     * @phpstan-assert-if-true Ok<T, never> $this
     *
     * @phpstan-assert-if-false Err<never, E> $this
     */
    abstract public function isOk(): bool;

    /**
     * @phpstan-assert-if-true Ok<T, never> $this
     *
     * @param  callable(T): bool  $fn
     */
    abstract public function isOkAnd(callable $fn): bool;

    /**
     * @phpstan-assert-if-true Err<never, E> $this
     *
     * @phpstan-assert-if-false Ok<T, never> $this
     */
    abstract public function isErr(): bool;

    /**
     * @phpstan-assert-if-true Err<never, E> $this
     *
     * @param  callable(E): bool  $fn
     */
    abstract public function isErrAnd(callable $fn): bool;

    /**
     * @return Option<T>
     */
    #[\NoDiscard('ignoring it drops a success or a failure')]
    abstract public function ok(): Option;

    /**
     * @return Option<E>
     */
    #[\NoDiscard('ignoring it drops a success or a failure')]
    abstract public function err(): Option;

    /**
     * @return T
     *
     * @throws \RuntimeException
     */
    abstract public function unwrap(): mixed;

    /**
     * Returns the value of `Ok`. On `Err` it throws the error itself when the
     * error is a `\Throwable`. Any other error is thrown the way `unwrap()` throws it.
     *
     * @return T
     *
     * @throws \Throwable
     */
    abstract public function unwrapOrThrow(): mixed;

    /**
     * One step of `Result::gen()`, written `yield from $result->step()`.
     *
     * That expression is the value of `Ok`. An `Err` ends the generator, and
     * `Result::gen()` returns that `Err`. This is `?` in Rust.
     *
     * @return Generator<int, Result<never, E>, mixed, T>
     */
    #[\NoDiscard('ignoring it drops a success or a failure')]
    abstract public function step(): Generator;

    /**
     * @return T
     *
     * @throws \RuntimeException
     */
    abstract public function expect(string $msg): mixed;

    /**
     * @return E
     *
     * @throws \RuntimeException
     */
    abstract public function expectErr(string $msg): mixed;

    /**
     * @return Result<T, E>
     */
    #[\NoDiscard('ignoring it drops a success or a failure')]
    abstract public function flatten(): self;

    /**
     * @return E
     *
     * @throws \RuntimeException
     */
    abstract public function intoErr(): mixed;

    /**
     * @return T
     *
     * @throws \RuntimeException
     */
    abstract public function intoOk(): mixed;

    /**
     * @return Iterator<int, T>
     */
    abstract public function iter(): Iterator;

    /**
     * @template D
     *
     * @param  D  $default
     * @return T|D
     */
    abstract public function unwrapOr(mixed $default): mixed;

    /**
     * @template D
     *
     * @param  callable(E): D  $fn
     * @return T|D
     */
    abstract public function unwrapOrElse(callable $fn): mixed;

    /**
     * @return E
     *
     * @throws \RuntimeException
     */
    abstract public function unwrapErr(): mixed;

    /**
     * @template U
     *
     * @param  callable(T): U  $fn
     * @return Result<U, E>
     */
    #[\NoDiscard('ignoring it drops a success or a failure')]
    abstract public function map(callable $fn): self;

    /**
     * @template F
     *
     * @param  callable(E): F  $fn
     * @return Result<T, F>
     */
    #[\NoDiscard('ignoring it drops a success or a failure')]
    abstract public function mapErr(callable $fn): self;

    /**
     * @template G
     *
     * @param  G  $default
     * @param  callable(T): G  $fn
     * @return G
     */
    abstract public function mapOr(mixed $default, callable $fn): mixed;

    /**
     * @template H
     *
     * @param  callable(E): H  $default
     * @param  callable(T): H  $fn
     * @return H
     */
    abstract public function mapOrElse(callable $default, callable $fn): mixed;

    /**
     * @param  callable(T): void  $fn
     * @return Result<T, E>
     */
    #[\NoDiscard('ignoring it drops a success or a failure')]
    abstract public function inspect(callable $fn): self;

    /**
     * @param  callable(E): void  $fn
     * @return Result<T, E>
     */
    #[\NoDiscard('ignoring it drops a success or a failure')]
    abstract public function inspectErr(callable $fn): self;

    /**
     * @template U
     * @template F
     *
     * @param  Result<U, F>  $res
     * @return Result<U, E|F>
     */
    #[\NoDiscard('ignoring it drops a success or a failure')]
    abstract public function and(self $res): self;

    /**
     * @template U
     * @template F
     *
     * @param  callable(T): Result<U, F>  $fn
     * @return Result<U, E|F>
     */
    #[\NoDiscard('ignoring it drops a success or a failure')]
    abstract public function andThen(callable $fn): self;

    /**
     * @template U
     * @template F
     *
     * @param  Result<U, F>  $res
     * @return Result<T|U, F>
     */
    #[\NoDiscard('ignoring it drops a success or a failure')]
    abstract public function or(self $res): self;

    /**
     * @template U
     * @template F
     *
     * @param  callable(E): Result<U, F>  $fn
     * @return Result<T|U, F>
     */
    #[\NoDiscard('ignoring it drops a success or a failure')]
    abstract public function orElse(callable $fn): self;

    /**
     * Turns `Result<Option<U>, E>` into `Option<Result<U, E>>`.
     *
     * `Ok(Some(v))` becomes `Some(Ok(v))`, `Ok(None)` becomes `None`, and
     * `Err(e)` becomes `Some(Err(e))`.
     *
     * @return Option<Result<mixed, E>>
     */
    #[\NoDiscard('ignoring it drops a success or a failure')]
    abstract public function transpose(): Option;

    /**
     * @template U
     *
     * @param  callable(T): U  $Ok
     * @param  callable(E): U  $Err
     * @return U
     */
    abstract public function match(callable $Ok, callable $Err): mixed;

    protected static function describe(mixed $value): string
    {
        return match (true) {
            $value instanceof \Throwable => $value::class.': '.$value->getMessage(),
            is_scalar($value), $value instanceof \Stringable => (string) $value,
            default => get_debug_type($value),
        };
    }
}
