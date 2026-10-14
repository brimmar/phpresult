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
final class Err implements Result
{
    public function __construct(
        private readonly mixed $error,
    ) {}

    public function isOk(): bool
    {
        return false;
    }

    public function isOkAnd(callable $fn): bool
    {
        return false;
    }

    public function isErr(): bool
    {
        return true;
    }

    public function isErrAnd(callable $fn): bool
    {
        return $fn($this->error);
    }

    /**
     * @return Option<T>
     */
    #[\NoDiscard('ignoring it drops a success or a failure')]
    public function ok(): Option
    {
        /** @var None<T> */
        return new None;
    }

    /**
     * @return Option<E>
     */
    #[\NoDiscard('ignoring it drops a success or a failure')]
    public function err(): Option
    {
        /** @var Some<E> */
        return new Some($this->error);
    }

    public function unwrap(): never
    {
        throw new \RuntimeException("Called unwrap on an Err value: $this->error");
    }

    public function unwrapOrElse(callable $fn): mixed
    {
        return $fn($this->error);
    }

    public function expect(string $msg): never
    {
        throw new \RuntimeException("$msg: $this->error");
    }

    public function expectErr(string $msg): mixed
    {
        return $this->error;
    }

    /**
     * @return Result<T, E>
     */
    #[\NoDiscard('ignoring it drops a success or a failure')]
    public function flatten(): Result
    {
        return $this;
    }

    public function intoErr(): mixed
    {
        return $this->error;
    }

    public function intoOk(): mixed
    {
        throw new \RuntimeException('Called intoOk on an Err value, should be unreachable');
    }

    /** @return Iterator<int, T> */
    public function iter(): Iterator
    {
        return new ArrayIterator([]);
    }

    public function unwrapOr(mixed $default): mixed
    {
        return $default;
    }

    public function unwrapErr(): mixed
    {
        return $this->error;
    }

    #[\NoDiscard('ignoring it drops a success or a failure')]
    public function map(callable $fn): Result
    {
        return $this;
    }

    #[\NoDiscard('ignoring it drops a success or a failure')]
    public function mapErr(callable $fn): Result
    {
        /** @var Err<T, mixed> */
        return new Err($fn($this->error));
    }

    public function mapOr(mixed $default, callable $fn): mixed
    {
        return $default;
    }

    public function mapOrElse(callable $default, callable $fn): mixed
    {
        return $default($this->error);
    }

    /**
     * @return Result<T, E>
     */
    #[\NoDiscard('ignoring it drops a success or a failure')]
    public function inspect(callable $fn): Result
    {
        return $this;
    }

    /**
     * @return Result<T, E>
     */
    #[\NoDiscard('ignoring it drops a success or a failure')]
    public function inspectErr(callable $fn): Result
    {
        $fn($this->error);

        return $this;
    }

    #[\NoDiscard('ignoring it drops a success or a failure')]
    public function and(Result $res): Result
    {
        return $this;
    }

    #[\NoDiscard('ignoring it drops a success or a failure')]
    public function andThen(callable $fn): Result
    {
        return $this;
    }

    #[\NoDiscard('ignoring it drops a success or a failure')]
    public function or(Result $res): Result
    {
        return $res;
    }

    #[\NoDiscard('ignoring it drops a success or a failure')]
    public function orElse(callable $fn): Result
    {
        return $fn($this->error);
    }

    /**
     * @return Option<Result<T, E>>
     */
    #[\NoDiscard('ignoring it drops a success or a failure')]
    public function transpose(): Option
    {
        /** @var Some<Result<T, E>> */
        return new Some($this);
    }

    public function match(callable $Ok, callable $Err): mixed
    {
        return $Err($this->error);
    }
}
