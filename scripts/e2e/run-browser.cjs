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
    // Key test-only untuk environment sqlite ephemeral E2E (bukan kredensial produksi).
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
    QR_PROXY_ALLOWED_HOSTS: 'tripay.co.id,127.0.0.1',
    PHP_CLI_SERVER_WORKERS: '4',
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

function runPlaywright(specs, serverless = false, extraEnv = {}) {
    run(process.execPath, [playwrightCli, 'test', ...specs, ...extraArgs], {
        ...e2eEnvironment,
        E2E_SERVERLESS: serverless ? '1' : '0',
        ...extraEnv,
    });
}

function buildAssets() {
    run(process.execPath, [viteCli, 'build'], process.env);
}

/**
 * Pastikan tidak ada bundle SSR tertinggal. Mode yang tidak menjalankan SSR
 * harus tetap menguji jalur fallback client-side.
 */
function removeSsrBundle() {
    fs.rmSync(path.join(root, 'bootstrap', 'ssr'), { recursive: true, force: true });
}

function buildSsrBundle() {
    run(process.execPath, [viteCli, 'build', '--ssr', 'resources/js/ssr.jsx', '--outDir', 'bootstrap/ssr'], process.env);
}

/**
 * Jalankan server SSR di port terpisah, tunggu sampai sehat, lalu jalankan
 * callback. Bundle SSR dibangun lebih dulu supaya halaman benar-benar
 * di-render di server, bukan jatuh ke shell client-only.
 */
async function withSsrServer(callback) {
    buildSsrBundle();

    // Port SSR ditentukan oleh @inertiajs/core (13714) dan harus sama dengan
    // config('inertia.ssr.url') yang dipakai Laravel.
    const ssrPort = '13714';
    const child = spawn(process.execPath, ['bootstrap/ssr/ssr.mjs'], {
        cwd: root,
        env: e2eEnvironment,
        stdio: 'inherit',
    });

    const stop = () => {
        if (!child.killed) {
            child.kill('SIGTERM');
        }
    };

    const ready = (async () => {
        for (let attempt = 0; attempt < 40; attempt += 1) {
            await new Promise((resolve) => setTimeout(resolve, 250));

            try {
                const response = await fetch(`http://127.0.0.1:${ssrPort}/health`);
                if (response.ok) {
                    return true;
                }
            } catch {
                // server belum siap
            }
        }

        return false;
    })();

    try {
        const isReady = await ready;

        if (!isReady) {
            console.error('SSR server tidak siap pada waktu yang ditentukan.');
            stop();
            process.exit(1);
        }

        await callback();
    } finally {
        stop();
    }
}

function serve() {
    // Playwright menjalankan mode ini sebagai webServer. Mode `ssr` sengaja
    // mempertahankan bundle yang baru di-build, jadi penghapusan harus
    // dilewati lewat penanda dari pemanggilnya.
    if (process.env.E2E_KEEP_SSR_BUNDLE !== '1') {
        removeSsrBundle();
    }

    fs.rmSync(runtimeDir, { recursive: true, force: true });
    fs.mkdirSync(cacheDir, { recursive: true });
    fs.writeFileSync(databasePath, '');

    const migrateArgs = [
        'artisan',
        'migrate:fresh',
        '--force',
        '--seed',
        '--seeder=Database\\Seeders\\E2EBrowserSeeder',
    ];

    if (process.env.E2E_SKIP_SCHEMA_DUMP === '1') {
        migrateArgs.push('--schema-path=/dev/null');
    }

    run(php, migrateArgs);

    const child = spawn(php, ['artisan', 'serve', '--host=127.0.0.1', `--port=${port}`, '--no-reload'], {
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
    case 'ssr':
        // Mode khusus: render server-side + bukti konten di HTML awal.
        withSsrServer(async () => {
            buildAssets();
            runPlaywright(
                ['tests/e2e/ssr-content.spec.js', 'tests/e2e/anti-fouc.spec.js'],
                false,
                { E2E_KEEP_SSR_BUNDLE: '1' }
            );
        }).catch((error) => {
            console.error(error);
            process.exit(1);
        });
        break;
    case 'tracking':
        runPlaywright(['tests/e2e/tracking-bootstrap.spec.js'], true);
        break;
    case 'app':
        removeSsrBundle();
        buildAssets();
        runPlaywright([
            'tests/e2e/homepage-popup.spec.js',
            'tests/e2e/storefront-order.spec.js',
            'tests/e2e/deposit-flow.spec.js',
            'tests/e2e/deposit-pricing-sync.spec.js',
            'tests/e2e/invoice-detail.spec.js',
            'tests/e2e/member-settings.spec.js',
            'tests/e2e/seo-boundaries.spec.js',
            'tests/e2e/storefront-navbar.spec.js',
            'tests/e2e/storefront-order-mobile-gutter.spec.js',
            'tests/e2e/storefront-google-signup.spec.js',
            'tests/e2e/article-faq-schema.spec.js',
        ]);
        break;
    case 'all':
        removeSsrBundle();
        buildAssets();
        runPlaywright(['tests/e2e/tracking-bootstrap.spec.js'], true);
        runPlaywright([
            'tests/e2e/homepage-popup.spec.js',
            'tests/e2e/storefront-order.spec.js',
            'tests/e2e/deposit-flow.spec.js',
            'tests/e2e/deposit-pricing-sync.spec.js',
            'tests/e2e/invoice-detail.spec.js',
            'tests/e2e/member-settings.spec.js',
            'tests/e2e/seo-boundaries.spec.js',
            'tests/e2e/storefront-navbar.spec.js',
            'tests/e2e/storefront-order-mobile-gutter.spec.js',
            'tests/e2e/storefront-google-signup.spec.js',
            'tests/e2e/article-faq-schema.spec.js',
        ]);
        break;
    default:
        console.error(`Unknown E2E mode: ${mode}`);
        process.exit(1);
}
