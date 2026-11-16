<?php

declare(strict_types=1);

namespace Checkout\Tests;

use Checkout\Money;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class MoneyTest extends TestCase
{
    /** @return iterable<string, array{string, int}> */
    public static function validStrings(): iterable
    {
        yield 'whole number' => ['4', 400];
        yield 'one decimal' => ['4.5', 450];
        yield 'two decimals' => ['4.50', 450];
        yield 'cents only' => ['0.05', 5];
        yield 'zero' => ['0', 0];
    }

    #[DataProvider('validStrings')]
    public function testParsesValidStrings(string $input, int $expectedCents): void
    {
        self::assertSame($expectedCents, Money::fromString($input)->cents());
    }

    /** @return iterable<string, array{string}> */
    public static function invalidStrings(): iterable
    {
        yield 'three decimals' => ['4.005'];
        yield 'negative' => ['-1'];
        yield 'letters' => ['abc'];
        yield 'empty' => [''];
        yield 'trailing dot' => ['4.'];
    }

    #[DataProvider('invalidStrings')]
    public function testRejectsInvalidStrings(string $input): void
    {
        $this->expectException(InvalidArgumentException::class);

        Money::fromString($input);
    }

    /** @return iterable<string, array{int, string}> */
    public static function formats(): iterable
    {
        yield 'whole' => [1200, '12.00'];
        yield 'cents only' => [5, '0.05'];
        yield 'zero' => [0, '0.00'];
        yield 'negative' => [-400, '-4.00'];
        yield 'negative cents only' => [-5, '-0.05'];
    }

    #[DataProvider('formats')]
    public function testFormats(int $cents, string $expected): void
    {
        self::assertSame($expected, Money::fromCents($cents)->format());
    }

    public function testArithmetic(): void
    {
        $four = Money::fromString('4.00');

        self::assertSame(650, $four->add(Money::fromString('2.50'))->cents());
        self::assertSame(150, $four->subtract(Money::fromString('2.50'))->cents());
        self::assertSame(1200, $four->multiply(3)->cents());
        self::assertSame(-400, $four->negate()->cents());
        self::assertTrue($four->subtract(Money::fromString('5'))->isNegative());
        self::assertTrue($four->equals(Money::fromCents(400)));
    }

    /** @return iterable<string, array{int, int}> */
    public static function tenPercentRounding(): iterable
    {
        yield '5c rounds half up to 1c' => [5, 1];
        yield '4c rounds down to 0c' => [4, 0];
        yield '15c rounds half up to 2c' => [15, 2];
        yield '14c rounds down to 1c' => [14, 1];
        yield 'brief example 39.00' => [3900, 390];
    }

    #[DataProvider('tenPercentRounding')]
    public function testPercentageRoundsHalfUp(int $cents, int $expected): void
    {
        self::assertSame($expected, Money::fromCents($cents)->percentage(10)->cents());
    }

    public function testPercentageRejectsNegativeAmount(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Money::fromCents(-100)->percentage(10);
    }
}
