<?php

declare(strict_types=1);

namespace Brimmar\PhpOption\Interfaces;

use Brimmar\PhpOption\None;
use Brimmar\PhpOption\Some;
use Brimmar\PhpResult\Interfaces\Result;
use Iterator;

/**
 * Absence of a value. `Some` holds `T`. `None` holds nothing.
 *
 * This is not a failure. Expected failure is `Result`. Effect 4 uses the same
 * split: `Option` for absence, `Result` for success or failure. There is no
 * `Either` type.
 *
 * @template T
 */
interface Option
{
    /**
     * @phpstan-assert-if-true Some<T> $this
     */
    public function isSome(): bool;

    /**
     * @param  callable(T): bool  $fn
     *
     * @phpstan-assert-if-true Some<T> $this
     */
    public function isSomeAnd(callable $fn): bool;

    /**
     * @phpstan-assert-if-true None<T> $this
     */
    public function isNone(): bool;

    public function iter(): Iterator;

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
     * @return Option<T>
     */
    #[\NoDiscard('ignoring it drops a success or a failure')]
    public function flatten(): Option;

    /**
     * @template D
     *
     * @param  D  $default
     * @return T|D
     */
    public function unwrapOr(mixed $default): mixed;

    /**
     * @template D
     *
     * @param  callable(): D  $default
     * @return T|D
     */
    public function unwrapOrElse(callable $default): mixed;

    /**
     * @template U
     *
     * @param  callable(T): U  $fn
     * @return Option<U>
     */
    #[\NoDiscard('ignoring it drops a success or a failure')]
    public function map(callable $fn): Option;

    /**
     * @template U
     *
     * @param  U  $default
     * @param  callable(T): U  $fn
     * @return U
     */
    public function mapOr(mixed $default, callable $fn): mixed;

    /**
     * @template U
     *
     * @param  callable(): U  $default
     * @param  callable(T): U  $fn
     * @return U
     */
    public function mapOrElse(callable $default, callable $fn): mixed;

    /**
     * @param  callable(T): void  $fn
     * @return Option<T>
     */
    #[\NoDiscard('ignoring it drops a success or a failure')]
    public function inspect(callable $fn): self;

    /**
     * @template E
     *
     * @param  E  $error
     * @return Result<T, E>
     */
    #[\NoDiscard('ignoring it drops a success or a failure')]
    public function okOr(mixed $error): Result;

    /**
     * @template E
     *
     * @param  callable(): E  $error
     * @return Result<T, E>
     */
    #[\NoDiscard('ignoring it drops a success or a failure')]
    public function okOrElse(callable $error): Result;

    /**
     * @template U
     *
     * @param  Option<U>  $opt
     * @return Option<U>
     */
    #[\NoDiscard('ignoring it drops a success or a failure')]
    public function and(Option $opt): Option;

    /**
     * @template U
     *
     * @param  callable(T): Option<U>  $fn
     * @return Option<U>
     */
    #[\NoDiscard('ignoring it drops a success or a failure')]
    public function andThen(callable $fn): Option;

    /**
     * @param  Option<T>  $opt
     * @return Option<T>
     */
    #[\NoDiscard('ignoring it drops a success or a failure')]
    public function or(Option $opt): Option;

    /**
     * @param  callable(): Option<T>  $fn
     * @return Option<T>
     */
    #[\NoDiscard('ignoring it drops a success or a failure')]
    public function orElse(callable $fn): Option;

    /**
     * Turns `Option<Result<U, E>>` into `Result<Option<U>, E>`.
     *
     * `Some(Ok(v))` becomes `Ok(Some(v))`, `Some(Err(e))` becomes `Err(e)`,
     * and `None` becomes `Ok(None)`.
     *
     * @return Result<Option<mixed>, mixed>
     */
    #[\NoDiscard('ignoring it drops a success or a failure')]
    public function transpose(): Result;

    /**
     * @param  Option<T>  $opt
     * @return Option<T>
     */
    #[\NoDiscard('ignoring it drops a success or a failure')]
    public function xor(Option $opt): Option;

    /**
     * @template U
     *
     * @param  Option<U>  $other
     * @return Option<array{T, U}>
     */
    #[\NoDiscard('ignoring it drops a success or a failure')]
    public function zip(Option $other): Option;

    /**
     * @template U
     * @template R
     *
     * @param  Option<U>  $other
     * @param  callable(T, U): R  $fn
     * @return Option<R>
     */
    #[\NoDiscard('ignoring it drops a success or a failure')]
    public function zipWith(Option $other, callable $fn): Option;

    /**
     * @return array{Option<mixed>, Option<mixed>}
     */
    #[\NoDiscard('ignoring it drops a success or a failure')]
    public function unzip(): array;

    /**
     * @template U
     *
     * @param  callable(T): U  $Some
     * @param  callable(): U  $None
     * @return U
     */
    public function match(callable $Some, callable $None): mixed;

    /**
     * @param  callable(T): bool  $predicate
     * @return Option<T>
     */
    #[\NoDiscard('ignoring it drops a success or a failure')]
    public function filter(callable $predicate): Option;
}
