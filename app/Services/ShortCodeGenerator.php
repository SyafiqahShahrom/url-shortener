<?php

namespace App\Services;

use InvalidArgumentException;

class ShortCodeGenerator
{
    public const MIN_LENGTH = 6;

    public const MAX_LENGTH = 8;

    /** Regex fragment used to constrain the redirect route. */
    public const PATTERN = '[A-Za-z0-9]{6,8}';

    private const ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';

    /**
     * 7 characters gives 62^7 (~3.5 trillion) combinations, so collisions are
     * rare in practice while links stay short.
     */
    public function __construct(private readonly int $length = 7)
    {
        if ($length < self::MIN_LENGTH || $length > self::MAX_LENGTH) {
            throw new InvalidArgumentException(sprintf(
                'Short code length must be between %d and %d, %d given.',
                self::MIN_LENGTH,
                self::MAX_LENGTH,
                $length,
            ));
        }
    }

    /**
     * Generate an unpredictable alphanumeric code using a CSPRNG.
     */
    public function generate(): string
    {
        $maxIndex = strlen(self::ALPHABET) - 1;
        $code = '';

        for ($i = 0; $i < $this->length; $i++) {
            $code .= self::ALPHABET[random_int(0, $maxIndex)];
        }

        return $code;
    }
}
