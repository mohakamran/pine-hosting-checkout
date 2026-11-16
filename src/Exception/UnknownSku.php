<?php

declare(strict_types=1);

namespace Checkout\Exception;

use InvalidArgumentException;

final class UnknownSku extends InvalidArgumentException
{
    public static function forSku(string $sku): self
    {
        return new self(sprintf('Unknown SKU "%s".', $sku));
    }
}
