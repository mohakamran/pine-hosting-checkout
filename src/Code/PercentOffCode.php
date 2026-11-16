<?php

declare(strict_types=1);

namespace Checkout\Code;

use Checkout\Discount;
use Checkout\Money;
use InvalidArgumentException;

final readonly class PercentOffCode implements DiscountCode
{
    public function __construct(
        private string $code,
        private int $percent,
    ) {
        if ($percent < 1 || $percent > 100) {
            throw new InvalidArgumentException('Percent must be between 1 and 100.');
        }
    }

    public function code(): string
    {
        return $this->code;
    }

    public function apply(Money $totalAfterOffers): Discount
    {
        return new Discount($this->code, $totalAfterOffers->percentage($this->percent));
    }
}
