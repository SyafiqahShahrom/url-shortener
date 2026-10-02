import { afterEach, describe, expect, it, vi } from 'vitest';
import { ApiError, createShortUrl, ERROR_MESSAGES } from '../../resources/js/services/shortUrlService';

function mockFetchResponse(status, body) {
    vi.stubGlobal(
        'fetch',
        vi.fn().mockResolvedValue({
            ok: status >= 200 && status < 300,
            status,
            json: body === undefined ? () => Promise.reject(new SyntaxError('Not JSON')) : () => Promise.resolve(body),
        }),
    );
}

async function captureError(promise) {
    try {
        await promise;
    } catch (error) {
        return error;
    }

    throw new Error('Expected the promise to reject');
}

describe('createShortUrl', () => {
    afterEach(() => vi.unstubAllGlobals());

    it('posts the URL as JSON and returns the created short URL', async () => {
        const data = { short_code: 'Ab12X9z', short_url: 'http://localhost/Ab12X9z', original_url: 'https://example.com' };
        mockFetchResponse(201, { data });

        await expect(createShortUrl('https://example.com')).resolves.toEqual(data);
        expect(fetch).toHaveBeenCalledWith('/api/urls', {
            method: 'POST',
            headers: { Accept: 'application/json', 'Content-Type': 'application/json' },
            body: JSON.stringify({ url: 'https://example.com' }),
        });
    });

    it('exposes Laravel validation errors per field', async () => {
        mockFetchResponse(422, { message: 'Invalid URL.', errors: { url: ['Invalid URL.'] } });

        const error = await captureError(createShortUrl('nope'));

        expect(error).toBeInstanceOf(ApiError);
        expect(error.status).toBe(422);
        expect(error.fieldErrors).toEqual({ url: ['Invalid URL.'] });
    });

    it('explains rate limiting', async () => {
        mockFetchResponse(429, { message: 'Too Many Attempts.' });

        const error = await captureError(createShortUrl('https://example.com'));

        expect(error.message).toBe(ERROR_MESSAGES.rateLimited);
    });

    it('hides server error details behind a generic message', async () => {
        mockFetchResponse(500, { message: 'SQLSTATE[HY000] secret details' });

        const error = await captureError(createShortUrl('https://example.com'));

        expect(error.status).toBe(500);
        expect(error.message).toBe(ERROR_MESSAGES.server);
    });

    it('handles non-JSON error responses', async () => {
        mockFetchResponse(502, undefined);

        const error = await captureError(createShortUrl('https://example.com'));

        expect(error.message).toBe(ERROR_MESSAGES.server);
    });

    it('reports network failures', async () => {
        vi.stubGlobal('fetch', vi.fn().mockRejectedValue(new TypeError('Failed to fetch')));

        const error = await captureError(createShortUrl('https://example.com'));

        expect(error).toBeInstanceOf(ApiError);
        expect(error.message).toBe(ERROR_MESSAGES.network);
    });
});
