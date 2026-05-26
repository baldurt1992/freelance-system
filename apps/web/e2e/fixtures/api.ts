import type { Client, FinanceEntry, FinanceEntryCreateInput } from "@freelance/contracts";
import { getE2eApiBaseUrl } from "./env";

function requireEnv(name: string): string {
  const value = process.env[name];
  if (!value) {
    throw new Error(`[E2E] Falta variable de entorno ${name}. Copia .env.e2e.example a .env.e2e`);
  }

  return value;
}

export function getApiBaseUrl(): string {
  return getE2eApiBaseUrl();
}

export function getE2eCredentials(): { email: string; password: string } {
  return {
    email: requireEnv("E2E_USER_EMAIL"),
    password: requireEnv("E2E_USER_PASSWORD"),
  };
}

export function uniqueSuffix(): string {
  return `${Date.now()}-${Math.random().toString(36).slice(2, 8)}`;
}

export async function loginAndGetToken(): Promise<string> {
  const { email, password } = getE2eCredentials();
  const response = await fetch(`${getApiBaseUrl()}/auth/login`, {
    method: "POST",
    headers: {
      Accept: "application/json",
      "Content-Type": "application/json",
    },
    body: JSON.stringify({ email, password }),
  });

  if (!response.ok) {
    const body = await response.text();
    throw new Error(`[E2E] Login API falló (${response.status}): ${body}`);
  }

  const payload = (await response.json()) as { token: string };
  return payload.token;
}

async function apiRequest<T>(
  token: string,
  path: string,
  options: RequestInit = {},
): Promise<T> {
  const response = await fetch(`${getApiBaseUrl()}${path}`, {
    ...options,
    headers: {
      Accept: "application/json",
      "Content-Type": "application/json",
      Authorization: `Bearer ${token}`,
      ...(options.headers ?? {}),
    },
  });

  if (!response.ok) {
    const body = await response.text();
    throw new Error(`[E2E] ${options.method ?? "GET"} ${path} falló (${response.status}): ${body}`);
  }

  return response.json() as Promise<T>;
}

export async function createClient(token: string, suffix: string): Promise<Client> {
  return apiRequest<Client>(token, "/clients", {
    method: "POST",
    body: JSON.stringify({
      name: `E2E Cliente ${suffix}`,
      email: `e2e-${suffix}@example.com`,
      phone: null,
      tax_id: null,
      address: null,
      notes: null,
      avatar: null,
    }),
  });
}

export async function createFinanceEntry(
  token: string,
  input: FinanceEntryCreateInput,
): Promise<FinanceEntry> {
  return apiRequest<FinanceEntry>(token, "/finances/entries", {
    method: "POST",
    body: JSON.stringify(input),
  });
}
