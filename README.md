# Checkout pricing

A small PHP library that prices a shopping basket (item offers + one discount code) and prints a receipt.

## How to run the code and its tests

Requires **PHP 8.2+** and **Composer**.

```bash
composer install   # install dev tools (PHPUnit, PHPStan, PHP CS Fixer)
composer test      # run the PHPUnit tests
composer check     # code style + static analysis (PHPStan level max) + tests
composer demo      # print the receipt for the example in the brief
```

Other scripts: `composer analyse` (PHPStan only), `composer cs` (style check), `composer cs:fix` (fix style).

Example output of `composer demo` (3 × NB, 5 × PEN, 1 × MUG, code SAVE10):

```
3 x Notebook               12.00
5 x Pen pack               25.00
1 x Mug                     8.50
--------------------------------
Subtotal                   45.50
3-for-2                    -4.00
bulk pens                  -2.50
SAVE10                     -3.90
--------------------------------
Total                      35.10
```

## Design overview

| File | Responsibility |
|---|---|
| `src/Money.php` | Exact money as an `int` of cents: parse, add, subtract, multiply, percentage (half-up), format |
| `src/Product.php` | SKU, name, unit price |
| `src/Catalog.php` | Look up a product by SKU; rejects duplicates, throws `UnknownSku` |
| `src/Basket.php` | SKUs and quantities (repeats merged, first-added order) plus at most one code |
| `src/PricedLine.php` | A basket line at full price (product, quantity, line total) |
| `src/Discount.php` | One receipt discount line (label, amount) |
| `src/Offer/Offer.php` | Interface: given the priced lines, return zero or more discounts |
| `src/Offer/MultiBuyOffer.php` | "Buy N, pay for M" per full group (3-for-2 notebooks) |
| `src/Offer/BulkPriceOffer.php` | Reprice every unit once quantity ≥ minimum (pens at 4.50) |
| `src/Code/DiscountCode.php` | Interface for a typed-in code applied after offers |
| `src/Code/PercentOffCode.php` | Percentage off (SAVE10) |
| `src/Checkout.php` | `price(Basket): Receipt` — subtotal → offers → code → total |
| `src/Receipt.php` / `src/ReceiptFormatter.php` | Result data, and its fixed-width text rendering |
| `src/CheckoutFactory.php` | Wires up the catalog, offers and code from the brief |
| `src/Exception/*` | `UnknownSku`, `UnknownDiscountCode`, `CodeAlreadyApplied` |
| `bin/demo.php` | Prints the example receipt |

**Why integer cents.** Floats cannot represent most decimal amounts exactly (`0.1 + 0.2 !== 0.3`), so totals can drift by a cent. Storing whole cents in an `int` makes addition, subtraction and multiplication exact. The one place that divides (percentages) uses integer maths with an explicit half-up rule: `intdiv($cents * $percent + 50, 100)`.

**How to add a new offer.**

1. Create a class that implements `Checkout\Offer\Offer` and returns a `Discount` for each saving it finds in the priced lines.
2. Add an instance of it to the `$offers` list in `CheckoutFactory::default()` (order there is the order on the receipt).
3. Add a test for it. `Checkout` itself does not change. `tests/ExtensibilityTest.php` shows this with a "mug + tote bundle" offer.

## Assumptions

- Item offers stack with each other and with the discount code.
- 3-for-2 applies per full group of three (5 notebooks → one free, 6 → two free).
- The bulk price reprices every pen once you buy 5 or more (not just the 5th onwards).
- The bulk saving is shown as its own discount line ("bulk pens"), and the pen line shows full price.
- `SAVE10` applies to the total after item offers, not to the subtotal.
- Codes are case-sensitive (`save10` is unknown).
- An unknown SKU or unknown code throws an exception rather than being silently ignored.
- Applying a second code to a basket throws.
- Quantities are positive integers.
- One currency, two decimal places.

## What I would do next with more time

- Currency-aware `Money` (currency code on each amount; refuse to mix currencies).
- Explicit stacking/priority rules between offers (e.g. "best offer wins" or exclusive offers).
- Config-driven catalog and offers (JSON/YAML) instead of wiring them in PHP.
- Conditional codes: minimum spend, expiry dates, product restrictions.
- Property-based tests (e.g. total is never negative, discounts never exceed subtotal).
- A small CLI that takes a basket as arguments and prints the receipt.

## AI tools used

TODO – written by me
