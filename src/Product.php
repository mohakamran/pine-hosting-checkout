<?php

declare(strict_types=1);

namespace Checkout;

final readonly class Product
{
    public function __construct(
        public string $sku,
        public string $name,
        public Money $unitPrice,
    ) {
    }
}
