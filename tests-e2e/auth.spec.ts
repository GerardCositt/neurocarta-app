import { test, expect } from '@playwright/test';

const email = process.env.E2E_EMAIL || 'test@test.com';
const password = process.env.E2E_PASSWORD || 'Password123!';

test('login al panel y acceso a productos', async ({ page }) => {
  await page.goto('/login');
  await page.getByLabel('Email').fill(email);
  await page.getByLabel('Password').fill(password);
  await page.getByRole('button', { name: /log in|iniciar sesión/i }).click();

  // dashboard redirige a /product
  await page.waitForURL('**/product');
  await expect(page.getByText(/Productos de la carta/i)).toBeVisible();
});

