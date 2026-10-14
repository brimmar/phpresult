<?php

declare(strict_types=1);

namespace Brimmar\PhpResult;

use ArrayIterator;
use Brimmar\PhpOption\Interfaces\Option;
use Brimmar\PhpOption\None;
use Brimmar\PhpOption\Some;
use Brimmar\PhpResult\Interfaces\Result;
use Iterator;

/**
 * @template T
 * @template E
 *
 * @implements Result<T, E>
 */
final class Ok implements Result
{
    public function __construct(
        private readonly mixed $value,
    ) {}

    public function isOk(): bool
    {
        return true;
    }

    public function isOkAnd(callable $fn): bool
    {
        return $fn($this->value);
    }

    public function isErr(): bool
    {
        return false;
    }

    public function isErrAnd(callable $fn): bool
    {
        return false;
    }

    /**
     * @return Option<T>
     */
    #[\NoDiscard('ignoring it drops a success or a failure')]
    public function ok(): Option
    {
        /** @var Some<T> */
        return new Some($this->value);
    }

    /**
     * @return Option<E>
     */
    #[\NoDiscard('ignoring it drops a success or a failure')]
    public function err(): Option
    {
        /** @var None<E> */
        return new None;
    }

    public function unwrap(): mixed
    {
        return $this->value;
    }

    public function unwrapOrElse(callable $fn): mixed
    {
        return $this->value;
    }

    public function expect(string $msg): mixed
    {
        return $this->value;
    }

    public function expectErr(string $msg): mixed
    {
        throw new \RuntimeException("$msg: $this->value");
    }

    /**
     * @return Result<T, E>
     */
    #[\NoDiscard('ignoring it drops a success or a failure')]
    public function flatten(): Result
    {
        if ($this->value instanceof Result) {
            return $this->value;
        }

        return $this;
    }

    public function intoErr(): mixed
    {
        throw new \RuntimeException('Called intoErr on an Ok value, should be unreachable');
    }

    public function intoOk(): mixed
    {
        return $this->value;
    }

    /** @return Iterator<int, T> */
    public function iter(): Iterator
    {
        return new ArrayIterator([$this->value]);
    }

    public function unwrapOr(mixed $default): mixed
    {
        return $this->value;
    }

    public function unwrapErr(): never
    {
        throw new \RuntimeException('Called unwrapErr on an Ok value');
    }

    #[\NoDiscard('ignoring it drops a success or a failure')]
    public function map(callable $fn): Result
    {
        /** @var Ok<mixed, E> */
        return new Ok($fn($this->value));
    }

    #[\NoDiscard('ignoring it drops a success or a failure')]
    public function mapErr(callable $fn): Result
    {
        return $this;
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
     * @return Result<T, E>
     */
    #[\NoDiscard('ignoring it drops a success or a failure')]
    public function inspect(callable $fn): Result
    {
        $fn($this->value);

        return $this;
    }

    /**
     * @return Result<T, E>
     */
    #[\NoDiscard('ignoring it drops a success or a failure')]
    public function inspectErr(callable $fn): Result
    {
        return $this;
    }

    #[\NoDiscard('ignoring it drops a success or a failure')]
    public function and(Result $res): Result
    {
        return $res;
    }

    #[\NoDiscard('ignoring it drops a success or a failure')]
    public function andThen(callable $fn): Result
    {
        return $fn($this->value);
    }

    #[\NoDiscard('ignoring it drops a success or a failure')]
    public function or(Result $res): Result
    {
        return $this;
    }

    #[\NoDiscard('ignoring it drops a success or a failure')]
    public function orElse(callable $fn): Result
    {
        return $this;
    }

    /**
     * @return Option<Result<mixed, E>>
     */
    #[\NoDiscard('ignoring it drops a success or a failure')]
    public function transpose(): Option
    {
        if ($this->value instanceof None) {
            /** @var None<Result<mixed, E>> */
            return new None;
        }

        if ($this->value instanceof Some) {
            /** @var Some<Result<mixed, E>> */
            return new Some(new Ok($this->value->unwrap()));
        }

        throw new \InvalidArgumentException('transpose() expects Ok to hold an Option');
    }

    public function match(callable $Ok, callable $Err): mixed
    {
        return $Ok($this->value);
    }
}
