import { flushPromises, mount } from '@vue/test-utils';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import HomeView from '../../resources/js/views/HomeView.vue';
import { ApiError, createShortUrl, ERROR_MESSAGES } from '../../resources/js/services/shortUrlService';

vi.mock('../../resources/js/services/shortUrlService', async (importOriginal) => ({
    ...(await importOriginal()),
    createShortUrl: vi.fn(),
}));

const created = {
    short_code: 'Ab12X9z',
    short_url: 'http://localhost/Ab12X9z',
    original_url: 'https://www.google.com/search?q=laravel',
    created_at: '2026-10-02T13:00:00.000000Z',
};

function deferred() {
    let resolve;
    const promise = new Promise((res) => {
        resolve = res;
    });

    return { promise, resolve };
}

async function submit(wrapper, url) {
    await wrapper.find('input#url').setValue(url);
    await wrapper.find('form').trigger('submit');
}

describe('HomeView', () => {
    let wrapper;

    beforeEach(() => {
        wrapper = mount(HomeView, { attachTo: document.body });
    });

    afterEach(() => {
        wrapper.unmount();
        vi.unstubAllGlobals();
    });

    it('shortens a URL and shows the result', async () => {
        createShortUrl.mockResolvedValue(created);

        await submit(wrapper, '  https://www.google.com/search?q=laravel ');
        await flushPromises();

        expect(createShortUrl).toHaveBeenCalledWith('https://www.google.com/search?q=laravel');
        expect(wrapper.find('[data-test="short-url"]').text()).toBe(created.short_url);
        expect(wrapper.find('[data-test="short-url"]').attributes('href')).toBe(created.short_url);
        expect(wrapper.find('[data-test="original-url"]').text()).toBe(created.original_url);
        expect(document.activeElement?.id).toBe('result-heading');
    });

    it('shows a loading state and ignores repeat submissions while pending', async () => {
        const request = deferred();
        createShortUrl.mockReturnValue(request.promise);

        await submit(wrapper, 'https://example.com');

        const button = wrapper.find('button[type="submit"]');
        expect(button.attributes('disabled')).toBeDefined();
        expect(button.text()).toBe('Shortening…');

        await wrapper.find('form').trigger('submit');
        expect(createShortUrl).toHaveBeenCalledTimes(1);

        request.resolve(created);
        await flushPromises();

        expect(button.attributes('disabled')).toBeUndefined();
        expect(button.text()).toBe('Shorten');
    });

    it('validates empty input without calling the API', async () => {
        await submit(wrapper, '   ');

        expect(createShortUrl).not.toHaveBeenCalled();
        expect(wrapper.find('#url-error').text()).toContain('Please enter a URL to shorten.');
        expect(wrapper.find('input#url').attributes('aria-invalid')).toBe('true');
        expect(wrapper.find('input#url').attributes('aria-describedby')).toBe('url-error');
    });

    it('validates malformed URLs without calling the API', async () => {
        await submit(wrapper, 'javascript:alert(1)');

        expect(createShortUrl).not.toHaveBeenCalled();
        expect(wrapper.find('#url-error').text()).toContain('starting with http:// or https://');
    });

    it('shows server-side validation errors next to the input', async () => {
        createShortUrl.mockRejectedValue(
            new ApiError('This URL is already a short link.', {
                status: 422,
                fieldErrors: { url: ['This URL is already a short link.'] },
            }),
        );

        await submit(wrapper, 'https://example.com');
        await flushPromises();

        expect(wrapper.find('#url-error').text()).toContain('This URL is already a short link.');
        expect(wrapper.find('[data-test="short-url"]').exists()).toBe(false);
    });

    it('shows request-level failures as an alert', async () => {
        createShortUrl.mockRejectedValue(new ApiError(ERROR_MESSAGES.network));

        await submit(wrapper, 'https://example.com');
        await flushPromises();

        const alert = wrapper.findAll('[role="alert"]').find((el) => el.text().includes(ERROR_MESSAGES.network));
        expect(alert).toBeDefined();
        expect(wrapper.find('button[type="submit"]').attributes('disabled')).toBeUndefined();
    });

    it('clears the error when the user edits the URL', async () => {
        await submit(wrapper, 'nope');
        expect(wrapper.find('#url-error').exists()).toBe(true);

        await wrapper.find('input#url').setValue('https://');

        expect(wrapper.find('#url-error').exists()).toBe(false);
    });

    it('copies the short URL to the clipboard', async () => {
        const writeText = vi.fn().mockResolvedValue();
        vi.stubGlobal('navigator', { clipboard: { writeText } });
        createShortUrl.mockResolvedValue(created);

        await submit(wrapper, 'https://example.com');
        await flushPromises();

        const copyButton = wrapper.findAll('button').find((b) => b.text() === 'Copy');
        await copyButton.trigger('click');
        await flushPromises();

        expect(writeText).toHaveBeenCalledWith(created.short_url);
        expect(copyButton.text()).toBe('Copied!');
    });

    it('tells the user when copying fails', async () => {
        vi.stubGlobal('navigator', { clipboard: { writeText: vi.fn().mockRejectedValue(new Error('denied')) } });
        createShortUrl.mockResolvedValue(created);

        await submit(wrapper, 'https://example.com');
        await flushPromises();

        await wrapper.findAll('button').find((b) => b.text() === 'Copy').trigger('click');
        await flushPromises();

        expect(wrapper.text()).toContain("Couldn't copy automatically");
    });

    it('resets the form for another link', async () => {
        createShortUrl.mockResolvedValue(created);

        await submit(wrapper, 'https://example.com');
        await flushPromises();

        await wrapper.findAll('button').find((b) => b.text() === 'Shorten another link').trigger('click');
        await flushPromises();

        expect(wrapper.find('[data-test="short-url"]').exists()).toBe(false);
        expect(wrapper.find('input#url').element.value).toBe('');
        expect(document.activeElement?.id).toBe('url');
    });
});
