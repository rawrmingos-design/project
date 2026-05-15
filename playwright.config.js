const { defineConfig, devices } = require('@playwright/test');

const shouldUseManagedWebServer = process.env.E2E_MANAGED_SERVER === '1';
const serverHost = process.env.E2E_SERVER_HOST || '127.0.0.1';
const serverPort = process.env.E2E_SERVER_PORT || '8000';
const managedServerUrl = `http://${serverHost}:${serverPort}`;
const baseURL = process.env.E2E_BASE_URL
    || (shouldUseManagedWebServer ? managedServerUrl : 'http://istanatopup.test:8000');

module.exports = defineConfig({
    testDir: './tests/e2e',
    timeout: 30_000,
    expect: {
        timeout: 7_000,
    },
    fullyParallel: true,
    retries: process.env.CI ? 1 : 0,
    workers: process.env.CI ? 1 : undefined,
    reporter: [
        ['list'],
        ['html', { open: 'never' }],
    ],
    webServer: shouldUseManagedWebServer
        ? {
            command: `php artisan serve --host=${serverHost} --port=${serverPort}`,
            url: managedServerUrl,
            reuseExistingServer: true,
            timeout: 120_000,
        }
        : undefined,
    use: {
        baseURL,
        trace: 'on-first-retry',
        screenshot: 'only-on-failure',
        video: 'retain-on-failure',
    },
    projects: [
        {
            name: 'chromium',
            use: { ...devices['Desktop Chrome'] },
        },
    ],
});
