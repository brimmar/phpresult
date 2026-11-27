<?php

declare(strict_types=1);

namespace Brimmar\PhpOption;

use Brimmar\PhpResult\Result;
use Iterator;

/**
 * Absence of a value. `Some` holds `T`. `None` holds nothing.
 *
 * This is not a failure. Expected failure is `Result`. Effect 4 uses the same
 * split: `Option` for absence, `Result` for success or failure. There is no
 * `Either` type.
 *
 * @template-covariant T
 */
abstract class Option
{
    /**
     * `null` becomes `None`. Every other value becomes `Some`.
     *
     * @template U
     *
     * @param  U|null  $value
     * @return Option<U>
     */
    #[\NoDiscard('ignoring it drops a success or a failure')]
    public static function fromNullable(mixed $value): self
    {
        return $value === null ? new None : new Some($value);
    }

    /**
     * @phpstan-assert-if-true Some<T> $this
     *
     * @phpstan-assert-if-false None<never> $this
     */
    abstract public function isSome(): bool;

    /**
     * @param  callable(T): bool  $fn
     *
     * @phpstan-assert-if-true Some<T> $this
     */
    abstract public function isSomeAnd(callable $fn): bool;

    /**
     * @phpstan-assert-if-true None<never> $this
     *
     * @phpstan-assert-if-false Some<T> $this
     */
    abstract public function isNone(): bool;

    /**
     * @return Iterator<int, T>
     */
    abstract public function iter(): Iterator;

    /**
     * @return T
     *
     * @throws \RuntimeException
     */
    abstract public function unwrap(): mixed;

    /**
     * @return T
     *
     * @throws \RuntimeException
     */
    abstract public function expect(string $msg): mixed;

    /**
     * @return Option<T>
     */
    #[\NoDiscard('ignoring it drops a success or a failure')]
    abstract public function flatten(): self;

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
     * @param  callable(): D  $default
     * @return T|D
     */
    abstract public function unwrapOrElse(callable $default): mixed;

    /**
     * @template U
     *
     * @param  callable(T): U  $fn
     * @return Option<U>
     */
    #[\NoDiscard('ignoring it drops a success or a failure')]
    abstract public function map(callable $fn): self;

    /**
     * @template U
     *
     * @param  U  $default
     * @param  callable(T): U  $fn
     * @return U
     */
    abstract public function mapOr(mixed $default, callable $fn): mixed;

    /**
     * @template U
     *
     * @param  callable(): U  $default
     * @param  callable(T): U  $fn
     * @return U
     */
    abstract public function mapOrElse(callable $default, callable $fn): mixed;

    /**
     * @param  callable(T): void  $fn
     * @return Option<T>
     */
    #[\NoDiscard('ignoring it drops a success or a failure')]
    abstract public function inspect(callable $fn): self;

    /**
     * @template E
     *
     * @param  E  $error
     * @return Result<T, E>
     */
    #[\NoDiscard('ignoring it drops a success or a failure')]
    abstract public function okOr(mixed $error): Result;

    /**
     * @template E
     *
     * @param  callable(): E  $error
     * @return Result<T, E>
     */
    #[\NoDiscard('ignoring it drops a success or a failure')]
    abstract public function okOrElse(callable $error): Result;

    /**
     * @template U
     *
     * @param  Option<U>  $opt
     * @return Option<U>
     */
    #[\NoDiscard('ignoring it drops a success or a failure')]
    abstract public function and(self $opt): self;

    /**
     * @template U
     *
     * @param  callable(T): Option<U>  $fn
     * @return Option<U>
     */
    #[\NoDiscard('ignoring it drops a success or a failure')]
    abstract public function andThen(callable $fn): self;

    /**
     * @template U
     *
     * @param  Option<U>  $opt
     * @return Option<T|U>
     */
    #[\NoDiscard('ignoring it drops a success or a failure')]
    abstract public function or(self $opt): self;

    /**
     * @template U
     *
     * @param  callable(): Option<U>  $fn
     * @return Option<T|U>
     */
    #[\NoDiscard('ignoring it drops a success or a failure')]
    abstract public function orElse(callable $fn): self;

    /**
     * Turns `Option<Result<U, E>>` into `Result<Option<U>, E>`.
     *
     * `Some(Ok(v))` becomes `Ok(Some(v))`, `Some(Err(e))` becomes `Err(e)`,
     * and `None` becomes `Ok(None)`.
     *
     * @return Result<Option<mixed>, mixed>
     */
    #[\NoDiscard('ignoring it drops a success or a failure')]
    abstract public function transpose(): Result;

    /**
     * @template U
     *
     * @param  Option<U>  $opt
     * @return Option<T|U>
     */
    #[\NoDiscard('ignoring it drops a success or a failure')]
    abstract public function xor(self $opt): self;

    /**
     * @template U
     *
     * @param  Option<U>  $other
     * @return Option<array{T, U}>
     */
    #[\NoDiscard('ignoring it drops a success or a failure')]
    abstract public function zip(self $other): self;

    /**
     * @template U
     * @template R
     *
     * @param  Option<U>  $other
     * @param  callable(T, U): R  $fn
     * @return Option<R>
     */
    #[\NoDiscard('ignoring it drops a success or a failure')]
    abstract public function zipWith(self $other, callable $fn): self;

    /**
     * @return array{Option<mixed>, Option<mixed>}
     */
    #[\NoDiscard('ignoring it drops a success or a failure')]
    abstract public function unzip(): array;

    /**
     * @template U
     *
     * @param  callable(T): U  $Some
     * @param  callable(): U  $None
     * @return U
     */
    abstract public function match(callable $Some, callable $None): mixed;

    /**
     * @param  callable(T): bool  $predicate
     * @return Option<T>
     */
    #[\NoDiscard('ignoring it drops a success or a failure')]
    abstract public function filter(callable $predicate): self;
}
