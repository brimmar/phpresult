<?php

declare(strict_types=1);

namespace Brimmar\PhpOption;

use ArrayIterator;
use Brimmar\PhpResult\Err;
use Brimmar\PhpResult\Ok;
use Brimmar\PhpResult\Result;
use Iterator;

/**
 * @template-covariant T
 *
 * @extends Option<T>
 */
final class Some extends Option
{
    /** @param T $value */
    public function __construct(
        private readonly mixed $value,
    ) {}

    #[\Override]
    public function isSome(): bool
    {
        return true;
    }

    #[\Override]
    public function isSomeAnd(callable $fn): bool
    {
        return $fn($this->value);
    }

    #[\Override]
    public function isNone(): bool
    {
        return false;
    }

    #[\Override]
    public function iter(): Iterator
    {
        return new ArrayIterator([$this->value]);
    }

    #[\Override]
    public function unwrap(): mixed
    {
        return $this->value;
    }

    #[\Override]
    public function expect(string $msg): mixed
    {
        return $this->value;
    }

    #[\Override]
    #[\NoDiscard('ignoring it drops a success or a failure')]
    public function flatten(): Option
    {
        if ($this->value instanceof Option) {
            return $this->value;
        }

        return $this;
    }

    #[\Override]
    public function unwrapOr(mixed $default): mixed
    {
        return $this->value;
    }

    #[\Override]
    public function unwrapOrElse(callable $default): mixed
    {
        return $this->value;
    }

    #[\Override]
    #[\NoDiscard('ignoring it drops a success or a failure')]
    public function map(callable $fn): Option
    {
        return new Some($fn($this->value));
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
    public function inspect(callable $fn): Option
    {
        $fn($this->value);

        return $this;
    }

    #[\Override]
    #[\NoDiscard('ignoring it drops a success or a failure')]
    public function okOr(mixed $error): Result
    {
        return new Ok($this->value);
    }

    #[\Override]
    #[\NoDiscard('ignoring it drops a success or a failure')]
    public function okOrElse(callable $error): Result
    {
        return new Ok($this->value);
    }

    #[\Override]
    #[\NoDiscard('ignoring it drops a success or a failure')]
    public function and(Option $opt): Option
    {
        return $opt;
    }

    #[\Override]
    #[\NoDiscard('ignoring it drops a success or a failure')]
    public function andThen(callable $fn): Option
    {
        return $fn($this->value);
    }

    #[\Override]
    #[\NoDiscard('ignoring it drops a success or a failure')]
    public function or(Option $opt): Option
    {
        return $this;
    }

    #[\Override]
    #[\NoDiscard('ignoring it drops a success or a failure')]
    public function orElse(callable $fn): Option
    {
        return $this;
    }

    #[\Override]
    #[\NoDiscard('ignoring it drops a success or a failure')]
    public function transpose(): Result
    {
        if ($this->value instanceof Ok) {
            return new Ok(new Some($this->value->unwrap()));
        }

        if ($this->value instanceof Err) {
            return new Err($this->value->unwrapErr());
        }

        throw new \InvalidArgumentException('transpose() expects Some to hold a Result');
    }

    #[\Override]
    #[\NoDiscard('ignoring it drops a success or a failure')]
    public function xor(Option $opt): Option
    {
        if ($opt instanceof None) {
            return $this;
        }

        return new None;
    }

    #[\Override]
    #[\NoDiscard('ignoring it drops a success or a failure')]
    public function zip(Option $other): Option
    {
        if ($other instanceof None) {
            return new None;
        }

        return new Some([$this->value, $other->unwrap()]);
    }

    #[\Override]
    #[\NoDiscard('ignoring it drops a success or a failure')]
    public function zipWith(Option $other, callable $fn): Option
    {
        if ($other instanceof None) {
            return new None;
        }

        return new Some($fn($this->value, $other->unwrap()));
    }

    #[\Override]
    #[\NoDiscard('ignoring it drops a success or a failure')]
    public function unzip(): array
    {
        if (! is_array($this->value) || ! array_key_exists(0, $this->value) || ! array_key_exists(1, $this->value)) {
            return [new None, new None];
        }

        return [new Some($this->value[0]), new Some($this->value[1])];
    }

    #[\Override]
    public function match(callable $Some, callable $None): mixed
    {
        return $Some($this->value);
    }

    #[\Override]
    #[\NoDiscard('ignoring it drops a success or a failure')]
    public function filter(callable $predicate): Option
    {
        if ($predicate($this->value)) {
            return $this;
        }

        return new None;
    }
}
