import { chromium, type FullConfig } from "@playwright/test";
import fs from "node:fs";
import path from "node:path";
import { fileURLToPath } from "node:url";
import { loginAndGetToken } from "./fixtures/api";
import { getE2eCookieDomain } from "./fixtures/env";

const __dirname = path.dirname(fileURLToPath(import.meta.url));

async function globalSetup(_config: FullConfig) {
  const authDir = path.join(__dirname, ".auth");
  fs.mkdirSync(authDir, { recursive: true });

  const token = await loginAndGetToken();
  const browser = await chromium.launch();
  const context = await browser.newContext();

  await context.addCookies([
    {
      name: "freelance_auth_token",
      value: token,
      domain: getE2eCookieDomain(),
      path: "/",
      sameSite: "Lax",
    },
  ]);

  await context.storageState({ path: path.join(authDir, "user.json") });
  await browser.close();
}

export default globalSetup;
