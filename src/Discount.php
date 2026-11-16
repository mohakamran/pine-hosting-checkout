<?php

declare(strict_types=1);

namespace Checkout;

/** A saving shown on the receipt. The amount is positive; the receipt prints it as negative. */
final readonly class Discount
{
    public function __construct(
        public string $label,
        public Money $amount,
    ) {
    }
}
