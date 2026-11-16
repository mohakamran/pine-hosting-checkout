<?php

declare(strict_types=1);

namespace Checkout\Exception;

use InvalidArgumentException;

final class UnknownDiscountCode extends InvalidArgumentException
{
    public static function forCode(string $code): self
    {
        return new self(sprintf('Unknown discount code "%s".', $code));
    }
}
