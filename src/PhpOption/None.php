<?php

declare(strict_types=1);

namespace Brimmar\PhpOption;

use ArrayIterator;
use Brimmar\PhpResult\Err;
use Brimmar\PhpResult\Ok;
use Brimmar\PhpResult\Result;
use Iterator;

/**
 * @template-covariant T = never
 *
 * @extends Option<T>
 */
final class None extends Option
{
    #[\Override]
    public function isSome(): bool
    {
        return false;
    }

    #[\Override]
    public function isSomeAnd(callable $fn): bool
    {
        return false;
    }

    #[\Override]
    public function isNone(): bool
    {
        return true;
    }

    #[\Override]
    public function iter(): Iterator
    {
        return new ArrayIterator([]);
    }

    #[\Override]
    public function unwrap(): never
    {
        throw new \RuntimeException('Called unwrap on a None value');
    }

    #[\Override]
    public function expect(string $msg): never
    {
        throw new \RuntimeException($msg);
    }

    #[\Override]
    #[\NoDiscard('ignoring it drops a success or a failure')]
    public function flatten(): Option
    {
        return $this;
    }

    #[\Override]
    public function unwrapOr(mixed $default): mixed
    {
        return $default;
    }

    #[\Override]
    public function unwrapOrElse(callable $default): mixed
    {
        return $default();
    }

    #[\Override]
    #[\NoDiscard('ignoring it drops a success or a failure')]
    public function map(callable $fn): Option
    {
        return new None;
    }

    #[\Override]
    public function mapOr(mixed $default, callable $fn): mixed
    {
        return $default;
    }

    #[\Override]
    public function mapOrElse(callable $default, callable $fn): mixed
    {
        return $default();
    }

    #[\Override]
    #[\NoDiscard('ignoring it drops a success or a failure')]
    public function inspect(callable $fn): Option
    {
        return $this;
    }

    #[\Override]
    #[\NoDiscard('ignoring it drops a success or a failure')]
    public function okOr(mixed $error): Result
    {
        return new Err($error);
    }

    #[\Override]
    #[\NoDiscard('ignoring it drops a success or a failure')]
    public function okOrElse(callable $error): Result
    {
        return new Err($error());
    }

    #[\Override]
    #[\NoDiscard('ignoring it drops a success or a failure')]
    public function and(Option $opt): Option
    {
        return new None;
    }

    #[\Override]
    #[\NoDiscard('ignoring it drops a success or a failure')]
    public function andThen(callable $fn): Option
    {
        return new None;
    }

    #[\Override]
    #[\NoDiscard('ignoring it drops a success or a failure')]
    public function or(Option $opt): Option
    {
        return $opt;
    }

    #[\Override]
    #[\NoDiscard('ignoring it drops a success or a failure')]
    public function orElse(callable $fn): Option
    {
        return $fn();
    }

    #[\Override]
    #[\NoDiscard('ignoring it drops a success or a failure')]
    public function transpose(): Result
    {
        return new Ok(new None);
    }

    #[\Override]
    #[\NoDiscard('ignoring it drops a success or a failure')]
    public function xor(Option $opt): Option
    {
        if ($opt instanceof Some) {
            return $opt;
        }

        return new None;
    }

    #[\Override]
    #[\NoDiscard('ignoring it drops a success or a failure')]
    public function zip(Option $other): Option
    {
        return new None;
    }

    #[\Override]
    #[\NoDiscard('ignoring it drops a success or a failure')]
    public function zipWith(Option $other, callable $fn): Option
    {
        return new None;
    }

    #[\Override]
    #[\NoDiscard('ignoring it drops a success or a failure')]
    public function unzip(): array
    {
        return [new None, new None];
    }

    #[\Override]
    public function match(callable $Some, callable $None): mixed
    {
        return $None();
    }

    #[\Override]
    #[\NoDiscard('ignoring it drops a success or a failure')]
    public function filter(callable $predicate): Option
    {
        return $this;
    }
}
