<?php

declare(strict_types=1);

namespace Checkout\Tests;

use Checkout\Catalog;
use Checkout\Exception\UnknownSku;
use Checkout\Money;
use Checkout\Product;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class CatalogTest extends TestCase
{
    public function testFindsProductBySku(): void
    {
        $mug = new Product('MUG', 'Mug', Money::fromString('8.50'));
        $catalog = new Catalog([$mug]);

        self::assertSame($mug, $catalog->get('MUG'));
    }

    public function testThrowsOnUnknownSku(): void
    {
        $catalog = new Catalog([]);

        $this->expectException(UnknownSku::class);
        $this->expectExceptionMessage('Unknown SKU "HAT".');

        $catalog->get('HAT');
    }

    public function testRejectsDuplicateSkus(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Catalog([
            new Product('MUG', 'Mug', Money::fromString('8.50')),
            new Product('MUG', 'Other mug', Money::fromString('9.00')),
        ]);
    }
}
