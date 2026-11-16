<?php

declare(strict_types=1);

namespace Checkout\Exception;

use LogicException;

final class CodeAlreadyApplied extends LogicException
{
    public static function because(string $existing, string $attempted): self
    {
        return new self(sprintf(
            'Cannot apply "%s": code "%s" is already applied and a basket allows only one.',
            $attempted,
            $existing,
        ));
    }
}
