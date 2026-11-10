import { expect, test } from '@playwright/test';
import { addFirstItem, openList, openProduct, toggleButton } from '../support/inquiry';

test.describe('inquiry list', () => {
    test('shows the item that was added', async ({ page }) => {
        await openProduct(page);
        const name = await toggleButton(page).first().getAttribute('data-inquiry-item-uid');
        await addFirstItem(page);

        await openList(page);

        await expect(page.locator(`[data-inquiry-item-uid="${name}"]`).first()).toBeVisible();
        await expect(page.locator('.inquiry-item-delete').first()).toBeVisible();
    });

    test('carries the contact form', async ({ page }) => {
        await openProduct(page);
        await addFirstItem(page);
        await openList(page);

        // The id comes from the FormDefinition this extension builds, not from
        // the surrounding site — a plain "form" would match the site search.
        const form = page.locator('form#inquiryForm');
        await expect(form).toBeVisible();
        await expect(form.locator('.inquiry-item-fieldset').first()).toBeVisible();
    });

    test('drops an item through the delete button', async ({ page }) => {
        await openProduct(page);
        await addFirstItem(page);
        await openList(page);

        const items = page.locator('.inquiry-item-delete');
        const before = await items.count();
        expect(before).toBeGreaterThan(0);

        await items.first().click();

        await expect(page.locator('.inquiry-item-delete')).toHaveCount(before - 1);
    });

    test('offers the PDF download', async ({ page }) => {
        await openProduct(page);
        await addFirstItem(page);
        await openList(page);

        await expect(page.locator('.inquiry-generate-pdf').first()).toBeVisible();
    });
});
