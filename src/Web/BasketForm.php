<?php

declare(strict_types=1);

namespace Checkout\Web;

use Checkout\Basket;
use Checkout\Catalog;
use InvalidArgumentException;

/**
 * Turns the web form's input into a Basket. Kept out of public/index.php so it
 * can be unit tested; the page itself only renders.
 */
final class BasketForm
{
    private const MAX_QUANTITY = 999;

    /**
     * Expects input shaped like ['qty' => ['NB' => '3', ...], 'code' => 'SAVE10'].
     * Blank or 0 quantities are skipped; anything else must be a whole number.
     *
     * @param array<mixed> $input usually $_GET
     */
    public static function toBasket(array $input, Catalog $catalog): Basket
    {
        $basket = new Basket();
        $quantities = is_array($input['qty'] ?? null) ? $input['qty'] : [];

        foreach ($catalog->all() as $product) {
            $raw = $quantities[$product->sku] ?? '';
            $raw = is_string($raw) ? trim($raw) : 'invalid';
            if ($raw === '' || $raw === '0') {
                continue;
            }

            if (!ctype_digit($raw) || (int) $raw > self::MAX_QUANTITY) {
                throw new InvalidArgumentException(sprintf(
                    'Quantity for %s must be a whole number from 0 to %d.',
                    $product->name,
                    self::MAX_QUANTITY,
                ));
            }

            $basket->add($product->sku, (int) $raw);
        }

        $code = $input['code'] ?? '';
        if (is_string($code) && trim($code) !== '') {
            $basket->applyCode(trim($code));
        }

        return $basket;
    }
}
