import { expect, test } from "@playwright/test";
import { createClient, loginAndGetToken, uniqueSuffix } from "./fixtures/api";
import { selectComboboxOption } from "./helpers/ui";

test.describe("Project manual create", () => {
  test("happy path: crea proyecto y muestra detalle", async ({ page }) => {
    const suffix = uniqueSuffix();
    const token = await loginAndGetToken();
    const client = await createClient(token, suffix);

    const projectName = `E2E Proyecto ${suffix}`;
    const agreedTotal = "10000";

    await page.goto("/projects/new");

    await selectComboboxOption(page, page.locator("#project-client"), client.name);
    await page.locator("#project-name").fill(projectName);
    await page.getByRole("spinbutton", { name: "Total acordado*" }).fill(agreedTotal);

    await page.getByRole("button", { name: "Guardar proyecto" }).click();

    await expect(page).toHaveURL(/\/projects\/\d+$/);
    await expect(page.getByRole("heading", { name: projectName }).first()).toBeVisible();
    await expect(page.getByText(client.name, { exact: true })).toBeVisible();
    await expect(page.getByText("10.000,00 COP").first()).toBeVisible();
  });
});
