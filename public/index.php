<?php

declare(strict_types=1);

/*
 * A small web page on top of the library. It reads the form (GET), builds a
 * Basket with BasketForm, prices it with the same Checkout the tests use, and
 * renders the Receipt. There is no pricing logic in this file.
 *
 * Run it with: composer serve   (then open http://localhost:8000)
 */

use Checkout\CheckoutFactory;
use Checkout\Receipt;
use Checkout\ReceiptFormatter;
use Checkout\Web\BasketForm;

require __DIR__ . '/../vendor/autoload.php';

$catalog = CheckoutFactory::catalog();

// Shown next to each product. The real offer rules live in CheckoutFactory.
$offerHints = [
    'NB' => '3 for the price of 2',
    'PEN' => '4.50 each when buying 5 or more',
];

$receipt = null;
$error = null;
$submitted = isset($_GET['qty']) || isset($_GET['code']);

if ($submitted) {
    try {
        $receipt = CheckoutFactory::default()->price(BasketForm::toBasket($_GET, $catalog));
    } catch (LogicException $exception) {
        // Covers bad quantities, unknown codes and SKUs (all InvalidArgumentException,
        // which extends LogicException) and discounts bigger than the basket.
        $error = $exception->getMessage();
    }
}

$h = static fn (string $text): string => htmlspecialchars($text, ENT_QUOTES, 'UTF-8');

/** The value the user typed last time, so the form keeps its state. */
$old = static function (string ...$path): string {
    $value = $_GET;
    foreach ($path as $key) {
        $value = is_array($value) ? ($value[$key] ?? '') : '';
    }

    return is_string($value) ? $value : '';
};

$plainText = $receipt instanceof Receipt ? (new ReceiptFormatter())->format($receipt) : '';
$exampleUrl = '?' . http_build_query(['qty' => ['NB' => 3, 'PEN' => 5, 'MUG' => 1], 'code' => 'SAVE10']);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Checkout</title>
    <style>
        :root {
            --bg: #f5f6f8;
            --card: #ffffff;
            --text: #1d2330;
            --muted: #6b7280;
            --line: #e5e7eb;
            --accent: #2457d6;
            --saving: #15803d;
            --error-bg: #fef2f2;
            --error-text: #b91c1c;
        }
        @media (prefers-color-scheme: dark) {
            :root {
                --bg: #14161b;
                --card: #1d2027;
                --text: #e6e8ec;
                --muted: #9aa1ad;
                --line: #2d313a;
                --accent: #7aa2f7;
                --saving: #7ed384;
                --error-bg: #3a1d1d;
                --error-text: #fca5a5;
            }
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            background: var(--bg);
            color: var(--text);
            font: 16px/1.5 system-ui, -apple-system, "Segoe UI", sans-serif;
        }
        main { max-width: 960px; margin: 0 auto; padding: 32px 16px; }
        h1 { margin: 0 0 4px; font-size: 1.6rem; }
        h2 { margin: 0 0 16px; font-size: 1.1rem; }
        .intro { margin: 0 0 24px; color: var(--muted); }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; align-items: start; }
        @media (max-width: 720px) { .grid { grid-template-columns: 1fr; } }
        .card { background: var(--card); border: 1px solid var(--line); border-radius: 12px; padding: 24px; }
        .product { display: flex; align-items: center; gap: 12px; padding: 12px 0; border-bottom: 1px solid var(--line); }
        .product:last-of-type { border-bottom: 0; }
        .product label { flex: 1; }
        .product .name { font-weight: 600; display: block; }
        .product .meta { color: var(--muted); font-size: 0.875rem; }
        .product .offer { color: var(--saving); }
        input {
            font: inherit;
            color: var(--text);
            background: var(--bg);
            border: 1px solid var(--line);
            border-radius: 8px;
            padding: 8px 10px;
        }
        input[type=number] { width: 80px; text-align: right; }
        .code { margin-top: 16px; }
        .code label { display: block; font-weight: 600; margin-bottom: 4px; }
        .code input { width: 100%; }
        .hint { color: var(--muted); font-size: 0.875rem; margin-top: 4px; }
        .actions { display: flex; gap: 12px; align-items: center; margin-top: 20px; flex-wrap: wrap; }
        button {
            font: inherit;
            font-weight: 600;
            color: #fff;
            background: var(--accent);
            border: 0;
            border-radius: 8px;
            padding: 10px 18px;
            cursor: pointer;
        }
        a { color: var(--accent); }
        table { width: 100%; border-collapse: collapse; }
        td { padding: 6px 0; }
        td.amount { text-align: right; font-variant-numeric: tabular-nums; white-space: nowrap; }
        tr.rule td { border-top: 1px solid var(--line); padding-top: 10px; }
        tr.saving td { color: var(--saving); }
        tr.total td { font-weight: 700; font-size: 1.2rem; border-top: 2px solid var(--text); padding-top: 10px; }
        .empty { color: var(--muted); margin: 0; }
        .error { background: var(--error-bg); color: var(--error-text); border-radius: 8px; padding: 12px 14px; margin: 0; }
        details { margin-top: 20px; }
        summary { cursor: pointer; color: var(--muted); }
        pre { background: var(--bg); border: 1px solid var(--line); border-radius: 8px; padding: 12px; overflow-x: auto; font-size: 0.875rem; }
    </style>
</head>
<body>
<main>
    <h1>Checkout</h1>
    <p class="intro">Pick some items, add a code if you have one, and price the basket.</p>

    <div class="grid">
        <form class="card" method="get">
            <h2>Basket</h2>
            <?php foreach ($catalog->all() as $product) : ?>
                <div class="product">
                    <label for="qty-<?= $h($product->sku) ?>">
                        <span class="name"><?= $h($product->name) ?></span>
                        <span class="meta"><?= $h($product->unitPrice->format()) ?> each</span>
                        <?php if (isset($offerHints[$product->sku])) : ?>
                            <span class="meta offer">· <?= $h($offerHints[$product->sku]) ?></span>
                        <?php endif ?>
                    </label>
                    <input type="number" min="0" max="999" step="1" placeholder="0"
                           id="qty-<?= $h($product->sku) ?>"
                           name="qty[<?= $h($product->sku) ?>]"
                           value="<?= $h($old('qty', $product->sku)) ?>">
                </div>
            <?php endforeach ?>

            <div class="code">
                <label for="code">Discount code</label>
                <input type="text" id="code" name="code" autocomplete="off" value="<?= $h($old('code')) ?>">
                <div class="hint">Try SAVE10 for 10% off after item offers. Codes are case-sensitive.</div>
            </div>

            <div class="actions">
                <button type="submit">Price basket</button>
                <a href="<?= $h($exampleUrl) ?>">Load the example from the brief</a>
                <a href="?">Clear</a>
            </div>
        </form>

        <section class="card" aria-live="polite">
            <h2>Receipt</h2>
            <?php if ($error !== null) : ?>
                <p class="error"><?= $h($error) ?></p>
            <?php elseif ($receipt === null) : ?>
                <p class="empty">Your receipt will show up here once you price a basket.</p>
            <?php else : ?>
                <table>
                    <?php foreach ($receipt->lines as $line) : ?>
                        <tr>
                            <td><?= $line->quantity ?> × <?= $h($line->product->name) ?></td>
                            <td class="amount"><?= $h($line->lineTotal->format()) ?></td>
                        </tr>
                    <?php endforeach ?>
                    <tr class="rule">
                        <td>Subtotal</td>
                        <td class="amount"><?= $h($receipt->subtotal->format()) ?></td>
                    </tr>
                    <?php foreach ($receipt->discounts as $discount) : ?>
                        <tr class="saving">
                            <td><?= $h($discount->label) ?></td>
                            <td class="amount"><?= $h($discount->amount->negate()->format()) ?></td>
                        </tr>
                    <?php endforeach ?>
                    <tr class="total">
                        <td>Total</td>
                        <td class="amount"><?= $h($receipt->total->format()) ?></td>
                    </tr>
                </table>

                <details>
                    <summary>Plain-text receipt (same as <code>composer demo</code>)</summary>
                    <pre><?= $h($plainText) ?></pre>
                </details>
            <?php endif ?>
        </section>
    </div>
</main>
</body>
</html>
