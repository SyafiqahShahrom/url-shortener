import { onScopeDispose, ref } from 'vue';

/**
 * Copy text to the clipboard and expose the outcome as
 * 'idle' | 'copied' | 'failed', resetting to 'idle' after a short delay.
 */
export function useClipboard({ resetAfterMs = 2000 } = {}) {
    const status = ref('idle');
    let timer = null;

    async function copy(text) {
        clearTimeout(timer);

        try {
            // Unavailable on insecure (non-HTTPS, non-localhost) origins.
            if (!navigator.clipboard?.writeText) {
                throw new Error('Clipboard API unavailable');
            }

            await navigator.clipboard.writeText(text);
            status.value = 'copied';
        } catch {
            status.value = 'failed';
        }

        timer = setTimeout(() => {
            status.value = 'idle';
        }, resetAfterMs);
    }

    onScopeDispose(() => clearTimeout(timer));

    return { status, copy };
}
