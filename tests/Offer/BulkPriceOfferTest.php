<?php

declare(strict_types=1);

namespace Checkout\Tests\Offer;

use Checkout\Money;
use Checkout\Offer\BulkPriceOffer;
use Checkout\PricedLine;
use Checkout\Product;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class BulkPriceOfferTest extends TestCase
{
    /** @return iterable<string, array{int, ?string}> */
    public static function quantities(): iterable
    {
        yield '4 pens: below minimum' => [4, null];
        yield '5 pens: every pen repriced' => [5, '2.50'];
        yield '8 pens' => [8, '4.00'];
    }

    #[DataProvider('quantities')]
    public function testRepricesEveryUnitOnceMinimumReached(int $quantity, ?string $expectedDiscount): void
    {
        $offer = new BulkPriceOffer('PEN', 5, Money::fromString('4.50'), 'bulk pens');
        $pen = new Product('PEN', 'Pen pack', Money::fromString('5.00'));

        $discounts = $offer->apply([new PricedLine($pen, $quantity, $pen->unitPrice->multiply($quantity))]);

        if ($expectedDiscount === null) {
            self::assertSame([], $discounts);

            return;
        }
        self::assertCount(1, $discounts);
        self::assertSame('bulk pens', $discounts[0]->label);
        self::assertSame($expectedDiscount, $discounts[0]->amount->format());
    }
}
