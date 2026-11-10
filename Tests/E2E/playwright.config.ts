import { defineConfig, devices } from '@playwright/test';

/**
 * These tests need a running TYPO3 with EXT:inquiry and a consumer that
 * resolves items — the extension on its own renders nothing clickable.
 * Point them at such an instance through the environment rather than baking a
 * host name into the repository:
 *
 *   INQUIRY_BASE_URL=https://example.ddev.site \
 *   INQUIRY_PRODUCT_PATH=/some-product \
 *   INQUIRY_LIST_PATH=/inquiry-list \
 *   npm test
 *
 * See README.md for the rest.
 */
const baseURL = process.env.INQUIRY_BASE_URL;

if (!baseURL) {
    throw new Error(
        'INQUIRY_BASE_URL is not set. These tests drive a real TYPO3 instance; '
        + 'see Tests/E2E/README.md for what it has to provide.'
    );
}

export default defineConfig({
    testDir: './specs',
    fullyParallel: false,
    forbidOnly: !!process.env.CI,
    retries: process.env.CI ? 1 : 0,
    workers: 1,
    reporter: process.env.CI ? [['github'], ['html', { open: 'never' }]] : [['list']],
    use: {
        baseURL,
        // ddev serves a self-signed certificate
        ignoreHTTPSErrors: true,
        trace: 'retain-on-failure',
        screenshot: 'only-on-failure',
    },
    projects: [
        {
            name: 'chromium',
            use: { ...devices['Desktop Chrome'] },
        },
    ],
});
