<?php

namespace App\Exceptions;

use RuntimeException;

class ShortCodeGenerationException extends RuntimeException
{
    public static function afterAttempts(int $attempts): self
    {
        return new self("Could not generate a unique short code after {$attempts} attempts.");
    }
}
