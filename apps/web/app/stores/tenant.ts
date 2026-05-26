import { defineStore } from "pinia";
import { useApi } from "~/composables/api/useApi";
import type { ApiTenant, MeResponse } from "~/types/auth";

export const useTenantStore = defineStore("tenant", () => {
  const current = ref<ApiTenant | null>(null);

  const displayName = computed(() => current.value?.name ?? "Workspace");
  const currency = computed(() => current.value?.currency ?? "COP");
  const taxEnabled = computed(() => current.value?.tax_enabled ?? false);
  const taxRate = computed(() => current.value?.tax_rate ?? 19);

  function setTenant(tenant: ApiTenant | null): void {
    current.value = tenant;
  }

  function clear(): void {
    current.value = null;
  }

  async function loadFromMe(): Promise<void> {
    const { api } = useApi();
    const response = await api<MeResponse>("/auth/me");
    setTenant(response.tenant);
  }

  return {
    current,
    displayName,
    currency,
    taxEnabled,
    taxRate,
    setTenant,
    clear,
    loadFromMe,
  };
});
