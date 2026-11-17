import { defineConfig, devices } from '@playwright/test';

export default defineConfig({
    testDir: './e2e',
    fullyParallel: false,
    workers: 1,
    timeout: 60_000,
    expect: {
        timeout: 10_000,
    },
    reporter: [['list']],
    use: {
        baseURL: process.env.ORCA_URL ?? 'https://orca.ddev.site:30001',
        ignoreHTTPSErrors: true,
        locale: 'pt-BR',
        timezoneId: 'America/Sao_Paulo',
        screenshot: 'only-on-failure',
        trace: 'retain-on-failure',
    },
    projects: [
        {
            name: 'chromium',
            use: { ...devices['Desktop Chrome'] },
        },
    ],
});
