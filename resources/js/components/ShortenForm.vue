<script setup>
import { ref } from 'vue';

defineProps({
    error: { type: String, default: '' },
    isSubmitting: { type: Boolean, default: false },
});

const emit = defineEmits(['submit', 'input']);

const url = defineModel({ type: String, default: '' });
const input = ref(null);

defineExpose({
    focus: () => input.value?.focus(),
});
</script>

<template>
    <!-- novalidate: we show our own accessible messages instead of browser tooltips. -->
    <form novalidate @submit.prevent="emit('submit', url)">
        <label for="url" class="block text-sm font-medium text-slate-700">Long URL</label>

        <div class="mt-2 flex flex-col gap-3 sm:flex-row">
            <input
                id="url"
                ref="input"
                v-model="url"
                type="url"
                name="url"
                inputmode="url"
                autocomplete="url"
                autocapitalize="off"
                spellcheck="false"
                placeholder="https://example.com/a/very/long/link"
                :aria-invalid="error ? 'true' : 'false'"
                :aria-describedby="error ? 'url-error' : 'url-hint'"
                class="min-w-0 flex-1 rounded-lg border bg-white px-4 py-3 text-base text-slate-900 shadow-sm placeholder:text-slate-400 focus:ring-2 focus:outline-none"
                :class="
                    error
                        ? 'border-red-600 focus:border-red-600 focus:ring-red-200'
                        : 'border-slate-300 focus:border-indigo-600 focus:ring-indigo-200'
                "
                @input="emit('input')"
            />

            <button
                type="submit"
                :disabled="isSubmitting"
                class="inline-flex items-center justify-center gap-2 rounded-lg bg-indigo-600 px-6 py-3 font-medium text-white shadow-sm transition-colors hover:bg-indigo-700 focus-visible:ring-2 focus-visible:ring-indigo-600 focus-visible:ring-offset-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-70"
            >
                <svg
                    v-if="isSubmitting"
                    class="size-4 animate-spin"
                    viewBox="0 0 24 24"
                    fill="none"
                    aria-hidden="true"
                >
                    <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" class="opacity-25" />
                    <path d="M22 12a10 10 0 0 0-10-10" stroke="currentColor" stroke-width="4" stroke-linecap="round" />
                </svg>
                {{ isSubmitting ? 'Shortening…' : 'Shorten' }}
            </button>
        </div>

        <p v-if="error" id="url-error" role="alert" class="mt-2 flex items-start gap-1.5 text-sm text-red-700">
            <svg class="mt-0.5 size-4 shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path
                    fill-rule="evenodd"
                    d="M18 10a8 8 0 1 1-16 0 8 8 0 0 1 16 0Zm-8-5a.75.75 0 0 1 .75.75v4.5a.75.75 0 0 1-1.5 0v-4.5A.75.75 0 0 1 10 5Zm0 10a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z"
                    clip-rule="evenodd"
                />
            </svg>
            <span><span class="sr-only">Error: </span>{{ error }}</span>
        </p>
        <p v-else id="url-hint" class="mt-2 text-sm text-slate-500">Paste a link starting with http:// or https://.</p>
    </form>
</template>
