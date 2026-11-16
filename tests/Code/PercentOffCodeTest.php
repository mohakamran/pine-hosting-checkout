<?php

declare(strict_types=1);

namespace Checkout\Tests\Code;

use Checkout\Code\PercentOffCode;
use Checkout\Money;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class PercentOffCodeTest extends TestCase
{
    public function testTakesPercentageOffWithCodeAsLabel(): void
    {
        $code = new PercentOffCode('SAVE10', 10);

        $discount = $code->apply(Money::fromString('39.00'));

        self::assertSame('SAVE10', $code->code());
        self::assertSame('SAVE10', $discount->label);
        self::assertSame('3.90', $discount->amount->format());
    }

    public function testRejectsOutOfRangePercent(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new PercentOffCode('SAVE200', 200);
    }
}
