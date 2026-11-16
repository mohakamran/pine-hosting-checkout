<?php

declare(strict_types=1);

namespace Checkout;

final readonly class Receipt
{
    /**
     * @param list<PricedLine> $lines
     * @param list<Discount> $discounts
     */
    public function __construct(
        public array $lines,
        public Money $subtotal,
        public array $discounts,
        public Money $total,
    ) {
    }
}
