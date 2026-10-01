import { expect, type Page } from '@playwright/test';

/**
 * The paths the specs drive. Kept in the environment because every instance
 * names its pages differently — the extension does not ship a page tree.
 */
export const paths = {
    product: process.env.INQUIRY_PRODUCT_PATH ?? '/',
    list: process.env.INQUIRY_LIST_PATH ?? '/',
};

/**
 * The item list lives in the session, so each test starts from a clean context
 * unless it deliberately shares one.
 */
export async function openProduct(page: Page): Promise<void> {
    await page.goto(paths.product, { waitUntil: 'domcontentloaded' });
    await expect(toggleButton(page).first()).toBeVisible();
}

export function toggleButton(page: Page) {
    return page.locator('.toggle-inquiry-item-status-button[data-inquiry-item-uid][data-inquiry-item-type]');
}

/**
 * The badge is deliberately empty at zero rather than showing "0", so read it
 * as "how many does the page claim to hold" and compare against a string.
 * inquiry.js fills it after its own request, so always read it through
 * expect.poll() -- a single read races that request and fails now and then.
 */
export async function counterText(page: Page): Promise<string> {
    const counter = page.locator('.to-inquiry-list .inquiry-item-counter').first();
    if (await counter.count() === 0) {
        return '';
    }

    return (await counter.textContent())?.trim() ?? '';
}

/**
 * Waits for the toggle round trip instead of sleeping: the button marks itself
 * as added, which only happens once the server answered.
 */
export async function addFirstItem(page: Page): Promise<void> {
    const button = toggleButton(page).first();
    await button.click();
    await expect(button).toHaveClass(/\badded\b/);
}

export async function removeFirstItem(page: Page): Promise<void> {
    const button = toggleButton(page).first();
    await button.click();
    await expect(button).not.toHaveClass(/\badded\b/);
}

export async function openList(page: Page): Promise<void> {
    await page.goto(paths.list, { waitUntil: 'domcontentloaded' });
}
