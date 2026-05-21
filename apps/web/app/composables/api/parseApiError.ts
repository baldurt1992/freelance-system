import type { ParsedApiError, ParsedApiErrorKind } from "@freelance/contracts";
import { parseApiErrorBody } from "@freelance/contracts";

const FALLBACK_MESSAGES: Record<ParsedApiErrorKind, string> = {
  network: "No se pudo conectar con el servidor. Intenta de nuevo.",
  unauthorized: "Sesión expirada. Vuelve a iniciar sesión.",
  forbidden: "No tienes permiso para esta acción.",
  not_found: "El recurso no existe o fue eliminado.",
  validation: "Revisa los datos del formulario.",
  server: "Error del servidor. Intenta más tarde.",
  unknown: "Ocurrió un error inesperado.",
};

function mapStatusToKind(status: number | null): ParsedApiErrorKind {
  if (status === null || status === 0) return "network";
  if (status === 401) return "unauthorized";
  if (status === 403) return "forbidden";
  if (status === 404) return "not_found";
  if (status === 422) return "validation";
  if (status >= 500) return "server";
  return "unknown";
}

/**
 * Normaliza cualquier error de la API (ofetch / fetch) a un ParsedApiError.
 * Usa el status HTTP y el cuerpo JSON según el contrato ApiErrorSchema.
 */
export function parseApiError(error: unknown): ParsedApiError {
  const raw = error;

  // 1. Detectar error de red (no hay respuesta del servidor)
  if (error instanceof Error && error.message.includes("fetch")) {
    return {
      kind: "network",
      status: 0,
      message: FALLBACK_MESSAGES.network,
      fieldErrors: {},
      raw,
    };
  }

  // 2. Errores de ofetch: tienen statusCode y data
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

  // 3. Fallback para cualquier otro error
  return {
    kind: "unknown",
    status: null,
    message: FALLBACK_MESSAGES.unknown,
    fieldErrors: {},
    raw,
  };
}
