<?php

declare(strict_types=1);

use Checkout\Basket;
use Checkout\CheckoutFactory;
use Checkout\ReceiptFormatter;

require __DIR__ . '/../vendor/autoload.php';

$basket = new Basket();
$basket->add('NB', 3);
$basket->add('PEN', 5);
$basket->add('MUG', 1);
$basket->applyCode('SAVE10');

echo (new ReceiptFormatter())->format(CheckoutFactory::default()->price($basket));
