<?php

declare(strict_types=1);

namespace Checkout\Offer;

use Checkout\Discount;
use Checkout\Money;
use InvalidArgumentException;

/** Once quantity reaches $minQuantity, every unit costs $bulkUnitPrice instead. */
final readonly class BulkPriceOffer implements Offer
{
    public function __construct(
        private string $sku,
        private int $minQuantity,
        private Money $bulkUnitPrice,
        private string $label,
    ) {
        if ($minQuantity < 1) {
            throw new InvalidArgumentException('Bulk offer minimum quantity must be at least 1.');
        }
    }

    public function apply(array $lines): array
    {
        $discounts = [];
        foreach ($lines as $line) {
            if ($line->product->sku !== $this->sku || $line->quantity < $this->minQuantity) {
                continue;
            }

            $savingPerUnit = $line->product->unitPrice->subtract($this->bulkUnitPrice);
            if ($savingPerUnit->cents() > 0) {
                $discounts[] = new Discount($this->label, $savingPerUnit->multiply($line->quantity));
            }
        }

        return $discounts;
    }
}
