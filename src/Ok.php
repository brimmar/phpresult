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

    public function ok(?string $someClassName = '\Brimmar\PhpOption\Some'): mixed
    {
        try {
            $some = new $someClassName($this->value);

            return $some;
        } catch (ReflectionException $e) {
            return new Err('Could not instantiate the class: '.$e);
        }
    }

    public function err(?string $noneClassName = '\Brimmar\PhpOption\None'): mixed
    {
        try {
            $none = new $noneClassName;

            return $none;
        } catch (ReflectionException $e) {
            return new Err('Could not instantiate the class: '.$e);
        }
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

    public function iter(): Iterator
    {
        if (is_array($this->value)) {
            return new ArrayIterator($this->value);
        }

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

    public function map(callable $fn): Result
    {
        return new Ok($fn($this->value));
    }

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

    public function inspect(callable $fn): Result
    {
        $fn($this->value);

        return $this;
    }

    public function inspectErr(callable $fn): Result
    {
        return $this;
    }

    public function and(Result $res): Result
    {
        return $res;
    }

    public function andThen(callable $fn): Result
    {
        return $fn($this->value);
    }

    public function or(Result $res): Result
    {
        return $this;
    }

    public function orElse(callable $fn): Result
    {
        return $this;
    }

    public function transpose(?string $noneClassName = '\Brimmar\PhpOption\None', ?string $someClassName = '\Brimmar\PhpOption\Some'): mixed
    {
        if ($this->value instanceof $noneClassName) {
            try {
                $none = new $noneClassName;

                return $none;
            } catch (ReflectionException $e) {
                return new Err('Could not instantiate class: '.$e);
            }
        } elseif ($this->value instanceof $someClassName) {
            try {
                $innerValue = $this->unwrap()->unwrap();

                $some = new $someClassName(new Ok($innerValue));

                return $some;
            } catch (ReflectionException $e) {
                return new Err('Could not instantiate class: '.$e);
            }
        }

        return $this;
    }

    public function match(callable $Ok, callable $Err): mixed
    {
        return $Ok($this->value);
    }
}
