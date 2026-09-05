const { test, expect } = require('@playwright/test');

async function openTester(page) {
  await page.goto('/wp-admin/admin.php?page=shipping-rules-tester-for-woocommerce');

  if (page.url().includes('wp-login.php')) {
    if (!process.env.SRT_ADMIN_USER || !process.env.SRT_ADMIN_PASSWORD) {
      throw new Error('Set SRT_ADMIN_USER and SRT_ADMIN_PASSWORD for the browser suite.');
    }

    await page.locator('#user_login').fill(process.env.SRT_ADMIN_USER);
    await page.locator('#user_pass').fill(process.env.SRT_ADMIN_PASSWORD);
    await page.locator('#wp-submit').click();
    await page.goto('/wp-admin/admin.php?page=shipping-rules-tester-for-woocommerce');
  }

  await page.goto('/wp-admin/plugins.php');
  const pluginRow = page.locator('tr').filter({ hasText: 'AP Shipping Rules Tester for WooCommerce' });
  const activateLink = pluginRow.getByRole('link', { name: /Activate/ });
  if (await activateLink.count()) {
    await activateLink.click();
  }

  await page.goto('/wp-admin/admin.php?page=shipping-rules-tester-for-woocommerce');
  await expect(page.locator('h1')).toHaveText('Shipping Rules Tester');
}

test('runs a local shipping test and renders the result', async ({ page }) => {
  const pluginRequests = [];
  const externalRequests = [];
  const baseOrigin = new URL(process.env.SRT_TEST_URL || 'http://localhost:8089').origin;
  page.on('request', (request) => {
    if (request.url().includes('/shipping-rules-tester-for-woocommerce/')) {
      pluginRequests.push(request.url());
    }
  });

  await openTester(page);
  page.on('request', (request) => {
    if (/^https?:/.test(request.url()) && new URL(request.url()).origin !== baseOrigin) {
      externalRequests.push(request.url());
    }
  });
  await page.locator('select[name="country"]').selectOption('US');
  await page.locator('input[name="value"]').fill('50');
  await page.locator('input[name="weight"]').fill('2');
  await page.locator('input[name="quantity"]').fill('3');
  await page.locator('#srt-submit').click();

  await expect(page.locator('#srt-results')).toContainText('Matched shipping zone');
  await expect(page.locator('#srt-results')).toContainText('Shipping methods');
  await expect(page.locator('#srt-results')).toBeVisible();
  expect(pluginRequests.every((url) => new URL(url).origin === baseOrigin)).toBeTruthy();
  expect(externalRequests).toEqual([]);
});

test('shows a server validation error for malformed input', async ({ page }) => {
  await openTester(page);
  await page.locator('select[name="country"]').selectOption('US');
  await page.locator('input[name="value"]').evaluate((input) => {
    input.value = 'not-a-number';
  });
  await page.locator('#srt-submit').click();

  await expect(page.locator('#srt-status')).toContainText('Enter package values within the supported ranges.');
  await expect(page.locator('#srt-results')).toBeHidden();
});

test('keeps the form usable at a mobile width', async ({ page }) => {
  await page.setViewportSize({ width: 390, height: 844 });
  await openTester(page);

  const grid = page.locator('.srt-grid').first();
  const columns = await grid.evaluate((element) => getComputedStyle(element).gridTemplateColumns);
  expect(columns.split(' ').length).toBe(1);
  await expect(page.locator('select[name="country"]')).toBeEnabled();
});

test('keeps all form controls keyboard focusable', async ({ page }) => {
  await openTester(page);

  const controls = page.locator('#srt-form input, #srt-form select, #srt-submit');
  for (let index = 0; index < await controls.count(); index += 1) {
    const control = controls.nth(index);
    await control.focus();
    await expect(control).toBeFocused();
  }
});
