<?php

declare(strict_types=1);

namespace Checkout;

use Checkout\Code\DiscountCode;
use Checkout\Exception\UnknownDiscountCode;
use Checkout\Offer\Offer;
use InvalidArgumentException;
use LogicException;

/**
 * Prices a basket: subtotal -> item offers (in configured order) -> discount
 * code on what is left -> total.
 */
final readonly class Checkout
{
    /** @var array<string, DiscountCode> */
    private array $codesByName;

    /**
     * @param list<Offer> $offers
     * @param list<DiscountCode> $codes
     */
    public function __construct(
        private Catalog $catalog,
        private array $offers,
        array $codes,
    ) {
        $byName = [];
        foreach ($codes as $code) {
            if (isset($byName[$code->code()])) {
                throw new InvalidArgumentException(sprintf('Duplicate discount code "%s".', $code->code()));
            }
            $byName[$code->code()] = $code;
        }
        $this->codesByName = $byName;
    }

    public function price(Basket $basket): Receipt
    {
        // 1. Price each line at full price and add up the subtotal.
        $lines = [];
        $subtotal = Money::zero();
        foreach ($basket->quantities() as $sku => $quantity) {
            $product = $this->catalog->get($sku);
            $line = new PricedLine($product, $quantity, $product->unitPrice->multiply($quantity));
            $lines[] = $line;
            $subtotal = $subtotal->add($line->lineTotal);
        }

        // 2. Each offer looks at the lines and returns zero or more discounts.
        $discounts = [];
        foreach ($this->offers as $offer) {
            foreach ($offer->apply($lines) as $discount) {
                $discounts[] = $discount;
            }
        }

        // 3. The code (at most one) applies to the total after item offers.
        $code = $basket->code();
        if ($code !== null) {
            $afterOffers = self::totalOrFail($subtotal, $discounts);
            $discounts[] = $this->findCode($code)->apply($afterOffers);
        }

        // 4. Final total.
        $total = self::totalOrFail($subtotal, $discounts);

        return new Receipt($lines, $subtotal, $discounts, $total);
    }

    /**
     * Guard: discounts must never be bigger than the basket.
     *
     * @param list<Discount> $discounts
     */
    private static function totalOrFail(Money $subtotal, array $discounts): Money
    {
        $discountTotal = Money::zero();
        foreach ($discounts as $discount) {
            $discountTotal = $discountTotal->add($discount->amount);
        }

        $total = $subtotal->subtract($discountTotal);
        if ($total->isNegative()) {
            throw new LogicException(sprintf(
                'Discounts (%s) exceed the subtotal (%s).',
                $discountTotal->format(),
                $subtotal->format(),
            ));
        }

        return $total;
    }

    private function findCode(string $code): DiscountCode
    {
        return $this->codesByName[$code] ?? throw UnknownDiscountCode::forCode($code);
    }
}
