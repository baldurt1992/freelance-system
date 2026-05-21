import type { ParsedApiError } from "@freelance/contracts";
import { getFieldError, parseApiErrorBody } from "@freelance/contracts";
import { parseApiError } from "./parseApiError";

interface ToastApiErrorOptions {
  title?: string;
  fallback?: string;
}

export function useApiError() {
  const toast = useToast();

  /**
   * Muestra un toast con el error parseado de la API.
   * Prefiere el mensaje del backend; usa fallback si no hay.
   */
  function toastApiError(error: unknown, options: ToastApiErrorOptions = {}) {
    const parsed = parseApiError(error);
    const message =
      options.fallback && parsed.message === parsed.raw
        ? options.fallback
        : parsed.message;

    toast.add({
      title: options.title ?? "Error",
      description: message,
      color: "error",
      duration: 5000,
    });

    return parsed;
  }

  /**
   * Extrae el primer error de un campo para mostrarlo en formularios.
   * @example getFieldError(fieldErrors, 'avatar')
   */
  function getFieldErrorHelper(
    fieldErrors: Record<string, string[]>,
    field: string
  ): string | undefined {
    return getFieldError(fieldErrors, field);
  }

  /**
   * Log estructurado del error para debugging (sin tokens).
   */
  function logApiError(
    tag: string,
    error: unknown,
    context?: Record<string, unknown>
  ) {
    const parsed = parseApiError(error);
    // eslint-disable-next-line no-console
    console.error(`[${tag}] API Error`, {
      kind: parsed.kind,
      status: parsed.status,
      message: parsed.message,
      ...context,
    });
  }

  return {
    parseApiError,
    toastApiError,
    getFieldError: getFieldErrorHelper,
    logApiError,
  };
}
