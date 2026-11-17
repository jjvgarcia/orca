import { expect, test } from '@playwright/test';
import { createApprovedQuotation, login } from './helpers';

test('repetir o mesmo pagamento deixa uma única linha', async ({ page }) => {
    await login(page);
    await createApprovedQuotation(page);

    await page.getByRole('button', { name: 'Registrar' }).first().click();

    const key = await page.locator('input[name="idempotency_key"]').inputValue();
    const receivableId = await page.locator('input[name="receivable_id"]').inputValue();
    const csrf = await page.locator('meta[name="csrf-token"]').getAttribute('content');
    const paidAt = await page.evaluate(() => new Date().toLocaleDateString('sv-SE'));

    const payload = {
        _token: csrf ?? '',
        receivable_id: receivableId,
        amount_cents: '100',
        method: 'pix',
        paid_at: paidAt,
        idempotency_key: key,
    };

    const first = await page.request.post('/payments', {
        form: payload,
        headers: { Accept: 'application/json' },
    });
    expect(first.status()).toBe(201);

    const second = await page.request.post('/payments', {
        form: payload,
        headers: { Accept: 'application/json' },
    });
    expect(second.status()).toBe(200);
    expect((await second.json()).replayed).toBe(true);

    await page.reload();

    await expect(page.getByTestId('payments-table').locator('tbody tr')).toHaveCount(1);
});
