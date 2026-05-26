import { useApi } from "~/composables/api/useApi";
import { parseApiResponse } from "~/composables/api/parseApiResponse";
import {
  TenantSettingsSchema,
  type TenantSettings,
  type UpdateTenantSettingsInput,
} from "@freelance/contracts";

/** API de configuración del workspace tenant (IVA, moneda). */
export function useSettingsApi() {
  const { api } = useApi();

  async function getSettings(): Promise<TenantSettings> {
    const data = await api("/settings");
    return parseApiResponse(TenantSettingsSchema, data, "settings.getSettings");
  }

  async function updateSettings(input: UpdateTenantSettingsInput): Promise<TenantSettings> {
    const data = await api("/settings", {
      method: "PATCH",
      body: input,
    });
    return parseApiResponse(TenantSettingsSchema, data, "settings.updateSettings");
  }

  return { getSettings, updateSettings };
}
