import { defineConfig } from '@playwright/test';

export default defineConfig({
    testDir: './tests/e2e',
    timeout: 30_000,
    retries: process.env.CI ? 1 : 0,
    reporter: process.env.CI ? [['github'], ['html', { open: 'never' }]] : [['list'], ['html']],
    use: {
        baseURL: process.env.E2E_BASE_URL || 'http://127.0.0.1:8000',
        trace: 'on-first-retry',
        screenshot: 'only-on-failure',
        video: 'retain-on-failure',
    },
    webServer: process.env.CI
        ? undefined
        : {
            command: 'php -S 127.0.0.1:8000 -t public',
            url: 'http://127.0.0.1:8000',
            reuseExistingServer: !process.env.CI,
            timeout: 60_000,
        },
});
