<?php

declare(strict_types=1);

namespace Checkout;

/** Renders a Receipt as fixed-width plain text: label on the left, amount on the right. */
final class ReceiptFormatter
{
    private const WIDTH = 32;

    public function format(Receipt $receipt): string
    {
        $rows = [];
        foreach ($receipt->lines as $line) {
            $rows[] = $this->row(sprintf('%d x %s', $line->quantity, $line->product->name), $line->lineTotal);
        }

        $rows[] = $this->divider();
        $rows[] = $this->row('Subtotal', $receipt->subtotal);
        foreach ($receipt->discounts as $discount) {
            $rows[] = $this->row($discount->label, $discount->amount->negate());
        }

        $rows[] = $this->divider();
        $rows[] = $this->row('Total', $receipt->total);

        return implode("\n", $rows) . "\n";
    }

    private function row(string $label, Money $amount): string
    {
        $value = $amount->format();
        // Keep at least one space between label and amount if the label is long.
        $padding = max(1, self::WIDTH - strlen($label) - strlen($value));

        return $label . str_repeat(' ', $padding) . $value;
    }

    private function divider(): string
    {
        return str_repeat('-', self::WIDTH);
    }
}
