<?php

declare(strict_types=1);

namespace Checkout;

use InvalidArgumentException;

/**
 * An exact amount of money, stored as a whole number of cents.
 * Floats are never used, so 0.1 + 0.2 problems cannot happen.
 */
final readonly class Money
{
    private function __construct(private int $cents)
    {
    }

    public static function fromCents(int $cents): self
    {
        return new self($cents);
    }

    public static function zero(): self
    {
        return new self(0);
    }

    /** Parses a non-negative amount such as "4", "4.5" or "4.50". */
    public static function fromString(string $amount): self
    {
        if (preg_match('/^\d+(\.\d{1,2})?$/', $amount) !== 1) {
            throw new InvalidArgumentException(sprintf('Invalid money amount "%s".', $amount));
        }

        $parts = explode('.', $amount);
        $whole = (int) $parts[0];
        $fraction = (int) str_pad($parts[1] ?? '0', 2, '0');

        return new self($whole * 100 + $fraction);
    }

    public function cents(): int
    {
        return $this->cents;
    }

    public function add(self $other): self
    {
        return new self($this->cents + $other->cents);
    }

    public function subtract(self $other): self
    {
        return new self($this->cents - $other->cents);
    }

    public function multiply(int $quantity): self
    {
        return new self($this->cents * $quantity);
    }

    public function negate(): self
    {
        return new self(-$this->cents);
    }

    /**
     * Returns $percent % of this amount, rounded to the nearest cent, halves up.
     */
    public function percentage(int $percent): self
    {
        if ($this->cents < 0 || $percent < 0) {
            throw new InvalidArgumentException('Percentage needs a non-negative amount and percent.');
        }

        // The exact result is (cents * percent) / 100. Adding 50 (half of the
        // divisor) before intdiv(), which rounds down for non-negative numbers,
        // pushes anything with a remainder of 50 or more up to the next cent:
        // 5c * 10% = 50/100 = 0.5 -> (50 + 50) / 100 = 1c; 4c * 10% -> (40 + 50) / 100 = 0c.
        return new self(intdiv($this->cents * $percent + 50, 100));
    }

    public function isNegative(): bool
    {
        return $this->cents < 0;
    }

    public function equals(self $other): bool
    {
        return $this->cents === $other->cents;
    }

    /** Formats as "12.00", "0.05" or "-4.00". */
    public function format(): string
    {
        $sign = $this->cents < 0 ? '-' : '';
        $absolute = abs($this->cents);

        return sprintf('%s%d.%02d', $sign, intdiv($absolute, 100), $absolute % 100);
    }
}
