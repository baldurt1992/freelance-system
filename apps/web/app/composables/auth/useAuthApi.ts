import { useApi } from "~/composables/api/useApi";
import { parseApiResponse } from "~/composables/api/parseApiResponse";
import {
  LoginResponseSchema,
  MeResponseSchema,
  UpdatePasswordResponseSchema,
  type LoginResponse,
  type MeResponse,
  type UpdatePasswordInput,
  type UpdatePasswordResponse,
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

  async function updatePassword(input: UpdatePasswordInput): Promise<UpdatePasswordResponse> {
    const data = await api("/auth/password", {
      method: "PATCH",
      body: input,
    });
    return parseApiResponse(UpdatePasswordResponseSchema, data, "auth.updatePassword");
  }

  return { login, fetchMe, updatePassword };
}
