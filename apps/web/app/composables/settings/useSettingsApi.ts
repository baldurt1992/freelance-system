import { useApi } from "~/composables/api/useApi";
import type { TenantSettings, UpdateTenantSettingsInput } from "@freelance/contracts";

/** API de configuración del workspace tenant (IVA, moneda). */
export function useSettingsApi() {
  const { api } = useApi();

  async function getSettings(): Promise<TenantSettings> {
    return api<TenantSettings>("/settings");
  }

  async function updateSettings(input: UpdateTenantSettingsInput): Promise<TenantSettings> {
    return api<TenantSettings>("/settings", {
      method: "PATCH",
      body: input,
    });
  }

  return { getSettings, updateSettings };
}
