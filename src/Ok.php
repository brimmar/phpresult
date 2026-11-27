<?php

declare(strict_types=1);

namespace Brimmar\PhpResult;

use ArrayIterator;
use Brimmar\PhpOption\None;
use Brimmar\PhpOption\Option;
use Brimmar\PhpOption\Some;
use Generator;
use Iterator;

/**
 * @template-covariant T
 * @template-covariant E = never
 *
 * @extends Result<T, E>
 */
final class Ok extends Result
{
    /** @param T $value */
    public function __construct(
        private readonly mixed $value,
    ) {}

    #[\Override]
    public function isOk(): bool
    {
        return true;
    }

    #[\Override]
    public function isOkAnd(callable $fn): bool
    {
        return $fn($this->value);
    }

    #[\Override]
    public function isErr(): bool
    {
        return false;
    }

    #[\Override]
    public function isErrAnd(callable $fn): bool
    {
        return false;
    }

    #[\Override]
    #[\NoDiscard('ignoring it drops a success or a failure')]
    public function ok(): Option
    {
        return new Some($this->value);
    }

    #[\Override]
    #[\NoDiscard('ignoring it drops a success or a failure')]
    public function err(): Option
    {
        return new None;
    }

    #[\Override]
    public function unwrap(): mixed
    {
        return $this->value;
    }

    #[\Override]
    public function unwrapOrThrow(): mixed
    {
        return $this->value;
    }

    #[\Override]
    #[\NoDiscard('ignoring it drops a success or a failure')]
    public function step(): Generator
    {
        yield from [];

        return $this->value;
    }

    #[\Override]
    public function unwrapOrElse(callable $fn): mixed
    {
        return $this->value;
    }

    #[\Override]
    public function expect(string $msg): mixed
    {
        return $this->value;
    }

    #[\Override]
    public function expectErr(string $msg): never
    {
        throw new \RuntimeException($msg.': '.self::describe($this->value));
    }

    #[\Override]
    #[\NoDiscard('ignoring it drops a success or a failure')]
    public function flatten(): Result
    {
        if ($this->value instanceof Result) {
            return $this->value;
        }

        return $this;
    }

    #[\Override]
    public function intoErr(): never
    {
        throw new \RuntimeException('Called intoErr on an Ok value, should be unreachable');
    }

    #[\Override]
    public function intoOk(): mixed
    {
        return $this->value;
    }

    #[\Override]
    public function iter(): Iterator
    {
        return new ArrayIterator([$this->value]);
    }

    #[\Override]
    public function unwrapOr(mixed $default): mixed
    {
        return $this->value;
    }

    #[\Override]
    public function unwrapErr(): never
    {
        throw new \RuntimeException('Called unwrapErr on an Ok value');
    }

    #[\Override]
    #[\NoDiscard('ignoring it drops a success or a failure')]
    public function map(callable $fn): Result
    {
        return new Ok($fn($this->value));
    }

    #[\Override]
    #[\NoDiscard('ignoring it drops a success or a failure')]
    public function mapErr(callable $fn): Result
    {
        return $this;
    }

    #[\Override]
    public function mapOr(mixed $default, callable $fn): mixed
    {
        return $fn($this->value);
    }

    #[\Override]
    public function mapOrElse(callable $default, callable $fn): mixed
    {
        return $fn($this->value);
    }

    #[\Override]
    #[\NoDiscard('ignoring it drops a success or a failure')]
    public function inspect(callable $fn): Result
    {
        $fn($this->value);

        return $this;
    }

    #[\Override]
    #[\NoDiscard('ignoring it drops a success or a failure')]
    public function inspectErr(callable $fn): Result
    {
        return $this;
    }

    #[\Override]
    #[\NoDiscard('ignoring it drops a success or a failure')]
    public function and(Result $res): Result
    {
        return $res;
    }

    #[\Override]
    #[\NoDiscard('ignoring it drops a success or a failure')]
    public function andThen(callable $fn): Result
    {
        return $fn($this->value);
    }

    #[\Override]
    #[\NoDiscard('ignoring it drops a success or a failure')]
    public function or(Result $res): Result
    {
        return $this;
    }

    #[\Override]
    #[\NoDiscard('ignoring it drops a success or a failure')]
    public function orElse(callable $fn): Result
    {
        return $this;
    }

    #[\Override]
    #[\NoDiscard('ignoring it drops a success or a failure')]
    public function transpose(): Option
    {
        if ($this->value instanceof None) {
            return new None;
        }

        if ($this->value instanceof Some) {
            return new Some(new Ok($this->value->unwrap()));
        }

        throw new \InvalidArgumentException('transpose() expects Ok to hold an Option');
    }

    #[\Override]
    public function match(callable $Ok, callable $Err): mixed
    {
        return $Ok($this->value);
    }
}
