<?php

declare(strict_types=1);

namespace Checkout\Code;

use Checkout\Discount;
use Checkout\Money;

/** A code the customer types in. It is applied after all item offers. */
interface DiscountCode
{
    public function code(): string;

    public function apply(Money $totalAfterOffers): Discount;
}
