<script setup>
import { computed, ref } from 'vue';
import { useClipboard } from '../composables/useClipboard';

const props = defineProps({
    shortUrl: { type: Object, required: true },
});

const emit = defineEmits(['reset']);

const { status: copyStatus, copy } = useClipboard();
const heading = ref(null);

const copyLabel = computed(() => (copyStatus.value === 'copied' ? 'Copied!' : 'Copy'));

defineExpose({
    focus: () => heading.value?.focus(),
});
</script>

<template>
    <section aria-labelledby="result-heading" class="rounded-xl border border-emerald-200 bg-emerald-50 p-5">
        <h2 id="result-heading" ref="heading" tabindex="-1" class="text-sm font-semibold text-emerald-900 focus:outline-none">
            Your short link is ready
        </h2>

        <div class="mt-3 flex flex-col gap-3 sm:flex-row sm:items-center">
            <a
                :href="props.shortUrl.short_url"
                target="_blank"
                rel="noopener noreferrer"
                class="min-w-0 flex-1 truncate rounded-lg bg-white px-4 py-3 font-medium text-indigo-700 underline-offset-4 ring-1 ring-emerald-200 hover:underline focus-visible:ring-2 focus-visible:ring-indigo-600 focus-visible:outline-none"
                data-test="short-url"
            >
                {{ props.shortUrl.short_url }}
            </a>

            <button
                type="button"
                class="rounded-lg bg-slate-900 px-5 py-3 font-medium text-white transition-colors hover:bg-slate-700 focus-visible:ring-2 focus-visible:ring-slate-900 focus-visible:ring-offset-2 focus-visible:outline-none"
                @click="copy(props.shortUrl.short_url)"
            >
                {{ copyLabel }}
            </button>
        </div>

        <p class="sr-only" aria-live="polite">
            {{ copyStatus === 'copied' ? 'Short link copied to clipboard.' : '' }}
        </p>
        <p v-if="copyStatus === 'failed'" role="alert" class="mt-2 text-sm text-red-700">
            Couldn't copy automatically. Select the link above and copy it manually.
        </p>

        <p class="mt-4 text-sm text-slate-600">
            <span class="font-medium text-slate-700">Original:</span>{{ " " }}
            <span class="break-all" data-test="original-url">{{ props.shortUrl.original_url }}</span>
        </p>

        <button
            type="button"
            class="mt-4 text-sm font-medium text-indigo-700 underline-offset-4 hover:underline focus-visible:rounded focus-visible:ring-2 focus-visible:ring-indigo-600 focus-visible:outline-none"
            @click="emit('reset')"
        >
            Shorten another link
        </button>
    </section>
</template>
