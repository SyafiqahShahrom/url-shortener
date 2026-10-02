const ENDPOINT = '/api/urls';

export const ERROR_MESSAGES = {
    network: "We couldn't reach the server. Check your connection and try again.",
    rateLimited: "You're shortening links too quickly. Please wait a minute and try again.",
    server: 'Something went wrong on our side. Please try again in a moment.',
    validation: 'Please check the URL and try again.',
};

/**
 * An API failure normalised into something the UI can show directly.
 * `fieldErrors` holds Laravel's per-field validation messages (422 only).
 */
export class ApiError extends Error {
    constructor(message, { status = null, fieldErrors = {} } = {}) {
        super(message);
        this.name = 'ApiError';
        this.status = status;
        this.fieldErrors = fieldErrors;
    }
}

/**
 * Ask the API to shorten a URL.
 *
 * @param {string} url
 * @returns {Promise<{short_code: string, short_url: string, original_url: string, created_at: string}>}
 * @throws {ApiError}
 */
export async function createShortUrl(url) {
    let response;

    try {
        response = await fetch(ENDPOINT, {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ url }),
        });
    } catch {
        throw new ApiError(ERROR_MESSAGES.network);
    }

    // Error pages from proxies or a crashed server may not be JSON.
    const body = await response.json().catch(() => null);

    if (response.ok && body?.data) {
        return body.data;
    }

    if (response.status === 422) {
        throw new ApiError(body?.message ?? ERROR_MESSAGES.validation, {
            status: 422,
            fieldErrors: body?.errors ?? {},
        });
    }

    if (response.status === 429) {
        throw new ApiError(ERROR_MESSAGES.rateLimited, { status: 429 });
    }

    throw new ApiError(ERROR_MESSAGES.server, { status: response.status });
}
