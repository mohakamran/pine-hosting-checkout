# Checkout pricing (Pine Hosting, task 2)

This is my solution to the checkout task. It prices a basket of items, applies the item offers and an optional discount code, and prints a plain-text receipt.

It's a small PHP library with no framework. The only dependencies are dev tools: PHPUnit for tests, PHPStan for static analysis and PHP CS Fixer for code style.

![Demo receipt](docs/screenshots/demo.png)

## How to run the code and its tests

You need PHP 8.2 or newer and Composer.

```bash
composer install
```

Then:

| Command | What it does |
|---|---|
| `composer test` | Runs the PHPUnit test suite |
| `composer check` | Runs code style, PHPStan (level max) and the tests in one go |
| `composer demo` | Prints the receipt for the example basket from the brief |
| `composer analyse` | PHPStan only |
| `composer cs` / `composer cs:fix` | Check / fix code style (PSR-12) |

The demo basket is 3 notebooks, 5 pen packs and 1 mug with the code `SAVE10`. To try a different basket, edit `bin/demo.php` and run `composer demo` again.

All 53 tests pass:

<details>
<summary>Test output (<code>composer test -- --testdox</code>)</summary>

![Test results](docs/screenshots/tests.png)

</details>

## Design overview

The main idea is that `Checkout` doesn't know which offers exist. It's given a list of `Offer` objects and asks each one for its discounts. Adding a new kind of offer means writing a new class, not adding another `if` to the checkout.

| File | Responsibility |
|---|---|
| `src/Money.php` | Exact money amount stored as integer cents. Parsing, add/subtract/multiply, percentage with half-up rounding, formatting |
| `src/Product.php` | SKU, name and unit price |
| `src/Catalog.php` | Finds a product by SKU. Rejects duplicate SKUs, throws on unknown ones |
| `src/Basket.php` | What the customer is buying. Repeated SKUs are merged, and only one code is allowed |
| `src/PricedLine.php` | One basket line at full price (product, quantity, line total) |
| `src/Discount.php` | One discount line on the receipt (label and amount) |
| `src/Offer/Offer.php` | Interface for item offers: takes the priced lines, returns discounts |
| `src/Offer/MultiBuyOffer.php` | "Buy N, pay for M" per full group. Used for the notebook 3-for-2 |
| `src/Offer/BulkPriceOffer.php` | Lower unit price once you buy enough. Used for pens at 4.50 |
| `src/Code/DiscountCode.php` | Interface for discount codes |
| `src/Code/PercentOffCode.php` | Percentage off. Used for `SAVE10` |
| `src/Checkout.php` | Prices a basket: subtotal, then offers, then the code, then the total |
| `src/Receipt.php` | The result of pricing a basket |
| `src/ReceiptFormatter.php` | Turns a receipt into fixed-width text |
| `src/CheckoutFactory.php` | Sets up the catalog, offers and code from the brief |
| `src/Exception/` | `UnknownSku`, `UnknownDiscountCode`, `CodeAlreadyApplied` |
| `bin/demo.php` | The example from the brief |

### Why integer cents

Floats can't store most decimal amounts exactly. For example, `0.1 + 0.2` isn't exactly `0.3`, so a float total can end up a cent out. I store every amount as a whole number of cents in an `int`, which keeps adding, subtracting and multiplying exact.

The only division is for percentages. It's done with integer maths, `intdiv($cents * $percent + 50, 100)`. The `+ 50` is half of 100, so anything at or above half a cent rounds up and anything below rounds down.

### How to add a new offer

1. Write a class that implements `Checkout\Offer\Offer`. It gets the priced lines and returns a `Discount` for each saving.
2. Add it to the offers list in `CheckoutFactory::default()`. Offers are applied, and shown on the receipt, in that order.
3. Write a test for it.

`Checkout` doesn't need to change. `tests/ExtensibilityTest.php` does exactly this with a "mug + tote bundle" offer defined inside the test.

## Assumptions

Where the brief left room for interpretation, I went with:

- Item offers stack with each other and with the discount code.
- 3-for-2 counts full groups of three, so 5 notebooks get 1 free and 6 get 2 free.
- Once you buy 5 or more pens, every pen is 4.50, not just the ones after the fifth.
- The pen line shows the full price, and the bulk saving appears as its own "bulk pens" discount line.
- `SAVE10` is 10% of the total after item offers, not of the subtotal.
- Codes are case-sensitive, so `save10` is rejected.
- An unknown SKU or code throws an exception instead of being silently ignored.
- Applying a second code to the same basket throws.
- Quantities must be whole numbers of 1 or more.
- There's one currency, with two decimal places.

## What I would do next with more time

- Make `Money` currency-aware, so amounts in different currencies can't be mixed by accident.
- Add rules for how offers combine, for example "best offer wins" or offers that can't be combined.
- Load the catalog and offers from config instead of wiring them up in PHP.
- Support conditional codes, such as a minimum spend, an expiry date, or codes limited to certain products.
- Add property-based tests, for example "the total is never negative" over random baskets.
- Add a small CLI that takes a basket as arguments and prints the receipt.
