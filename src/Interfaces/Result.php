<?php

declare(strict_types=1);

namespace Brimmar\PhpResult\Interfaces;

use Brimmar\PhpOption\Interfaces\Option;
use Brimmar\PhpResult\Err;
use Brimmar\PhpResult\Ok;
use Iterator;

/**
 * A value that has already succeeded or failed.
 *
 * `Ok` holds `T`. `Err` holds `E`. This is Rust's `Result<T, E>` and Effect 4's
 * `Result<A, E>` (the type that replaced `Either`). It is eager data. A program
 * that still has requirements, defects, or interruption is an `Effect`, and the
 * value you get after running one is an `Exit`.
 *
 * @template T
 * @template E
 */
interface Result
{
    /**
     * @phpstan-assert-if-true Ok<T, E> $this
     */
    public function isOk(): bool;

    /**
     * @phpstan-assert-if-true Ok<T, E> $this
     *
     * @param  callable(T): bool  $fn
     */
    public function isOkAnd(callable $fn): bool;

    /**
     * @phpstan-assert-if-true Err<T, E> $this
     */
    public function isErr(): bool;

    /**
     * @phpstan-assert-if-true Err<T, E> $this
     *
     * @param  callable(E): bool  $fn
     */
    public function isErrAnd(callable $fn): bool;

    /**
     * @return Option<T>
     */
    #[\NoDiscard('ignoring it drops a success or a failure')]
    public function ok(): Option;

    /**
     * @return Option<E>
     */
    #[\NoDiscard('ignoring it drops a success or a failure')]
    public function err(): Option;

    /**
     * @return T
     *
     * @throws \RuntimeException
     */
    public function unwrap(): mixed;

    /**
     * @return T
     *
     * @throws \RuntimeException
     */
    public function expect(string $msg): mixed;

    /**
     * @return E
     *
     * @throws \RuntimeException
     */
    public function expectErr(string $msg): mixed;

    /**
     * @return Result<T, E>
     */
    #[\NoDiscard('ignoring it drops a success or a failure')]
    public function flatten(): Result;

    /**
     * @return E
     *
     * @throws \RuntimeException
     */
    public function intoErr(): mixed;

    /**
     * @return T
     *
     * @throws \RuntimeException
     */
    public function intoOk(): mixed;

    /**
     * @return Iterator<int, T>
     */
    public function iter(): Iterator;

    /**
     * @template D
     *
     * @param  D  $default
     * @return T|D
     */
    public function unwrapOr(mixed $default): mixed;

    /**
     * @param  callable(E): T  $fn
     * @return T
     */
    public function unwrapOrElse(callable $fn): mixed;

    /**
     * @return E
     *
     * @throws \RuntimeException
     */
    public function unwrapErr(): mixed;

    /**
     * @template U
     *
     * @param  callable(T): U  $fn
     * @return Result<U, E>
     */
    #[\NoDiscard('ignoring it drops a success or a failure')]
    public function map(callable $fn): Result;

    /**
     * @template F
     *
     * @param  callable(E): F  $fn
     * @return Result<T, F>
     */
    #[\NoDiscard('ignoring it drops a success or a failure')]
    public function mapErr(callable $fn): Result;

    /**
     * @template G
     *
     * @param  G  $default
     * @param  callable(T): G  $fn
     * @return G
     */
    public function mapOr(mixed $default, callable $fn): mixed;

    /**
     * @template H
     *
     * @param  callable(E): H  $default
     * @param  callable(T): H  $fn
     * @return H
     */
    public function mapOrElse(callable $default, callable $fn): mixed;

    /**
     * @param  callable(T): void  $fn
     * @return Result<T, E>
     */
    #[\NoDiscard('ignoring it drops a success or a failure')]
    public function inspect(callable $fn): self;

    /**
     * @param  callable(E): void  $fn
     * @return Result<T, E>
     */
    #[\NoDiscard('ignoring it drops a success or a failure')]
    public function inspectErr(callable $fn): self;

    /**
     * @template U
     *
     * @param  Result<U, E>  $res
     * @return Result<U, E>
     */
    #[\NoDiscard('ignoring it drops a success or a failure')]
    public function and(Result $res): Result;

    /**
     * @template U
     *
     * @param  callable(T): Result<U, E>  $fn
     * @return Result<U, E>
     */
    #[\NoDiscard('ignoring it drops a success or a failure')]
    public function andThen(callable $fn): Result;

    /**
     * @template F
     *
     * @param  Result<T, F>  $res
     * @return Result<T, F>
     */
    #[\NoDiscard('ignoring it drops a success or a failure')]
    public function or(Result $res): Result;

    /**
     * @template F
     *
     * @param  callable(E): Result<T, F>  $fn
     * @return Result<T, F>
     */
    #[\NoDiscard('ignoring it drops a success or a failure')]
    public function orElse(callable $fn): Result;

    /**
     * Turns `Result<Option<U>, E>` into `Option<Result<U, E>>`.
     *
     * `Ok(Some(v))` becomes `Some(Ok(v))`, `Ok(None)` becomes `None`, and
     * `Err(e)` becomes `Some(Err(e))`.
     *
     * @return Option<Result<mixed, E>>
     */
    #[\NoDiscard('ignoring it drops a success or a failure')]
    public function transpose(): Option;

    /**
     * @template U
     *
     * @param  callable(T): U  $Ok
     * @param  callable(E): U  $Err
     * @return U
     */
    public function match(callable $Ok, callable $Err): mixed;
}
