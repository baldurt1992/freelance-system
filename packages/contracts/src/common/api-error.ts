import { z } from "zod";

export const ApiErrorSchema = z.object({
  message: z.string(),
  errors: z.record(z.array(z.string())).optional(),
});

export type ParsedApiErrorKind =
  | "validation"
  | "unauthorized"
  | "forbidden"
  | "not_found"
  | "server"
  | "network"
  | "unknown";

export interface ParsedApiError {
  kind: ParsedApiErrorKind;
  status: number | null;
  message: string;
  fieldErrors: Record<string, string[]>;
  raw: unknown;
}

export function parseApiErrorBody(data: unknown): {
  message: string;
  fieldErrors: Record<string, string[]>;
} {
  const parsed = ApiErrorSchema.safeParse(data);
  if (parsed.success) {
    return {
      message: parsed.data.message,
      fieldErrors: parsed.data.errors ?? {},
    };
  }
  return { message: "Error desconocido", fieldErrors: {} };
}

export function getFieldError(
  fieldErrors: Record<string, string[]>,
  field: string
): string | undefined {
  return fieldErrors[field]?.[0];
}
