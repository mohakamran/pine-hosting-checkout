<?php

declare(strict_types=1);

namespace Checkout\Tests;

use Checkout\Basket;
use Checkout\Exception\CodeAlreadyApplied;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class BasketTest extends TestCase
{
    public function testMergesRepeatedAddsInFirstAddedOrder(): void
    {
        $basket = new Basket();
        $basket->add('NB');
        $basket->add('MUG', 2);
        $basket->add('NB', 2);

        self::assertSame(['NB' => 3, 'MUG' => 2], $basket->quantities());
    }

    /** @return iterable<string, array{int}> */
    public static function invalidQuantities(): iterable
    {
        yield 'zero' => [0];
        yield 'negative' => [-1];
    }

    #[DataProvider('invalidQuantities')]
    public function testRejectsInvalidQuantity(int $quantity): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new Basket())->add('NB', $quantity);
    }

    public function testAllowsOnlyOneCode(): void
    {
        $basket = new Basket();
        $basket->applyCode('SAVE10');

        $this->expectException(CodeAlreadyApplied::class);

        $basket->applyCode('SAVE20');
    }
}
