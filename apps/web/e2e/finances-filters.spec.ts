import { expect, test } from "@playwright/test";
import {
  createFinanceEntry,
  loginAndGetToken,
  uniqueSuffix,
} from "./fixtures/api";
import { buildIsolatedFinanceMonth } from "./helpers/finance-month";
import { setFinanceMonth } from "./helpers/ui";

test.describe("Finance filters by month/type", () => {
  test("filtra tabs por mes e ingreso/gasto con datos únicos de la corrida", async ({ page }) => {
    const suffix = uniqueSuffix();
    const token = await loginAndGetToken();
    const target = buildIsolatedFinanceMonth(Date.now());

    const incomeDescription = `E2E Ingreso ${suffix}`;
    const expenseDescription = `E2E Gasto ${suffix}`;
    const otherMonthDescription = `E2E Otro mes ${suffix}`;

    await createFinanceEntry(token, {
      type: "income",
      amount_cents: 100_000,
      occurred_on: `${target.isoMonth}-15`,
      name: incomeDescription,
      description: incomeDescription,
    });

    await createFinanceEntry(token, {
      type: "expense",
      amount_cents: 50_000,
      occurred_on: `${target.isoMonth}-20`,
      name: expenseDescription,
      description: expenseDescription,
    });

    await createFinanceEntry(token, {
      type: "income",
      amount_cents: 99_999,
      occurred_on: `${target.otherIsoMonth}-10`,
      name: otherMonthDescription,
      description: otherMonthDescription,
    });

    await page.goto("/finances");
    await setFinanceMonth(page, target.year, target.monthLabel);

    await page.getByRole("tab", { name: "Ingresos" }).click();
    await expect(page.getByText(incomeDescription)).toBeVisible();
    await expect(page.getByText(expenseDescription)).not.toBeVisible();
    await expect(page.getByText(otherMonthDescription)).not.toBeVisible();

    await page.getByRole("tab", { name: "Gastos" }).click();
    await expect(page.getByText(expenseDescription)).toBeVisible();
    await expect(page.getByText(incomeDescription)).not.toBeVisible();
    await expect(page.getByText(otherMonthDescription)).not.toBeVisible();

    await setFinanceMonth(page, target.year, target.otherMonthLabel);
    await page.getByRole("tab", { name: "Ingresos" }).click();
    await expect(page.getByText(otherMonthDescription)).toBeVisible();
    await expect(page.getByText(incomeDescription)).not.toBeVisible();
    await expect(page.getByText(expenseDescription)).not.toBeVisible();
  });
});
