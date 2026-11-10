import { expect, test } from '@playwright/test';
import { addFirstItem, openProduct } from '../support/inquiry';

/**
 * The fly-in is filled over its own endpoint when it opens, so an empty panel
 * and a populated one are two different round trips.
 */
test.describe('fly-in panel', () => {
    test.skip(({ browserName }) => browserName !== 'chromium', 'one browser is enough for the panel');

    test('announces the endpoint it loads from', async ({ page }) => {
        await openProduct(page);

        const meta = page.locator('meta[name="inquiry-flyin-items"]');
        await expect(meta).toHaveCount(1);
        expect(await meta.getAttribute('content')).toBeTruthy();
    });

    test('lists the item once one was added', async ({ page }) => {
        await openProduct(page);
        await addFirstItem(page);

        const endpoint = await page.locator('meta[name="inquiry-flyin-items"]').getAttribute('content');
        const body = await page.evaluate(async (url) => {
            const response = await fetch(url!, { credentials: 'same-origin' });
            return response.text();
        }, endpoint);

        expect(body).toContain('inquiry-flyin-items');
        expect(body).not.toContain('data-inquiry-flyin-empty="true"');
    });

    test('reports an empty list while nothing was added', async ({ page }) => {
        await openProduct(page);

        const endpoint = await page.locator('meta[name="inquiry-flyin-items"]').getAttribute('content');
        const body = await page.evaluate(async (url) => {
            const response = await fetch(url!, { credentials: 'same-origin' });
            return response.text();
        }, endpoint);

        expect(body).toContain('data-inquiry-flyin-empty="true"');
    });
});
