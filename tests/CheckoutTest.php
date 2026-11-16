<?php

declare(strict_types=1);

namespace Checkout\Tests;

use Checkout\Basket;
use Checkout\Catalog;
use Checkout\Checkout;
use Checkout\CheckoutFactory;
use Checkout\Discount;
use Checkout\Exception\UnknownDiscountCode;
use Checkout\Exception\UnknownSku;
use Checkout\Money;
use Checkout\Offer\Offer;
use Checkout\Product;
use Checkout\Receipt;
use Checkout\ReceiptFormatter;
use LogicException;
use PHPUnit\Framework\TestCase;

final class CheckoutTest extends TestCase
{
    public function testBriefExampleTotals(): void
    {
        $receipt = $this->price(['NB' => 3, 'PEN' => 5, 'MUG' => 1], 'SAVE10');

        self::assertSame('45.50', $receipt->subtotal->format());
        self::assertSame(
            [['3-for-2', '4.00'], ['bulk pens', '2.50'], ['SAVE10', '3.90']],
            array_map(static fn (Discount $d): array => [$d->label, $d->amount->format()], $receipt->discounts),
        );
        self::assertSame('35.10', $receipt->total->format());
    }

    public function testBriefExampleReceiptText(): void
    {
        $receipt = $this->price(['NB' => 3, 'PEN' => 5, 'MUG' => 1], 'SAVE10');

        $expected = <<<TXT
            3 x Notebook               12.00
            5 x Pen pack               25.00
            1 x Mug                     8.50
            --------------------------------
            Subtotal                   45.50
            3-for-2                    -4.00
            bulk pens                  -2.50
            SAVE10                     -3.90
            --------------------------------
            Total                      35.10

            TXT;

        self::assertSame($expected, (new ReceiptFormatter())->format($receipt));
    }

    public function testCodeAppliesAfterOffersNotOnSubtotal(): void
    {
        // Subtotal 12.00, 3-for-2 takes 4.00, so SAVE10 is 10% of 8.00 (not of 12.00).
        $receipt = $this->price(['NB' => 3], 'SAVE10');

        self::assertSame('0.80', $receipt->discounts[1]->amount->format());
        self::assertSame('7.20', $receipt->total->format());
    }

    public function testNoOffersOrCode(): void
    {
        $receipt = $this->price(['MUG' => 1, 'TOTE' => 1]);

        self::assertSame([], $receipt->discounts);
        self::assertSame('20.50', $receipt->total->format());
    }

    public function testEmptyBasketCostsNothing(): void
    {
        $receipt = $this->price([]);

        self::assertSame([], $receipt->lines);
        self::assertSame('0.00', $receipt->total->format());
    }

    public function testRepeatedAddsAreMergedIntoOneLine(): void
    {
        $basket = new Basket();
        $basket->add('NB');
        $basket->add('NB', 2);

        $receipt = CheckoutFactory::default()->price($basket);

        self::assertCount(1, $receipt->lines);
        self::assertSame(3, $receipt->lines[0]->quantity);
        self::assertSame('8.00', $receipt->total->format());
    }

    public function testUnknownCodeThrows(): void
    {
        $this->expectException(UnknownDiscountCode::class);
        $this->expectExceptionMessage('Unknown discount code "save10".');

        $this->price(['NB' => 1], 'save10');
    }

    public function testUnknownSkuThrows(): void
    {
        $this->expectException(UnknownSku::class);

        $this->price(['HAT' => 1]);
    }

    public function testThrowsIfDiscountsExceedSubtotal(): void
    {
        $tooGenerous = new class () implements Offer {
            public function apply(array $lines): array
            {
                return [new Discount('too generous', Money::fromString('100.00'))];
            }
        };
        $checkout = new Checkout(
            new Catalog([new Product('MUG', 'Mug', Money::fromString('8.50'))]),
            [$tooGenerous],
            [],
        );
        $basket = new Basket();
        $basket->add('MUG');

        $this->expectException(LogicException::class);

        $checkout->price($basket);
    }

    /** @param array<string, int> $items */
    private function price(array $items, ?string $code = null): Receipt
    {
        $basket = new Basket();
        foreach ($items as $sku => $quantity) {
            $basket->add($sku, $quantity);
        }
        if ($code !== null) {
            $basket->applyCode($code);
        }

        return CheckoutFactory::default()->price($basket);
    }
}
