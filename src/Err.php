<?php

declare(strict_types=1);

namespace Brimmar\PhpResult;

use ArrayIterator;
use Brimmar\PhpResult\Interfaces\Result;
use Iterator;
use ReflectionException;

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
    ) {
    }

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

    public function ok(?string $noneClassName = null): mixed
    {
        try {
            $none = new $noneClassName();

            return $none;
        } catch (ReflectionException $e) {
            return new Err('Could not instantiate the class: '.$e);
        }
    }

    public function err(?string $someClassName = null): mixed
    {
        try {
            $some = new $someClassName($this->error);

            return $some;
        } catch (ReflectionException $e) {
            return new Err('Could not instantiate the class: '.$e);
        }
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
    public function flatten(): Result
    {
        if ($this->error instanceof Result) {
            return $this->error;
        }

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

    public function map(callable $fn): Result
    {
        return $this;
    }

    public function mapErr(callable $fn): Result
    {
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
    public function inspect(callable $fn): Result
    {
        return $this;
    }

    /**
     * @return Result<T, E>
     */
    public function inspectErr(callable $fn): Result
    {
        $fn($this->error);

        return $this;
    }

    public function and(Result $res): Result
    {
        return $this;
    }

    public function andThen(callable $fn): Result
    {
        return $this;
    }

    public function or(Result $res): Result
    {
        return $res;
    }

    public function orElse(callable $fn): Result
    {
        return $fn($this->error);
    }

    public function transpose(?string $noneClassName = null, ?string $someClassName = null): mixed
    {
        try {
            $some = new $someClassName($this);

            return $some;
        } catch (ReflectionException $e) {
            return new Err('Could not instantiate class: '.$e);
        }
    }

    public function match(callable $Ok, callable $Err): mixed
    {
        return $Err($this->error);
    }
}
