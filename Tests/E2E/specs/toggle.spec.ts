import { expect, test } from '@playwright/test';
import { addFirstItem, counterText, openProduct, removeFirstItem, toggleButton } from '../support/inquiry';

/**
 * The toggle button is the only way an item enters the list, and everything it
 * does happens in the browser: the request, the class change, the label swap
 * and the badge. None of it is reachable from a PHP test.
 */
test.describe('toggle button', () => {
    test('adds an item and counts it', async ({ page }) => {
        await openProduct(page);
        expect(await counterText(page)).toBe('');

        await addFirstItem(page);

        expect(await counterText(page)).toBe('1');
    });

    test('swaps its label between add and remove', async ({ page }) => {
        await openProduct(page);

        const button = toggleButton(page).first();
        const addLabel = await button.getAttribute('data-add-label');
        const removeLabel = await button.getAttribute('data-remove-label');

        expect(addLabel, 'the button carries no add label').toBeTruthy();
        expect(removeLabel, 'the button carries no remove label').toBeTruthy();
        expect(addLabel).not.toBe(removeLabel);

        const label = button.locator('.inquiry-button-label');
        await expect(label).toHaveText(addLabel!.trim());

        await addFirstItem(page);
        await expect(label).toHaveText(removeLabel!.trim());
    });

    test('takes the item back out again', async ({ page }) => {
        await openProduct(page);
        await addFirstItem(page);
        expect(await counterText(page)).toBe('1');

        await removeFirstItem(page);

        // Zero is rendered as an empty badge on purpose, never as "0".
        expect(await counterText(page)).toBe('');
    });

    test('remembers the item across a reload', async ({ page }) => {
        await openProduct(page);
        await addFirstItem(page);

        await page.reload({ waitUntil: 'domcontentloaded' });

        await expect(toggleButton(page).first()).toHaveClass(/\badded\b/);
        expect(await counterText(page)).toBe('1');
    });

    test('never renders the unresolved-item error', async ({ page }) => {
        await openProduct(page);

        // Without a consumer claiming the item the view helper prints this
        // instead of a button — a silent misconfiguration worth catching.
        await expect(page.locator('body')).not.toContainText('ERROR: Item cannot be resolved');
    });
});
