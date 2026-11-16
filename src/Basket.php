<?php

declare(strict_types=1);

namespace Checkout;

use Checkout\Exception\CodeAlreadyApplied;
use InvalidArgumentException;

/**
 * What the customer wants to buy. It only holds SKUs, quantities and a code;
 * prices are worked out later by Checkout.
 */
final class Basket
{
    /** @var array<string, int> SKU => quantity, in first-added order */
    private array $quantities = [];

    private ?string $code = null;

    public function add(string $sku, int $quantity = 1): void
    {
        if ($quantity < 1) {
            throw new InvalidArgumentException(sprintf('Quantity must be at least 1, got %d.', $quantity));
        }

        $this->quantities[$sku] = ($this->quantities[$sku] ?? 0) + $quantity;
    }

    public function applyCode(string $code): void
    {
        if ($this->code !== null) {
            throw CodeAlreadyApplied::because($this->code, $code);
        }

        $this->code = $code;
    }

    /** @return array<string, int> */
    public function quantities(): array
    {
        return $this->quantities;
    }

    public function code(): ?string
    {
        return $this->code;
    }
}
