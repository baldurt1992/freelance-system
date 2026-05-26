import { defineStore } from "pinia";
import { useApi } from "~/composables/api/useApi";
import { parseApiError } from "~/composables/api/parseApiError";
import { useAuthApi } from "~/composables/auth/useAuthApi";
import type { AuthUser } from "@freelance/contracts";

const TOKEN_COOKIE = "freelance_auth_token";

export const useAuthStore = defineStore("auth", () => {
  const tokenCookie = useCookie<string | null>(TOKEN_COOKIE, {
    maxAge: 60 * 60 * 24 * 30,
    sameSite: "lax",
    secure: false,
  });

  const token = computed({
    get: () => tokenCookie.value,
    set: (value: string | null) => {
      tokenCookie.value = value;
    },
  });

  const user = ref<AuthUser | null>(null);
  const initialized = ref(false);
  const loading = ref(false);

  const isAuthenticated = computed(() => Boolean(token.value && user.value));

  async function login(email: string, password: string): Promise<void> {
    const authApi = useAuthApi();
    loading.value = true;

    try {
      const response = await authApi.login(email, password);

      token.value = response.token;
      user.value = response.user;
      await fetchMe();
    } finally {
      loading.value = false;
    }
  }

  async function fetchMe(): Promise<void> {
    if (!token.value) {
      user.value = null;
      return;
    }

    const authApi = useAuthApi();
    const response = await authApi.fetchMe();

    user.value = response.user;
    useTenantStore().setTenant(response.tenant);
  }

  async function logout(): Promise<void> {
    const { api } = useApi();

    try {
      if (token.value) {
        await api("/auth/logout", { method: "POST" });
      }
    } catch {
      // Sesión ya inválida en servidor
    } finally {
      token.value = null;
      user.value = null;
      useTenantStore().clear();
    }
  }

  async function initialize(): Promise<void> {
    if (initialized.value) {
      return;
    }

    if (!token.value) {
      initialized.value = true;
      return;
    }

    loading.value = true;

    try {
      await fetchMe();
    } catch (error) {
      const parsed = parseApiError(error);

      if (parsed.kind === "contract") {
        console.error("[AuthStore] Bootstrap abortado: drift de contrato en sesión", {
          message: parsed.message,
        });
      } else {
        console.error("[AuthStore] Bootstrap abortado: sesión no confiable", {
          kind: parsed.kind,
          message: parsed.message,
        });
      }

      token.value = null;
      user.value = null;
      useTenantStore().clear();
    } finally {
      loading.value = false;
      initialized.value = true;
    }
  }

  return {
    token,
    user,
    loading,
    initialized,
    isAuthenticated,
    login,
    logout,
    fetchMe,
    initialize,
  };
});
