import { test, expect } from '@playwright/test';

const email = process.env.E2E_EMAIL || 'test@test.com';
const password = process.env.E2E_PASSWORD || 'Password123!';

async function login(page: any) {
  await page.goto('/login');
  await page.getByLabel('Email').fill(email);
  await page.getByLabel('Password').fill(password);
  await page.getByRole('button', { name: /log in|iniciar sesión/i }).click();
  await page.waitForURL('**/product');
}

test('ajustes: apariencia carga y muestra opciones', async ({ page }) => {
  await login(page);
  await page.goto('/settings/appearance');
  await expect(page.getByText(/Ajustes · Apariencia/i)).toBeVisible();
  await expect(page.getByText(/Logo del local/i)).toBeVisible();
  await expect(page.getByRole('button', { name: 'Claro' })).toBeVisible();
  await expect(page.getByRole('button', { name: 'Oscuro' })).toBeVisible();
  await expect(page.getByRole('button', { name: 'Sistema' })).toBeVisible();
});

test('ajustes: pedidos muestra radios Pedido/Lista', async ({ page }) => {
  await login(page);
  await page.goto('/settings/orders');
  await expect(page.getByText(/Ajustes · Pedidos/i)).toBeVisible();
  await expect(page.getByText(/^Pedido$/)).toBeVisible();
  await expect(page.getByText(/^Lista$/)).toBeVisible();
});

