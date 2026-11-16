<?php

declare(strict_types=1);

namespace Checkout;

/** One basket line at full price, e.g. 3 x Notebook = 12.00. */
final readonly class PricedLine
{
    public function __construct(
        public Product $product,
        public int $quantity,
        public Money $lineTotal,
    ) {
    }
}
