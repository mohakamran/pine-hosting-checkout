<?php

declare(strict_types=1);

namespace Checkout\Offer;

use Checkout\Discount;
use Checkout\PricedLine;

/**
 * An item offer. To add a new kind of offer, implement this interface and
 * pass an instance to Checkout; no existing code needs to change.
 */
interface Offer
{
    /**
     * @param list<PricedLine> $lines
     * @return list<Discount>
     */
    public function apply(array $lines): array;
}
