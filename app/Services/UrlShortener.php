<?php

namespace App\Services;

use App\Exceptions\ShortCodeGenerationException;
use App\Models\ShortUrl;
use Illuminate\Database\UniqueConstraintViolationException;

class UrlShortener
{
    /**
     * Each retry is astronomically unlikely to be needed; the cap only exists
     * so a misconfiguration (e.g. a nearly full keyspace) fails loudly
     * instead of looping forever.
     */
    public const MAX_ATTEMPTS = 5;

    public function __construct(private readonly ShortCodeGenerator $generator) {}

    /**
     * Persist the URL under a new, unique short code.
     *
     * Rather than checking for an existing code first (an extra query that
     * still races with concurrent requests), we insert directly and let the
     * unique index reject a collision, then retry with a fresh code.
     *
     * @throws ShortCodeGenerationException
     */
    public function shorten(string $originalUrl): ShortUrl
    {
        for ($attempt = 1; $attempt <= self::MAX_ATTEMPTS; $attempt++) {
            try {
                return ShortUrl::create([
                    'original_url' => $originalUrl,
                    'short_code' => $this->generator->generate(),
                ]);
            } catch (UniqueConstraintViolationException) {
                // Collision on short_code: try again with a new code.
            }
        }

        throw ShortCodeGenerationException::afterAttempts(self::MAX_ATTEMPTS);
    }
}
