import { expect, test } from '@playwright/test';
import { addFirstItem, counterText, openProduct, paths, toggleButton } from '../support/inquiry';

/**
 * The list is synchronised between open tabs over a BroadcastChannel. Two tabs
 * are the whole point, so this is the one behaviour that cannot be checked in
 * any other way — not in PHP, not with a single page.
 */
test.describe('synchronisation between tabs', () => {
    test('a second tab picks up an item added in the first', async ({ context }) => {
        const first = await context.newPage();
        const second = await context.newPage();

        await openProduct(first);
        await openProduct(second);

        await expect.poll(() => counterText(second)).toBe('');

        await addFirstItem(first);

        // The channel delivers without a reload; polling through the assertion
        // keeps it honest about how long that may take.
        await expect
            .poll(() => counterText(second), { message: 'the second tab never learned about the item' })
            .toBe('1');

        await expect(toggleButton(second).first()).toHaveClass(/\badded\b/);
    });

    test('a tab that opens later starts from the shared state', async ({ context }) => {
        const first = await context.newPage();
        await openProduct(first);
        await addFirstItem(first);

        const later = await context.newPage();
        await later.goto(paths.product, { waitUntil: 'domcontentloaded' });

        await expect(toggleButton(later).first()).toHaveClass(/\badded\b/);
        await expect.poll(() => counterText(later)).toBe('1');
    });

    test('removal travels between tabs as well', async ({ context }) => {
        const first = await context.newPage();
        const second = await context.newPage();
        await openProduct(first);
        await addFirstItem(first);
        await openProduct(second);
        await expect.poll(() => counterText(second)).toBe('1');

        await toggleButton(first).first().click();
        await expect(toggleButton(first).first()).not.toHaveClass(/\badded\b/);

        await expect
            .poll(() => counterText(second), { message: 'the second tab still shows the removed item' })
            .toBe('');
    });
});
