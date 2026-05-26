import { defineStore } from "pinia";
import { useAuthApi } from "~/composables/auth/useAuthApi";
import type { SessionTenant } from "@freelance/contracts";

export const useTenantStore = defineStore("tenant", () => {
  const current = ref<SessionTenant | null>(null);

  const displayName = computed(() => current.value?.name ?? "Workspace");
  const currency = computed(() => current.value?.currency ?? "COP");
  const taxEnabled = computed(() => current.value?.tax_enabled ?? false);
  const taxRate = computed(() => current.value?.tax_rate ?? 19);

  function setTenant(tenant: SessionTenant | null): void {
    current.value = tenant;
  }

  function clear(): void {
    current.value = null;
  }

  async function loadFromMe(): Promise<void> {
    const authApi = useAuthApi();
    const response = await authApi.fetchMe();
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
