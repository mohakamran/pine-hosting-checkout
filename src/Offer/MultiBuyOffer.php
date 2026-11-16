<?php

declare(strict_types=1);

namespace Checkout\Offer;

use Checkout\Discount;
use InvalidArgumentException;

/** "Buy $buy, pay for $payFor", applied once per full group, e.g. 3 for the price of 2. */
final readonly class MultiBuyOffer implements Offer
{
    public function __construct(
        private string $sku,
        private int $buy,
        private int $payFor,
        private string $label,
    ) {
        if ($payFor < 0 || $buy <= $payFor) {
            throw new InvalidArgumentException('Multi-buy needs buy > payFor >= 0.');
        }
    }

    public function apply(array $lines): array
    {
        $discounts = [];
        foreach ($lines as $line) {
            if ($line->product->sku !== $this->sku) {
                continue;
            }

            $fullGroups = intdiv($line->quantity, $this->buy);
            $freeUnits = $fullGroups * ($this->buy - $this->payFor);
            if ($freeUnits > 0) {
                $discounts[] = new Discount($this->label, $line->product->unitPrice->multiply($freeUnits));
            }
        }

        return $discounts;
    }
}
