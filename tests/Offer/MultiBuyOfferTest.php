<?php

declare(strict_types=1);

namespace Checkout\Tests\Offer;

use Checkout\Discount;
use Checkout\Money;
use Checkout\Offer\MultiBuyOffer;
use Checkout\PricedLine;
use Checkout\Product;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class MultiBuyOfferTest extends TestCase
{
    /** @return iterable<string, array{int, int}> */
    public static function quantities(): iterable
    {
        yield '1 notebook' => [1, 0];
        yield '2 notebooks' => [2, 0];
        yield '3 notebooks' => [3, 400];
        yield '5 notebooks' => [5, 400];
        yield '6 notebooks' => [6, 800];
        yield '7 notebooks' => [7, 800];
    }

    #[DataProvider('quantities')]
    public function testThreeForTwoPerFullGroup(int $quantity, int $expectedDiscountCents): void
    {
        $offer = new MultiBuyOffer('NB', 3, 2, '3-for-2');
        $notebook = new Product('NB', 'Notebook', Money::fromString('4.00'));
        $line = new PricedLine($notebook, $quantity, $notebook->unitPrice->multiply($quantity));

        $discounts = $offer->apply([$line]);

        $total = array_sum(array_map(static fn (Discount $d): int => $d->amount->cents(), $discounts));
        self::assertSame($expectedDiscountCents, $total);
        if ($discounts !== []) {
            self::assertSame('3-for-2', $discounts[0]->label);
        }
    }

    public function testIgnoresOtherSkus(): void
    {
        $offer = new MultiBuyOffer('NB', 3, 2, '3-for-2');
        $mug = new Product('MUG', 'Mug', Money::fromString('8.50'));

        self::assertSame([], $offer->apply([new PricedLine($mug, 3, $mug->unitPrice->multiply(3))]));
    }

    public function testRejectsNonsenseConfiguration(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new MultiBuyOffer('NB', 2, 3, 'bad');
    }
}
