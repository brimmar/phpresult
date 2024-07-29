<?php

declare(strict_types=1);

namespace Brimmar\PhpResult\Interfaces;

use Iterator;

/**
 * @template T
 * @template E
 */
interface Result
{
    /**
     * @phpstan-assert-if-true Ok<T> $this
     */
    public function isOk(): bool;

    /**
     * @phpstan-assert-if-true Ok<T> $this
     *
     * @param  callable(T): bool  $fn
     */
    public function isOkAnd(callable $fn): bool;

    /**
     * @phpstan-assert-if-true Err<E> $this
     */
    public function isErr(): bool;

    /**
     * @phpstan-assert-if-true Err<E> $this
     *
     * @param  callable(E): bool  $fn
     */
    public function isErrAnd(callable $fn): bool;

    /**
     * @template U
     *
     * @param  class-string<U>  $className
     * @return U|E
     */
    public function ok(?string $className = null): mixed;

    /**
     * @template U
     *
     * @param  class-string<U>  $className
     * @return U|E
     */
    public function err(?string $className = null): mixed;

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
     * @return Result<U, E>
     */
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
    public function map(callable $fn): Result;

    /**
     * @template F
     *
     * @param  callable(E): F  $fn
     * @return Result<T, F>
     */
    public function mapErr(callable $fn): Result;

    /**
     * @template G
     *
     * @param  callable(T): G  $fn
     * @return G|mixed
     */
    public function mapOr(mixed $default, callable $fn): mixed;

    /**
     * @template H
     *
     * @param  callable(T): H  $default
     * @param  callable(E): H  $fn
     * @return H
     */
    public function mapOrElse(callable $default, callable $fn): mixed;

    /**
     * @param  callable(T): void  $fn
     */
    public function inspect(callable $fn): self;

    /**
     * @param  callable(E): void  $fn
     */
    public function inspectErr(callable $fn): self;

    /**
     * @template U
     *
     * @param  Result<U, E>  $res
     * @return Result<U, E>
     */
    public function and(Result $res): Result;

    /**
     * @template U
     *
     * @param  callable(T): Result<U, E>  $fn
     * @return Result<U, E>
     */
    public function andThen(callable $fn): Result;

    /**
     * @template F
     *
     * @param  Result<T, F>  $res
     * @return Result<T, F>
     */
    public function or(Result $res): Result;

    /**
     * @template F
     *
     * @param  callable(E): Result<T, F>  $fn
     * @return Result<T, F>
     */
    public function orElse(callable $fn): Result;

    /**
     * @throws \ReflectionException
     */
    public function transpose(?string $noneClassName = null, ?string $someClassName = null): mixed;

    /**
     * @template U
     *
     * @param  callable(T): U  $Ok
     * @param  callable(E): U  $Err
     * @return U
     */
    public function match(callable $Ok, callable $Err): mixed;
}
