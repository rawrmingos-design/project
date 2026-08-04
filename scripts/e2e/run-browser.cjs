const { spawn, spawnSync } = require('node:child_process');
const fs = require('node:fs');
const path = require('node:path');

const root = path.resolve(__dirname, '..', '..');
const runtimeDir = path.join(root, '.tmp', 'e2e');
const databasePath = path.join(runtimeDir, 'browser.sqlite');
const cacheDir = path.join(runtimeDir, 'cache');
const port = process.env.E2E_PORT || '4173';
const baseURL = `http://127.0.0.1:${port}`;
const mode = process.argv[2] || 'all';
const extraArgs = process.argv.slice(3);
const php = process.env.PHP_BINARY || 'php';
const viteCli = path.join(path.dirname(require.resolve('vite/package.json')), 'bin', 'vite.js');
const playwrightCli = require.resolve('@playwright/test/cli');

const e2eEnvironment = {
    ...process.env,
    APP_ENV: 'e2e',
    APP_DEBUG: 'false',
    APP_KEY: 'base64:cx0fphnsde2XPSe0r2v9U8lwpQbmg1fSIyEKGEhf4EY=',
    APP_URL: baseURL,
    DB_CONNECTION: 'sqlite',
    DB_DATABASE: databasePath,
    DB_FOREIGN_KEYS: 'true',
    CACHE_STORE: 'array',
    CACHE_DRIVER: 'array',
    SESSION_DRIVER: 'file',
    SESSION_SECURE_COOKIE: 'false',
    SESSION_DOMAIN: '',
    QUEUE_CONNECTION: 'sync',
    MAIL_MAILER: 'array',
    BROADCAST_CONNECTION: 'log',
    TELESCOPE_ENABLED: 'false',
    FILAMENT_ADMIN_DOMAIN: '',
    DOCS_DOMAIN: '',
    E2E_BASE_URL: baseURL,
    E2E_PORT: port,
    APP_CONFIG_CACHE: '.tmp/e2e/cache/config.php',
    APP_EVENTS_CACHE: '.tmp/e2e/cache/events.php',
    APP_PACKAGES_CACHE: '.tmp/e2e/cache/packages.php',
    APP_ROUTES_CACHE: '.tmp/e2e/cache/routes.php',
    APP_SERVICES_CACHE: '.tmp/e2e/cache/services.php',
};

function run(command, args, environment = e2eEnvironment) {
    const result = spawnSync(command, args, {
        cwd: root,
        env: environment,
        encoding: 'utf8',
        stdio: 'inherit',
    });

    if (result.error) {
        throw result.error;
    }

    if (result.status !== 0) {
        process.exit(result.status ?? 1);
    }
}

function runPlaywright(specs, serverless = false) {
    run(process.execPath, [playwrightCli, 'test', ...specs, ...extraArgs], {
        ...e2eEnvironment,
        E2E_SERVERLESS: serverless ? '1' : '0',
    });
}

function buildAssets() {
    run(process.execPath, [viteCli, 'build'], process.env);
}

function serve() {
    fs.rmSync(runtimeDir, { recursive: true, force: true });
    fs.mkdirSync(cacheDir, { recursive: true });
    fs.writeFileSync(databasePath, '');

    run(php, [
        'artisan',
        'migrate:fresh',
        '--force',
        '--seed',
        '--seeder=Database\\Seeders\\E2EBrowserSeeder',
    ]);

    const child = spawn(php, ['artisan', 'serve', '--host=127.0.0.1', `--port=${port}`], {
        cwd: root,
        env: e2eEnvironment,
        stdio: 'inherit',
    });

    const stop = (signal) => {
        if (!child.killed) {
            child.kill(signal);
        }
    };

    process.once('SIGINT', () => stop('SIGINT'));
    process.once('SIGTERM', () => stop('SIGTERM'));
    process.once('exit', () => stop('SIGTERM'));

    child.on('error', (error) => {
        console.error(error);
        process.exit(1);
    });

    child.on('exit', (code, signal) => {
        if (signal) {
            process.kill(process.pid, signal);
            return;
        }

        process.exit(code ?? 0);
    });
}

switch (mode) {
    case 'serve':
        serve();
        break;
    case 'tracking':
        runPlaywright(['tests/e2e/tracking-bootstrap.spec.js'], true);
        break;
    case 'app':
        buildAssets();
        runPlaywright(['tests/e2e/homepage-popup.spec.js']);
        break;
    case 'all':
        buildAssets();
        runPlaywright(['tests/e2e/tracking-bootstrap.spec.js'], true);
        runPlaywright(['tests/e2e/homepage-popup.spec.js']);
        break;
    default:
        console.error(`Unknown E2E mode: ${mode}`);
        process.exit(1);
}
