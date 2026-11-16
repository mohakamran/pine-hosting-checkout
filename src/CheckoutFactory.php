<?php

declare(strict_types=1);

namespace Checkout;

use Checkout\Code\PercentOffCode;
use Checkout\Offer\BulkPriceOffer;
use Checkout\Offer\MultiBuyOffer;

/** Wires up the catalog, offers and codes from the brief. */
final class CheckoutFactory
{
    public static function default(): Checkout
    {
        $catalog = new Catalog([
            new Product('NB', 'Notebook', Money::fromString('4.00')),
            new Product('PEN', 'Pen pack', Money::fromString('5.00')),
            new Product('MUG', 'Mug', Money::fromString('8.50')),
            new Product('TOTE', 'Tote bag', Money::fromString('12.00')),
        ]);

        $offers = [
            new MultiBuyOffer('NB', 3, 2, '3-for-2'),
            new BulkPriceOffer('PEN', 5, Money::fromString('4.50'), 'bulk pens'),
        ];

        $codes = [
            new PercentOffCode('SAVE10', 10),
        ];

        return new Checkout($catalog, $offers, $codes);
    }
}
