<?php

declare(strict_types=1);

namespace Brimmar\PhpOption;

use ArrayIterator;
use Brimmar\PhpOption\Interfaces\Option;
use Brimmar\PhpResult\Err;
use Brimmar\PhpResult\Interfaces\Result;
use Brimmar\PhpResult\Ok;
use Iterator;

/**
 * @template T
 *
 * @implements Option<T>
 */
final class Some implements Option
{
    public function __construct(
        private mixed $value,
    ) {}

    public function isSome(): bool
    {
        return true;
    }

    public function isSomeAnd(callable $fn): bool
    {
        return $fn($this->value);
    }

    public function isNone(): bool
    {
        return false;
    }

    public function iter(): Iterator
    {
        if (is_array($this->value)) {
            return new ArrayIterator($this->value);
        }

        return new ArrayIterator([$this->value]);
    }

    public function unwrap(): mixed
    {
        return $this->value;
    }

    public function expect(string $msg): mixed
    {
        return $this->value;
    }

    /**
     * @return Option<T>
     */
    #[\NoDiscard('ignoring it drops a success or a failure')]
    public function flatten(): Option
    {
        if ($this->value instanceof Option) {
            /** @var Option<T> */
            return $this->value;
        }

        return $this;
    }

    public function unwrapOr(mixed $default): mixed
    {
        return $this->value;
    }

    public function unwrapOrElse(callable $default): mixed
    {
        return $this->value;
    }

    /**
     * @template U
     *
     * @param  callable(T): U  $fn
     * @return Option<U>
     */
    #[\NoDiscard('ignoring it drops a success or a failure')]
    public function map(callable $fn): Option
    {
        /** @var Some<U> */
        return new Some($fn($this->value));
    }

    public function mapOr(mixed $default, callable $fn): mixed
    {
        return $fn($this->value);
    }

    public function mapOrElse(callable $default, callable $fn): mixed
    {
        return $fn($this->value);
    }

    /**
     * @return Option<T>
     */
    #[\NoDiscard('ignoring it drops a success or a failure')]
    public function inspect(callable $fn): Option
    {
        $fn($this->value);

        return $this;
    }

    /**
     * @return Result<T, mixed>
     */
    #[\NoDiscard('ignoring it drops a success or a failure')]
    public function okOr(mixed $error): Result
    {
        /** @var Ok<T, mixed> */
        return new Ok($this->value);
    }

    /**
     * @return Result<T, mixed>
     */
    #[\NoDiscard('ignoring it drops a success or a failure')]
    public function okOrElse(callable $error): Result
    {
        /** @var Ok<T, mixed> */
        return new Ok($this->value);
    }

    /**
     * @template U
     *
     * @param  Option<U>  $opt
     * @return Option<U>
     */
    #[\NoDiscard('ignoring it drops a success or a failure')]
    public function and(Option $opt): Option
    {
        if ($opt instanceof None) {
            /** @var None<U> */
            return new None;
        }

        return $opt;
    }

    /**
     * @template U
     *
     * @param  callable(T): Option<U>  $fn
     * @return Option<U>
     */
    #[\NoDiscard('ignoring it drops a success or a failure')]
    public function andThen(callable $fn): Option
    {
        return $fn($this->value);
    }

    /**
     * @param  Option<T>  $opt
     * @return Option<T>
     */
    #[\NoDiscard('ignoring it drops a success or a failure')]
    public function or(Option $opt): Option
    {
        return $this;
    }

    /**
     * @param  callable(): Option<T>  $fn
     * @return Option<T>
     */
    #[\NoDiscard('ignoring it drops a success or a failure')]
    public function orElse(callable $fn): Option
    {
        return $this;
    }

    /**
     * @return Result<Option<mixed>, mixed>
     */
    #[\NoDiscard('ignoring it drops a success or a failure')]
    public function transpose(): Result
    {
        if ($this->value instanceof Ok) {
            /** @var Ok<Option<mixed>, mixed> */
            return new Ok(new Some($this->value->unwrap()));
        }

        if ($this->value instanceof Err) {
            /** @var Err<Option<mixed>, mixed> */
            return new Err($this->value->unwrapErr());
        }

        throw new \InvalidArgumentException('transpose() expects Some to hold a Result');
    }

    /**
     * @return Option<T>
     */
    #[\NoDiscard('ignoring it drops a success or a failure')]
    public function xor(Option $opt): Option
    {
        if ($opt instanceof None) {
            return $this;
        }

        /** @var None<T> */
        return new None;
    }

    /**
     * @template U
     *
     * @param  Option<U>  $other
     * @return Option<array{T, U}>
     */
    #[\NoDiscard('ignoring it drops a success or a failure')]
    public function zip(Option $other): Option
    {
        if ($other instanceof None) {
            /** @var None<array{T, U}> */
            return new None;
        }

        /** @var Some<mixed> $other */
        $otherSome = $other;

        /** @var Some<array{T, U}> */
        return new Some([$this->value, $other->unwrap()]);
    }

    /**
     * @template U
     * @template R
     *
     * @param  Option<U>  $other
     * @param  callable(T, U): R  $fn
     * @return Option<R>
     */
    #[\NoDiscard('ignoring it drops a success or a failure')]
    public function zipWith(Option $other, callable $fn): Option
    {
        if ($other instanceof None) {
            /** @var None<R> */
            return new None;
        }

        /** @var Some<R> */
        return new Some($fn($this->value, $other->unwrap()));
    }

    /**
     * @return array{Option<mixed>, Option<mixed>}
     */
    #[\NoDiscard('ignoring it drops a success or a failure')]
    public function unzip(): array
    {
        if (! is_array($this->value)) {
            return [new None, new None];
        }

        return [new Some($this->value[0]), new Some($this->value[1])];
    }

    public function match(callable $Some, callable $None): mixed
    {
        return $Some($this->value);
    }

    /**
     * @return Option<T>
     */
    #[\NoDiscard('ignoring it drops a success or a failure')]
    public function filter(callable $predicate): Option
    {
        if ($predicate($this->value)) {
            return $this;
        }

        /** @var None<T> */
        return new None;
    }
}
