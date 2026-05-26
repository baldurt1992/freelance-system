import type { ParsedApiError, ParsedApiErrorKind } from "@freelance/contracts";
import { parseApiErrorBody } from "@freelance/contracts";
import { isContractValidationError } from "./parseApiResponse";

export const FALLBACK_MESSAGES: Record<ParsedApiErrorKind, string> = {
  network: "No se pudo conectar con el servidor. Intenta de nuevo.",
  unauthorized: "Sesión expirada. Vuelve a iniciar sesión.",
  forbidden: "No tienes permiso para esta acción.",
  not_found: "El recurso no existe o fue eliminado.",
  validation: "Revisa los datos del formulario.",
  server: "Error del servidor. Intenta más tarde.",
  contract:
    "La respuesta del servidor no coincide con lo esperado. Vuelve a intentar; si persiste, contacta soporte.",
  unknown: "Ocurrió un error inesperado.",
};

function mapStatusToKind(status: number | null): ParsedApiErrorKind {
  if (status === null || status === 0) return "network";
  if (status === 401) return "unauthorized";
  if (status === 403) return "forbidden";
  if (status === 404) return "not_found";
  if (status === 422) return "validation";
  if (status === 429) return "server";
  if (status >= 500) return "server";
  return "unknown";
}

/**
 * Normaliza errores de flujos HTTP: API (ofetch/fetch) o drift de contrato runtime.
 */
export function parseApiError(error: unknown): ParsedApiError {
  const raw = error;

  if (isContractValidationError(error)) {
    return {
      kind: "contract",
      status: null,
      message: FALLBACK_MESSAGES.contract,
      fieldErrors: {},
      raw,
    };
  }

  if (error instanceof Error && error.message.includes("fetch")) {
    return {
      kind: "network",
      status: 0,
      message: FALLBACK_MESSAGES.network,
      fieldErrors: {},
      raw,
    };
  }

  if (error && typeof error === "object" && "statusCode" in error) {
    const status = typeof error.statusCode === "number" ? error.statusCode : null;
    const data = "data" in error && error.data !== undefined ? error.data : null;

    const kind = mapStatusToKind(status);
    const { message: backendMessage, fieldErrors } =
      typeof data === "object" && data !== null
        ? parseApiErrorBody(data)
        : { message: "", fieldErrors: {} };

    const firstFieldError = Object.values(fieldErrors)[0]?.[0];
    const message =
      backendMessage ||
      firstFieldError ||
      FALLBACK_MESSAGES[kind] ||
      FALLBACK_MESSAGES.unknown;

    return {
      kind,
      status,
      message,
      fieldErrors,
      raw,
    };
  }

  return {
    kind: "unknown",
    status: null,
    message: FALLBACK_MESSAGES.unknown,
    fieldErrors: {},
    raw,
  };
}
