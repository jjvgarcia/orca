import { expect, type Page } from '@playwright/test';

export async function login(page: Page): Promise<void> {
    await page.goto('/login');
    await page.getByLabel('E-mail').fill('demo@orca.test');
    await page.getByLabel('Senha').fill('password');
    await page.getByRole('button', { name: 'Entrar' }).click();
    await expect(page).toHaveURL(/\/dashboard$/);
}

export async function createApprovedQuotation(page: Page, installments = 3): Promise<{ orderUrl: string; total: string }> {
    await page.goto('/quotations/create');

    await page.getByTestId('quotation-customer').selectOption({ index: 1 });
    await page.getByTestId('item-0-service').selectOption({ index: 1 });
    await page.getByTestId('item-0-quantity').fill('2');
    await page.getByLabel('Parcelamento').selectOption(String(installments));

    // espera o resumo recalcular depois dos eventos do Livewire
    await expect(page.getByTestId('quotation-total')).not.toHaveText('R$ 0,00');

    await page.getByTestId('save-and-send').click();
    await expect(page).toHaveURL(/\/quotations\/\d+$/);

    const total = (await page.getByTestId('quotation-total').innerText()).trim();
    const quotationUrl = page.url();
    const publicLink = await page.locator('#public-link').inputValue();

    await page.goto(publicLink);
    page.once('dialog', (dialog) => dialog.accept());
    await page.getByTestId('approve-button').click();
    await expect(page.locator('.seal-approved').first()).toBeVisible();

    await page.goto(quotationUrl);
    await page.getByTestId('order-link').click();
    await expect(page).toHaveURL(/\/orders\/\d+$/);

    return { orderUrl: page.url(), total };
}
