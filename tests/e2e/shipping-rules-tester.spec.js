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

async function selectCountry(page, code) {
  const search = page.locator('#srt-country-search');
  await search.fill(code);
  const option = page.locator(`#srt-country-options [role="option"][data-country-code="${code}"]`);
  await expect(option).toBeVisible();
  await option.click();
  await expect(page.locator('select[name="country"]')).toHaveValue(code);
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
  await expect(page.locator('label').filter({ hasText: /Package value \([A-Z]+\)/ })).toHaveCount(1);
  await expect(page.locator('label').filter({ hasText: /Total package weight \([^)]+\)/ })).toHaveCount(1);
  page.on('request', (request) => {
    if (/^https?:/.test(request.url()) && new URL(request.url()).origin !== baseOrigin) {
      externalRequests.push(request.url());
    }
  });
  await selectCountry(page, 'US');
  await page.locator('input[name="value"]').fill('50');
  await page.locator('input[name="weight"]').fill('2');
  await page.locator('input[name="quantity"]').fill('3');
  await page.locator('#srt-submit').click();

  await expect(page.locator('#srt-results')).toContainText('Matched shipping zone');
  await expect(page.locator('#srt-results')).toContainText('Zone matching rules');
  await expect(page.locator('#srt-form')).toBeHidden();
  await expect(page.locator('#srt-result-actions')).toBeVisible();
  await expect(page.locator('#srt-results')).toBeFocused();
  await expect(page.locator('#srt-results')).toContainText('Shipping methods');
  await expect(page.locator('#srt-results')).not.toContainText('This method returned a zero-cost rate.');
  await expect(page.locator('#srt-results')).toBeVisible();
  expect(pluginRequests.every((url) => new URL(url).origin === baseOrigin)).toBeTruthy();
  expect(externalRequests).toEqual([]);
});

test('collapses parameters for results and supports edit and reset actions', async ({ page }) => {
  await openTester(page);
  await selectCountry(page, 'US');
  await page.locator('input[name="value"]').fill('35');
  await page.locator('#srt-submit').click();

  await expect(page.locator('#srt-results')).toContainText('Matched shipping zone');
  await expect(page.locator('#srt-form')).toBeHidden();
  await expect(page.locator('#srt-edit-parameters')).toBeVisible();

  await page.locator('#srt-edit-parameters').click();
  await expect(page.locator('#srt-form')).toBeVisible();
  await expect(page.locator('#srt-result-actions')).toBeHidden();
  await page.locator('input[name="value"]').fill('45');
  await page.locator('#srt-submit').click();
  await expect(page.locator('#srt-form')).toBeHidden();
  await expect(page.locator('#srt-results')).toContainText('45.00');

  await page.locator('#srt-result-reset').click();
  await expect(page.locator('#srt-form')).toBeVisible();
  await expect(page.locator('#srt-results')).toBeHidden();
  await expect(page.locator('#srt-result-actions')).toBeHidden();
  await expect(page.locator('select[name="country"]')).toHaveValue('');
  await expect(page.locator('#srt-country-search')).toHaveValue('');
});

test('selects a country from the searchable dropdown with the keyboard', async ({ page }) => {
  await openTester(page);

  const search = page.locator('#srt-country-search');
  await search.fill('US');
  await expect(page.locator('.srt-country-option').first()).toBeVisible();
  await expect(page.locator('.srt-form')).toHaveCSS('overflow', 'visible');
  await search.press('ArrowDown');
  await expect(search).toHaveAttribute('aria-activedescendant', 'srt-country-option-us');
  await search.press('Enter');

  await expect(page.locator('select[name="country"]')).toHaveValue('US');
  await expect(search).toHaveValue(/🇺🇸 United States/);
  await expect(page.locator('#srt-package-section')).toBeVisible();
});

test('reveals destination fields progressively using the selected country regions', async ({ page }) => {
  await openTester(page);

  const state = page.locator('#srt-state');
  const postcode = page.locator('#srt-postcode');
  const city = page.locator('#srt-city');
  const countrySearch = page.locator('#srt-country-search');

  await expect(state).toBeHidden();
  await expect(postcode).toBeHidden();
  await expect(city).toBeHidden();
  await expect(page.locator('#srt-package-section')).toBeHidden();
  await expect(page.locator('#srt-form-actions')).toBeHidden();
  await expect(page.locator('#srt-summary-card')).toBeHidden();

  await countrySearch.fill('singapore');
  await expect(page.locator('#srt-country-options [data-country-code="SG"]')).toHaveCount(1);
  await expect(page.locator('#srt-country-options [data-country-code="US"]')).toHaveCount(0);
  await countrySearch.fill('US');
  const unitedStatesOption = page.locator('#srt-country-options [data-country-code="US"]');
  await expect(unitedStatesOption.locator('.srt-country-flag')).toHaveAttribute('data-flag', '🇺🇸');
  await countrySearch.fill('');

  await selectCountry(page, 'SG');
  await expect(page.locator('#srt-package-section')).toBeVisible();
  await expect(page.locator('#srt-form-actions')).toBeVisible();
  await expect(page.locator('#srt-summary-card')).toBeVisible();
  const desktopPackageColumns = await page.locator('.srt-quick-package').evaluate((element) => (
    getComputedStyle(element).gridTemplateColumns.split(' ').length
  ));
  expect(desktopPackageColumns).toBe(2);
  for (const name of ['value', 'weight', 'quantity']) {
    const field = page.locator(`input[name="${name}"]`);
    await expect(field).toHaveAttribute('required', '');
    await expect(field.locator('xpath=ancestor::label')).toContainText('*');
  }
  await expect(page.locator('#srt-product')).not.toHaveAttribute('required');
  const advancedToggle = page.locator('#srt-advanced-toggle');
  await advancedToggle.click();
  await expect(page.locator('.srt-item-value').first()).toHaveAttribute('required', '');
  await advancedToggle.click();
  await expect(page.locator('.srt-item-value').first()).not.toHaveAttribute('required');
  await expect(state).toBeHidden();
  await expect(postcode).toBeVisible();
  await expect(city).toBeHidden();
  await page.locator('#srt-skip-postcode').click();
  await expect(city).toBeVisible();

  await selectCountry(page, 'US');
  await expect(countrySearch).toHaveValue(/🇺🇸 United States/);
  await expect(state).toBeVisible();
  expect(await state.locator('option').count()).toBeGreaterThan(1);
  await expect(postcode).toBeHidden();
  await expect(city).toBeHidden();

  await state.selectOption('CA');
  await expect(postcode).toBeVisible();
  await expect(city).toBeHidden();

  await postcode.fill('90210');
  await expect(city).toBeVisible();

  const fieldWidths = await Promise.all([countrySearch, state, postcode, city].map(async (field) => (
    (await field.boundingBox()).width
  )));
  expect(Math.max(...fieldWidths) - Math.min(...fieldWidths)).toBeLessThan(1);

  await selectCountry(page, 'CA');
  await expect(state).toBeVisible();
  await expect(postcode).toBeHidden();
  await expect(city).toBeHidden();
  await expect(postcode).toHaveValue('');
});

test('places the unrelated-notice clarification outside the tester UI', async ({ page }) => {
  await openTester(page);

  await page.locator('.srt-hero-copy').evaluate((container) => {
    const notice = document.createElement('div');
    notice.className = 'notice notice-warning';
    notice.textContent = 'Action Scheduler: 5 past-due actions found.';
    container.appendChild(notice);
  });

  const notice = page.locator('#wpbody-content > .notice.notice-info').filter({
    hasText: 'About other admin notices:',
  });
  const schedulerNotice = page.locator('#wpbody-content > .notice.notice-warning').filter({
    hasText: 'Action Scheduler: 5 past-due actions found.',
  });
  await expect(notice).toContainText('About other admin notices:');
  await expect(notice).toContainText('unrelated to Shipping Rules Tester');
  await expect(notice).toContainText('does not schedule tasks or send notifications');
  await expect(schedulerNotice).toBeVisible();
  await expect(page.locator('.srt-wrap .notice')).toHaveCount(0);

  const appearsBeforeTester = await notice.evaluate((element) => (
    element.compareDocumentPosition(document.querySelector('.srt-wrap')) & Node.DOCUMENT_POSITION_FOLLOWING
  ));
  expect(appearsBeforeTester).toBeTruthy();

  const schedulerAppearsBeforeTester = await schedulerNotice.evaluate((element) => (
    element.compareDocumentPosition(document.querySelector('.srt-wrap')) & Node.DOCUMENT_POSITION_FOLLOWING
  ));
  expect(schedulerAppearsBeforeTester).toBeTruthy();

  const noticeBoxes = await Promise.all([notice, schedulerNotice].map((panel) => panel.boundingBox()));
  expect(noticeBoxes[0].x).toBe(noticeBoxes[1].x);
  expect(noticeBoxes[0].width).toBe(noticeBoxes[1].width);
});

test('shows a server validation error for out-of-range input', async ({ page }) => {
  await openTester(page);
  await selectCountry(page, 'US');
  await page.locator('input[name="value"]').evaluate((input) => {
    input.removeAttribute('min');
    input.value = '-1';
  });
  await page.locator('#srt-submit').click();

  await expect(page.locator('#srt-status')).toContainText('Enter package values within the supported ranges.');
  await expect(page.locator('#srt-results')).toBeHidden();
});

test('keeps the form usable at a mobile width', async ({ page }) => {
  await page.setViewportSize({ width: 390, height: 844 });
  await openTester(page);

  const grid = page.locator('.srt-field-grid').first();
  const columns = await grid.evaluate((element) => getComputedStyle(element).gridTemplateColumns);
  expect(columns.split(' ').length).toBe(1);
  const countrySearch = page.locator('#srt-country-search');
  await expect(countrySearch).toBeVisible();
  await selectCountry(page, 'US');
  const packageColumns = await page.locator('.srt-quick-package').evaluate((element) => (
    getComputedStyle(element).gridTemplateColumns.split(' ').length
  ));
  expect(packageColumns).toBe(1);
});

test('keeps all form controls keyboard focusable', async ({ page }) => {
  await openTester(page);

  const controls = page.locator('#srt-form input:not([type="hidden"]):visible, #srt-form select:visible, #srt-submit:visible');
  for (let index = 0; index < await controls.count(); index += 1) {
    const control = controls.nth(index);
    await control.focus();
    await expect(control).toBeFocused();
  }
});

test('does not add assets or output to the frontend', async ({ page }) => {
  await page.goto('/');

  await expect(page.locator('script[src*="shipping-rules-tester-for-woocommerce"]')).toHaveCount(0);
  await expect(page.locator('link[href*="shipping-rules-tester-for-woocommerce"]')).toHaveCount(0);
  await expect(page.locator('.srt-wrap')).toHaveCount(0);
});

test('exposes a direct tester link on the Plugins screen', async ({ page }) => {
  await openTester(page);
  await page.goto('/wp-admin/plugins.php');

  const pluginRow = page.locator('tr').filter({ hasText: 'AP Shipping Rules Tester for WooCommerce' });
  await expect(pluginRow.getByRole('link', { name: 'Test shipping rules' })).toHaveAttribute(
    'href',
    /admin\.php\?page=shipping-rules-tester-for-woocommerce/
  );
});

test('compares two scenarios in the browser without saving them', async ({ page }) => {
  await openTester(page);
  await selectCountry(page, 'US');
  await page.locator('input[name="value"]').fill('20');
  await page.locator('input[name="weight"]').fill('1');
  await page.locator('#srt-submit').click();
  await expect(page.locator('#srt-keep')).toBeVisible();

  await page.locator('#srt-keep').click();
  await page.locator('input[name="value"]').fill('40');
  await page.locator('input[name="weight"]').fill('3');
  await page.locator('#srt-submit').click();

  await expect(page.locator('#srt-results')).toContainText('Scenario comparison');
  await expect(page.locator('#srt-results')).toContainText('20.00');
  await expect(page.locator('#srt-results')).toContainText('40.00');
  await expect(page.locator('#srt-clear')).toBeVisible();
  await page.locator('#srt-clear').click();
  await expect(page.locator('#srt-results')).toBeHidden();
});

test('uses saved product context for a local test', async ({ page }) => {
  await openTester(page);
  await selectCountry(page, 'US');
  const productOption = page.locator('#srt-product option').nth(1);
  await expect(productOption).toHaveCount(1);
  const productName = (await productOption.innerText()).trim().replace(/\s+/g, ' ').replace(/ \(#\d+\)$/, '');
  await page.locator('#srt-product').selectOption({ index: 1 });
  await expect(page.locator('input[name="value"]')).toBeDisabled();
  await expect(page.locator('input[name="weight"]')).toBeDisabled();
  await page.locator('input[name="quantity"]').fill('2');
  await page.locator('#srt-submit').click();

  await expect(page.locator('#srt-results')).toContainText('Package items');
  await expect(page.locator('#srt-results')).toContainText(productName);
});

test('builds and tests an advanced multi-item package', async ({ page }) => {
  await openTester(page);
  await selectCountry(page, 'US');
  await page.locator('#srt-advanced-toggle').click();
  await expect(page.locator('#srt-advanced-panel')).toBeVisible();
  await page.locator('#srt-items-list [data-item-row] .srt-item-value').first().fill('12');
  await page.locator('#srt-items-list [data-item-row] .srt-item-weight').first().fill('1.5');
  await page.locator('#srt-items-list [data-item-row] .srt-item-quantity').first().fill('2');
  await page.locator('#srt-add-item').click();
  await expect(page.locator('#srt-items-list [data-item-row]')).toHaveCount(2);
  await page.locator('#srt-items-list [data-item-row]').nth(1).locator('.srt-item-value').fill('8');
  await page.locator('#srt-items-list [data-item-row]').nth(1).locator('.srt-item-weight').fill('0.5');
  await page.locator('#srt-submit').click();

  await expect(page.locator('#srt-results')).toContainText('Package items');
  await expect(page.locator('#srt-results')).toContainText('Items');
  await expect(page.locator('#srt-results')).toContainText('32.00');
});

test('applies a quick scenario preset without leaving the page', async ({ page }) => {
  await openTester(page);
  await selectCountry(page, 'US');
  await page.locator('[data-preset="heavy"]').click();
  await expect(page.locator('input[name="value"]')).toHaveValue('120');
  await expect(page.locator('input[name="weight"]')).toHaveValue('25');
  await expect(page.locator('#srt-summary-totals')).toContainText('25.00');
});
