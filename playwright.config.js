const { defineConfig } = require('@playwright/test');

module.exports = defineConfig({
  testDir: 'tests/e2e',
  timeout: 30000,
  retries: process.env.CI ? 2 : 0,
  use: {
    baseURL: process.env.SRT_TEST_URL || 'http://localhost:8089',
    screenshot: 'only-on-failure',
    trace: 'retain-on-failure',
  },
});
