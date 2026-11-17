import { expect, test } from '@playwright/test';
import { createApprovedQuotation, login } from './helpers';

test('orçamento enviado, aprovado pelo link e transformado em pedido', async ({ page }) => {
    await login(page);

    const { total } = await createApprovedQuotation(page, 3);

    await expect(page.getByTestId('order-total')).toHaveText(total);
    await expect(page.getByTestId('schedule-table').locator('tbody tr')).toHaveCount(3);
    await expect(page.getByText('3×').first()).toBeVisible();
});
