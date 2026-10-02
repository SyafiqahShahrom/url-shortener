<script setup>
import HomeView from './views/HomeView.vue';
</script>

<template>
    <div class="flex min-h-screen flex-col">
        <main class="flex flex-1 items-start justify-center px-4 py-12 sm:py-24">
            <HomeView />
        </main>

        <footer class="px-4 pb-6 text-center text-sm text-slate-500">
            Built with Laravel and Vue 3.
        </footer>
    </div>
</template>
