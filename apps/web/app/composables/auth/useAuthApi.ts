import { useApi } from "~/composables/api/useApi";
import { parseApiResponse } from "~/composables/api/parseApiResponse";
import {
  LoginResponseSchema,
  MeResponseSchema,
  type LoginResponse,
  type MeResponse,
} from "@freelance/contracts";

/** API HTTP de autenticación con validación runtime de contratos. */
export function useAuthApi() {
  const { api } = useApi();

  async function login(email: string, password: string): Promise<LoginResponse> {
    const data = await api("/auth/login", {
      method: "POST",
      body: { email, password },
    });
    return parseApiResponse(LoginResponseSchema, data, "auth.login");
  }

  async function fetchMe(): Promise<MeResponse> {
    const data = await api("/auth/me");
    return parseApiResponse(MeResponseSchema, data, "auth.me");
  }

  return { login, fetchMe };
}
