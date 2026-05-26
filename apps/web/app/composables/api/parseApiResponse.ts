import type { ZodIssue } from "zod";

export class ContractValidationError extends Error {
  override readonly name = "ContractValidationError";

  constructor(
    public readonly label: string,
    public readonly issues: ZodIssue[],
  ) {
    super(`Contract validation failed: ${label}`);
  }
}

export function isContractValidationError(
  error: unknown,
): error is ContractValidationError {
  return error instanceof ContractValidationError;
}

type ApiResponseSchema<T> = {
  safeParse: (
    data: unknown,
  ) =>
    | { success: true; data: T }
    | { success: false; error: { issues: ZodIssue[] } };
};

/** Valida una response JSON contra un schema Zod del paquete de contratos. */
export function parseApiResponse<T>(
  schema: ApiResponseSchema<T>,
  data: unknown,
  label: string,
): T {
  const result = schema.safeParse(data);

  if (!result.success) {
    console.error("[ContractValidation] Response no coincide con contrato", {
      label,
      issues: result.error.issues,
    });
    throw new ContractValidationError(label, result.error.issues);
  }

  return result.data;
}
