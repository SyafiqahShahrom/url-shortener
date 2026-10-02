// Mirrors StoreShortUrlRequest so most mistakes are caught without a round
// trip. The server remains the source of truth.
export const MAX_URL_LENGTH = 2048;

export const URL_MESSAGES = {
    required: 'Please enter a URL to shorten.',
    invalid: 'Please enter a valid URL starting with http:// or https://.',
    tooLong: `The URL may not be longer than ${MAX_URL_LENGTH} characters.`,
};

/**
 * Return a user-facing error message, or null when the URL looks valid.
 */
export function validateUrl(value) {
    const url = value.trim();

    if (url === '') {
        return URL_MESSAGES.required;
    }

    if (url.length > MAX_URL_LENGTH) {
        return URL_MESSAGES.tooLong;
    }

    let parsed;

    try {
        parsed = new URL(url);
    } catch {
        return URL_MESSAGES.invalid;
    }

    if (!['http:', 'https:'].includes(parsed.protocol) || parsed.hostname === '') {
        return URL_MESSAGES.invalid;
    }

    return null;
}
