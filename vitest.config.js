import { defineConfig } from 'vitest/config';
import vue from '@vitejs/plugin-vue';

// Kept separate from vite.config.js: the Laravel plugin is only needed for
// serving/building assets and refuses to start in CI environments.
export default defineConfig({
    plugins: [vue()],
    test: {
        environment: 'jsdom',
        include: ['tests/js/**/*.test.js'],
        mockReset: true,
    },
});
