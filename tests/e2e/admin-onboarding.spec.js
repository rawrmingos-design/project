const { test, expect } = require('@playwright/test');

const ADMIN_BASE = process.env.E2E_ADMIN_BASE_URL || 'http://admin.istanatopup.test:8000';
const adminEmail = process.env.E2E_ADMIN_EMAIL || 'owner@olfastore-demo.test';
const adminPassword = process.env.E2E_ADMIN_PASSWORD || 'DemoTopup123!';

async function safeGoto(page, url) {
  try {
    await page.goto(url, { waitUntil: 'domcontentloaded' });
  } catch (error) {
    const message = String(error?.message || '');
    if (!message.includes('ERR_ABORTED')) {
      throw error;
    }

    await page.waitForLoadState('domcontentloaded');
  }
}

async function submitAdminLogin(page) {
  const email = page.locator('input[name="email"], input[type="email"]').first();
  await expect(email).toBeVisible();
  await email.fill(adminEmail);

  const password = page.locator('input[name="password"], input[type="password"]').first();
  await password.fill(adminPassword);

  await page.getByRole('button', { name: /sign in|masuk|login/i }).first().click();
}

async function ensureOnAdminDashboard(page) {
  for (let attempt = 1; attempt <= 3; attempt += 1) {
    await safeGoto(page, `${ADMIN_BASE}/`);

    const pembeliansLink = page.locator('a[href*="/pembelians"]').first();
    if (await pembeliansLink.isVisible().catch(() => false)) {
      return;
    }

    const email = page.locator('input[name="email"], input[type="email"]').first();
    if (await email.isVisible().catch(() => false)) {
      await submitAdminLogin(page);
      await page.waitForTimeout(700);
      continue;
    }
  }

  throw new Error('Admin login did not reach dashboard after retries.');
}

async function loginAdmin(page) {
  await safeGoto(page, `${ADMIN_BASE}/login`);
  await submitAdminLogin(page);
  await ensureOnAdminDashboard(page);
}

async function goToPembeliansViaUi(page) {
  const navLink = page.locator('a[href*="/pembelians"]').first();
  await expect(navLink).toBeVisible();
  await navLink.click();
  await expect(page).toHaveURL(/\/pembelians(\?.*)?$/);
}

test.describe('Admin onboarding scope behavior', () => {
  test.beforeEach(async ({ page }) => {
    await loginAdmin(page);

    await page.evaluate(() => {
      Object.keys(localStorage)
        .filter((k) => k.startsWith('admin-onboarding:dismissed:'))
        .forEach((k) => localStorage.removeItem(k));
      Object.keys(sessionStorage)
        .filter((k) => k.startsWith('admin-onboarding:dismissed:'))
        .forEach((k) => sessionStorage.removeItem(k));
    });
  });

  test('debug scopes on dashboard and pembelians', async ({ page }) => {
    await safeGoto(page, `${ADMIN_BASE}/`);
    const dashboardScope = await page.locator('[data-onboarding-guide]').first().getAttribute('data-onboarding-scope');
    const closeButton = page.getByRole('button', { name: /^tutup$/i }).first();
    if (await closeButton.isVisible()) {
      await closeButton.click();
    }

    await goToPembeliansViaUi(page);
    const pembeliansScope = await page.locator('[data-onboarding-guide]').first().getAttribute('data-onboarding-scope');

    console.log('dashboardScope=', dashboardScope);
    console.log('pembeliansScope=', pembeliansScope);

    expect(dashboardScope).toBeTruthy();
    expect(pembeliansScope).toBeTruthy();
  });

  test('finish tour on dashboard should not hide onboarding on pembelians', async ({ page }) => {
    await safeGoto(page, `${ADMIN_BASE}/`);
    await expect(page.locator('[data-onboarding-welcome]')).toBeVisible();

    await page.getByRole('button', { name: /mulai panduan/i }).click();
    await expect(page.locator('[data-onboarding-tour]')).toBeVisible();
    await page.getByRole('button', { name: /^selesai$/i }).first().click();

    await goToPembeliansViaUi(page);
    const welcome = page.locator('[data-onboarding-welcome]');
    await expect(welcome).toBeVisible();
    await page.waitForTimeout(1200);
    await expect(welcome).toBeVisible();
  });

  test('close on dashboard should not persist dismissal to pembelians', async ({ page }) => {
    await safeGoto(page, `${ADMIN_BASE}/`);
    await expect(page.locator('[data-onboarding-welcome]')).toBeVisible();

    await page.getByRole('button', { name: /^tutup$/i }).first().click();
    await expect(page.locator('[data-onboarding-welcome]')).toBeHidden();

    await goToPembeliansViaUi(page);
    await expect(page.locator('[data-onboarding-welcome]')).toBeVisible();
  });

  test('lupakan saat ini on dashboard should only dismiss dashboard scope', async ({ page }) => {
    await safeGoto(page, `${ADMIN_BASE}/`);
    await expect(page.locator('[data-onboarding-welcome]')).toBeVisible();

    await page.getByRole('button', { name: /lupakan saat ini/i }).click();
    await expect(page.locator('[data-onboarding-welcome]')).toBeHidden();

    await goToPembeliansViaUi(page);
    await expect(page.locator('[data-onboarding-welcome]')).toBeVisible();
  });
});
