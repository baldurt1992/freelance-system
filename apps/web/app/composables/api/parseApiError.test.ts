import { describe, expect, it } from "vitest";
import { ContractValidationError } from "./parseApiResponse";
import { parseApiError, FALLBACK_MESSAGES } from "./parseApiError";

describe("parseApiError", () => {
  it("mapea ContractValidationError a kind contract con mensaje dedicado", () => {
    const error = new ContractValidationError("auth.login", [
      {
        code: "invalid_type",
        expected: "number",
        path: ["user", "id"],
        message: "Invalid input",
      },
    ]);

    const parsed = parseApiError(error);

    expect(parsed.kind).toBe("contract");
    expect(parsed.status).toBeNull();
    expect(parsed.message).toBe(FALLBACK_MESSAGES.contract);
    expect(parsed.message).not.toBe(FALLBACK_MESSAGES.unknown);
  });
});

describe("parseApiResponse", () => {
  it("propaga ContractValidationError para que parseApiError lo distinga", () => {
    const error = new ContractValidationError("auth.me", []);

    expect(parseApiError(error).kind).toBe("contract");
  });
});
