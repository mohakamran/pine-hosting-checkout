<?php

declare(strict_types=1);

namespace Checkout\Tests;

use Checkout\Basket;
use Checkout\Catalog;
use Checkout\Checkout;
use Checkout\Discount;
use Checkout\Money;
use Checkout\Offer\MultiBuyOffer;
use Checkout\Offer\Offer;
use Checkout\PricedLine;
use Checkout\Product;
use PHPUnit\Framework\TestCase;

/**
 * Proves a brand-new offer can be added without touching anything in src/:
 * write a class that implements Offer and pass it to Checkout.
 */
final class ExtensibilityTest extends TestCase
{
    public function testNewOfferPlugsInWithoutChangingSrc(): void
    {
        $bundle = new class () implements Offer {
            /** 1.00 off for each mug + tote pair. */
            public function apply(array $lines): array
            {
                $quantities = [];
                foreach ($lines as $line) {
                    $quantities[$line->product->sku] = $line->quantity;
                }
                $pairs = min($quantities['MUG'] ?? 0, $quantities['TOTE'] ?? 0);

                return $pairs > 0
                    ? [new Discount('mug + tote bundle', Money::fromString('1.00')->multiply($pairs))]
                    : [];
            }
        };

        $checkout = new Checkout(
            new Catalog([
                new Product('NB', 'Notebook', Money::fromString('4.00')),
                new Product('MUG', 'Mug', Money::fromString('8.50')),
                new Product('TOTE', 'Tote bag', Money::fromString('12.00')),
            ]),
            [new MultiBuyOffer('NB', 3, 2, '3-for-2'), $bundle],
            [],
        );

        $basket = new Basket();
        $basket->add('NB', 3);
        $basket->add('MUG');
        $basket->add('TOTE');
        $receipt = $checkout->price($basket);

        // 12.00 + 8.50 + 12.00 = 32.50, minus 4.00 (3-for-2) minus 1.00 (bundle) = 27.50
        self::assertSame(
            ['3-for-2', 'mug + tote bundle'],
            array_map(static fn (Discount $d): string => $d->label, $receipt->discounts),
        );
        self::assertSame('27.50', $receipt->total->format());
    }

    public function testOffersSeeFullPriceLines(): void
    {
        $spy = new class () implements Offer {
            /** @var list<PricedLine> */
            public array $seen = [];

            public function apply(array $lines): array
            {
                $this->seen = $lines;

                return [];
            }
        };

        $checkout = new Checkout(new Catalog([new Product('MUG', 'Mug', Money::fromString('8.50'))]), [$spy], []);
        $basket = new Basket();
        $basket->add('MUG', 2);
        $checkout->price($basket);

        self::assertCount(1, $spy->seen);
        self::assertSame('17.00', $spy->seen[0]->lineTotal->format());
    }
}
