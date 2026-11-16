<?php

declare(strict_types=1);

namespace Checkout\Tests\Web;

use Checkout\CheckoutFactory;
use Checkout\Web\BasketForm;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class BasketFormTest extends TestCase
{
    public function testBuildsBasketFromFormInput(): void
    {
        $basket = BasketForm::toBasket(
            ['qty' => ['NB' => '3', 'PEN' => ' 5 ', 'MUG' => '1', 'TOTE' => ''], 'code' => ' SAVE10 '],
            CheckoutFactory::catalog(),
        );

        self::assertSame(['NB' => 3, 'PEN' => 5, 'MUG' => 1], $basket->quantities());
        self::assertSame('SAVE10', $basket->code());
    }

    public function testBlankAndZeroQuantitiesAndCodeAreSkipped(): void
    {
        $basket = BasketForm::toBasket(['qty' => ['NB' => '0', 'PEN' => ''], 'code' => ''], CheckoutFactory::catalog());

        self::assertSame([], $basket->quantities());
        self::assertNull($basket->code());
    }

    public function testMissingOrMalformedInputGivesEmptyBasket(): void
    {
        $basket = BasketForm::toBasket(['qty' => 'not-an-array'], CheckoutFactory::catalog());

        self::assertSame([], $basket->quantities());
    }

    /** @return iterable<string, array{mixed}> */
    public static function badQuantities(): iterable
    {
        yield 'negative' => ['-1'];
        yield 'decimal' => ['1.5'];
        yield 'letters' => ['abc'];
        yield 'too many' => ['1000'];
        yield 'not a string' => [['3']];
    }

    #[DataProvider('badQuantities')]
    public function testRejectsBadQuantities(mixed $quantity): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Quantity for Notebook');

        BasketForm::toBasket(['qty' => ['NB' => $quantity]], CheckoutFactory::catalog());
    }
}
