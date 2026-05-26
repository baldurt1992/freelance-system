/** Base URL del frontend Nuxt (Playwright `baseURL` y origen de la cookie de sesión). */
export function getE2eBaseUrl(): string {
  return process.env.E2E_BASE_URL ?? "http://personal.localhost:3000";
}

/** Base URL de la API tenant (login y fixtures por HTTP). */
export function getE2eApiBaseUrl(): string {
  return process.env.E2E_API_BASE_URL ?? "http://personal.localhost:8000/api/v1";
}

/** Hostname para `freelance_auth_token`; derivado de `E2E_BASE_URL`, no hardcodeado. */
export function getE2eCookieDomain(): string {
  return new URL(getE2eBaseUrl()).hostname;
}
