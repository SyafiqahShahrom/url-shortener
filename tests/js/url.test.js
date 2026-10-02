import { describe, expect, it } from 'vitest';
import { MAX_URL_LENGTH, URL_MESSAGES, validateUrl } from '../../resources/js/utils/url';

describe('validateUrl', () => {
    it.each(['https://example.com', 'http://example.com/path?q=1#top', '  https://example.com  '])(
        'accepts %s',
        (url) => {
            expect(validateUrl(url)).toBeNull();
        },
    );

    it.each(['', '   '])('requires a value (%j)', (url) => {
        expect(validateUrl(url)).toBe(URL_MESSAGES.required);
    });

    it.each(['example.com', 'not a url', 'javascript:alert(1)', 'ftp://example.com', 'mailto:a@b.com'])(
        'rejects %s',
        (url) => {
            expect(validateUrl(url)).toBe(URL_MESSAGES.invalid);
        },
    );

    it('rejects URLs over the maximum length', () => {
        const url = 'https://example.com/' + 'a'.repeat(MAX_URL_LENGTH);

        expect(validateUrl(url)).toBe(URL_MESSAGES.tooLong);
    });
});
