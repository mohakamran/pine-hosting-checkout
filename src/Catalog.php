<?php

declare(strict_types=1);

namespace Checkout;

use Checkout\Exception\UnknownSku;
use InvalidArgumentException;

final readonly class Catalog
{
    /** @var array<string, Product> */
    private array $productsBySku;

    /** @param list<Product> $products */
    public function __construct(array $products)
    {
        $bySku = [];
        foreach ($products as $product) {
            if (isset($bySku[$product->sku])) {
                throw new InvalidArgumentException(sprintf('Duplicate SKU "%s" in catalog.', $product->sku));
            }
            $bySku[$product->sku] = $product;
        }
        $this->productsBySku = $bySku;
    }

    public function get(string $sku): Product
    {
        return $this->productsBySku[$sku] ?? throw UnknownSku::forSku($sku);
    }
}
