import type { Locator, Page } from "@playwright/test";

export async function selectComboboxOption(
  page: Page,
  trigger: Locator,
  optionLabel: string | RegExp,
): Promise<void> {
  await trigger.click();
  await page.getByRole("option", { name: optionLabel }).click();
}

export async function setFinanceMonth(
  page: Page,
  year: number,
  monthLabel: string,
): Promise<void> {
  await selectComboboxOption(page, page.locator("#finance-month-year"), String(year));
  await selectComboboxOption(page, page.locator("#finance-month-month"), monthLabel);
}
