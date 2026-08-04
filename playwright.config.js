// @ts-check
const path = require('node:path');
const { defineConfig, devices } = require('@playwright/test');

const port = process.env.E2E_PORT || '4173';
const baseURL = process.env.E2E_BASE_URL || `http://127.0.0.1:${port}`;
const serverless = process.env.E2E_SERVERLESS === '1';
const suite = serverless ? 'tracking' : 'app';

module.exports = defineConfig({
    testDir: './tests/e2e',
    fullyParallel: false,
    forbidOnly: Boolean(process.env.CI),
    retries: 0,
    workers: 1,
    timeout: 30_000,
    expect: {
        timeout: 5_000,
    },
    outputDir: path.join('test-results', suite),
    reporter: [
        ['list'],
        ['html', { outputFolder: path.join('playwright-report', suite), open: 'never' }],
    ],
    use: {
        baseURL,
        actionTimeout: 10_000,
        navigationTimeout: 15_000,
        trace: 'retain-on-failure',
        screenshot: 'only-on-failure',
        video: 'retain-on-failure',
    },
    webServer: serverless ? undefined : {
        command: 'node scripts/e2e/run-browser.cjs serve',
        url: `${baseURL}/id`,
        timeout: 120_000,
        reuseExistingServer: false,
        env: {
            ...process.env,
            E2E_PORT: port,
            E2E_BASE_URL: baseURL,
        },
    },
    projects: [
        {
            name: 'chromium',
            use: { ...devices['Desktop Chrome'] },
        },
    ],
});
