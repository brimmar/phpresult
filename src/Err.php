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
 * @template-covariant T = never
 * @template-covariant E = mixed
 *
 * @extends Result<T, E>
 */
final class Err extends Result
{
    /** @param E $error */
    public function __construct(
        private readonly mixed $error,
    ) {}

    #[\Override]
    public function isOk(): bool
    {
        return false;
    }

    #[\Override]
    public function isOkAnd(callable $fn): bool
    {
        return false;
    }

    #[\Override]
    public function isErr(): bool
    {
        return true;
    }

    #[\Override]
    public function isErrAnd(callable $fn): bool
    {
        return $fn($this->error);
    }

    #[\Override]
    #[\NoDiscard('ignoring it drops a success or a failure')]
    public function ok(): Option
    {
        return new None;
    }

    #[\Override]
    #[\NoDiscard('ignoring it drops a success or a failure')]
    public function err(): Option
    {
        return new Some($this->error);
    }

    #[\Override]
    public function unwrap(): never
    {
        throw $this->unwrapFailure('Called unwrap on an Err value');
    }

    #[\Override]
    public function unwrapOrThrow(): never
    {
        throw $this->error instanceof \Throwable ? $this->error : $this->unwrapFailure('Called unwrapOrThrow on an Err value');
    }

    #[\Override]
    #[\NoDiscard('ignoring it drops a success or a failure')]
    public function step(): Generator
    {
        yield $this;

        throw new \LogicException('step() was resumed after an Err. Write it as "yield from $result->step()" inside Result::gen().');
    }

    #[\Override]
    public function unwrapOrElse(callable $fn): mixed
    {
        return $fn($this->error);
    }

    #[\Override]
    public function expect(string $msg): never
    {
        throw $this->unwrapFailure($msg);
    }

    #[\Override]
    public function expectErr(string $msg): mixed
    {
        return $this->error;
    }

    #[\Override]
    #[\NoDiscard('ignoring it drops a success or a failure')]
    public function flatten(): Result
    {
        return $this;
    }

    #[\Override]
    public function intoErr(): mixed
    {
        return $this->error;
    }

    #[\Override]
    public function intoOk(): never
    {
        throw new \RuntimeException('Called intoOk on an Err value, should be unreachable');
    }

    #[\Override]
    public function iter(): Iterator
    {
        return new ArrayIterator([]);
    }

    #[\Override]
    public function unwrapOr(mixed $default): mixed
    {
        return $default;
    }

    #[\Override]
    public function unwrapErr(): mixed
    {
        return $this->error;
    }

    #[\Override]
    #[\NoDiscard('ignoring it drops a success or a failure')]
    public function map(callable $fn): Result
    {
        return $this;
    }

    #[\Override]
    #[\NoDiscard('ignoring it drops a success or a failure')]
    public function mapErr(callable $fn): Result
    {
        return new Err($fn($this->error));
    }

    #[\Override]
    public function mapOr(mixed $default, callable $fn): mixed
    {
        return $default;
    }

    #[\Override]
    public function mapOrElse(callable $default, callable $fn): mixed
    {
        return $default($this->error);
    }

    #[\Override]
    #[\NoDiscard('ignoring it drops a success or a failure')]
    public function inspect(callable $fn): Result
    {
        return $this;
    }

    #[\Override]
    #[\NoDiscard('ignoring it drops a success or a failure')]
    public function inspectErr(callable $fn): Result
    {
        $fn($this->error);

        return $this;
    }

    #[\Override]
    #[\NoDiscard('ignoring it drops a success or a failure')]
    public function and(Result $res): Result
    {
        return $this;
    }

    #[\Override]
    #[\NoDiscard('ignoring it drops a success or a failure')]
    public function andThen(callable $fn): Result
    {
        return $this;
    }

    #[\Override]
    #[\NoDiscard('ignoring it drops a success or a failure')]
    public function or(Result $res): Result
    {
        return $res;
    }

    #[\Override]
    #[\NoDiscard('ignoring it drops a success or a failure')]
    public function orElse(callable $fn): Result
    {
        return $fn($this->error);
    }

    #[\Override]
    #[\NoDiscard('ignoring it drops a success or a failure')]
    public function transpose(): Option
    {
        return new Some($this);
    }

    #[\Override]
    public function match(callable $Ok, callable $Err): mixed
    {
        return $Err($this->error);
    }

    /**
     * The exception for a failed unwrap. A `\Throwable` error rides along as
     * `previous`, so its stack trace reaches the log.
     */
    private function unwrapFailure(string $message): \RuntimeException
    {
        return new \RuntimeException(
            $message.': '.self::describe($this->error),
            previous: $this->error instanceof \Throwable ? $this->error : null,
        );
    }
}
