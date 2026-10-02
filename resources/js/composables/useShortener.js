import { ref } from 'vue';
import { ApiError, createShortUrl, ERROR_MESSAGES } from '../services/shortUrlService';
import { validateUrl } from '../utils/url';

/**
 * State and actions for the shorten-a-URL flow.
 *
 * Errors are split in two: `fieldError` belongs next to the input (bad URL),
 * `formError` is about the request itself (network, rate limit, server).
 */
export function useShortener() {
    const result = ref(null);
    const fieldError = ref('');
    const formError = ref('');
    const isSubmitting = ref(false);

    function clearErrors() {
        fieldError.value = '';
        formError.value = '';
    }

    async function shorten(rawUrl) {
        if (isSubmitting.value) {
            return;
        }

        const url = rawUrl.trim();

        clearErrors();
        result.value = null;

        const clientError = validateUrl(url);

        if (clientError) {
            fieldError.value = clientError;
            return;
        }

        isSubmitting.value = true;

        try {
            result.value = await createShortUrl(url);
        } catch (error) {
            if (!(error instanceof ApiError)) {
                console.error(error);
                formError.value = ERROR_MESSAGES.server;
            } else if (error.fieldErrors.url?.length) {
                fieldError.value = error.fieldErrors.url[0];
            } else {
                formError.value = error.message;
            }
        } finally {
            isSubmitting.value = false;
        }
    }

    function reset() {
        clearErrors();
        result.value = null;
    }

    return { result, fieldError, formError, isSubmitting, shorten, reset, clearErrors };
}
