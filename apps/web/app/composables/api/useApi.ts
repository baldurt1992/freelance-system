/**
 * Cliente HTTP hacia la API tenant (Bearer Sanctum).
 * Usa ofetch para evitar colisión de tipos con Nitro $fetch interno.
 * Los errores se propagan sin tragar; los consumidores deben usar parseApiError en el catch.
 */
import { ofetch, type FetchOptions } from "ofetch";

export function useApi() {
  const config = useRuntimeConfig();
  const auth = useAuthStore();

  const client = ofetch.create({
    baseURL: config.public.apiBaseUrl,
  });

  async function api<T>(path: string, options: FetchOptions = {}): Promise<T> {
    const headers: Record<string, string> = {
      Accept: "application/json",
      ...(options.headers as Record<string, string> | undefined),
    };

    if (auth.token) {
      headers.Authorization = `Bearer ${auth.token}`;
    }

    // No onResponseError aquí: deja que los consumidores usen parseApiError en catch.
    return await client<T>(path, {
      ...options,
      headers,
      responseType: options.responseType ?? "json",
    } as any);
  }

  return { api };
}
