const { spawnSync } = require('node:child_process');
const path = require('node:path');

const serverHost = process.env.E2E_SERVER_HOST || '127.0.0.1';
const serverPort = process.env.E2E_SERVER_PORT || '8001';
const localBaseUrl = `http://${serverHost}:${serverPort}`;

const env = {
    ...process.env,
    E2E_MANAGED_SERVER: '1',
    E2E_SERVER_HOST: serverHost,
    E2E_SERVER_PORT: serverPort,
    E2E_BASE_URL: process.env.E2E_BASE_URL || localBaseUrl,
    E2E_ADMIN_BASE_URL: process.env.E2E_ADMIN_BASE_URL || localBaseUrl,
    FILAMENT_ADMIN_DOMAIN: process.env.FILAMENT_ADMIN_DOMAIN || serverHost,
};

const playwrightCli = path.join(process.cwd(), 'node_modules', '@playwright', 'test', 'cli.js');

const playwrightArgs = [
    playwrightCli,
    'test',
    'tests/e2e/smoke.spec.js',
    'tests/e2e/admin-onboarding.spec.js',
    '--workers=1',
    ...process.argv.slice(2),
];

const run = spawnSync(process.execPath, playwrightArgs, { stdio: 'inherit', env });

if (run.error) {
    console.error(run.error);
}

if (typeof run.status === 'number') {
    process.exit(run.status);
}

process.exit(1);
