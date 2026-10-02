<script setup>
import { nextTick, ref } from 'vue';
import ShortenForm from '../components/ShortenForm.vue';
import ShortUrlResult from '../components/ShortUrlResult.vue';
import { useShortener } from '../composables/useShortener';

const { result, fieldError, formError, isSubmitting, shorten, reset, clearErrors } = useShortener();

const url = ref('');
const form = ref(null);
const resultPanel = ref(null);

async function handleSubmit(value) {
    await shorten(value);

    if (result.value) {
        // Move focus to the result so keyboard and screen reader users land on it.
        await nextTick();
        resultPanel.value?.focus();
    }
}

async function handleReset() {
    reset();
    url.value = '';
    await nextTick();
    form.value?.focus();
}
</script>

<template>
    <div class="w-full max-w-2xl">
        <header class="text-center">
            <h1 class="text-3xl font-semibold tracking-tight text-slate-900 sm:text-4xl">URL Shortener</h1>
            <p class="mt-3 text-base text-slate-600">
                Turn long, unwieldy links into short ones that are easy to share.
            </p>
        </header>

        <div class="mt-8 rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200 sm:p-8">
            <ShortenForm
                ref="form"
                v-model="url"
                :error="fieldError"
                :is-submitting="isSubmitting"
                @submit="handleSubmit"
                @input="clearErrors"
            />

            <div
                v-if="formError"
                role="alert"
                class="mt-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800"
            >
                <span class="font-semibold">Couldn't shorten that link.</span>
                {{ formError }}
            </div>

            <ShortUrlResult v-if="result" ref="resultPanel" :short-url="result" class="mt-6" @reset="handleReset" />
        </div>
    </div>
</template>
