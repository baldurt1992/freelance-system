import { defineStore } from "pinia";
import { useApi } from "~/composables/api/useApi";
import type { ApiUser, LoginResponse, MeResponse } from "~/types/auth";

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

  const user = ref<ApiUser | null>(null);
  const initialized = ref(false);
  const loading = ref(false);

  const isAuthenticated = computed(() => Boolean(token.value && user.value));

  async function login(email: string, password: string): Promise<void> {
    const { api } = useApi();
    loading.value = true;

    try {
      const response = await api<LoginResponse>("/auth/login", {
        method: "POST",
        body: { email, password },
      });

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

    const { api } = useApi();
    const response = await api<MeResponse>("/auth/me");

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
    } catch {
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
