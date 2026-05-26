import { describe, expect, it } from "vitest";
import { z } from "zod";
import { LoginResponseSchema } from "@freelance/contracts";
import {
  ContractValidationError,
  isContractValidationError,
  parseApiResponse,
} from "./parseApiResponse";

const SampleSchema = z.object({
  id: z.number().int().positive(),
  name: z.string().min(1),
});

describe("parseApiResponse", () => {
  it("devuelve data tipada cuando el payload es válido", () => {
    const payload = { id: 1, name: "Acme" };

    const result = parseApiResponse(SampleSchema, payload, "sample.find");

    expect(result).toEqual(payload);
  });

  it("lanza ContractValidationError cuando el payload es inválido", () => {
    const payload = { id: "bad", name: "" };

    expect(() => parseApiResponse(SampleSchema, payload, "sample.find")).toThrow(
      ContractValidationError,
    );

    try {
      parseApiResponse(SampleSchema, payload, "sample.find");
    } catch (error) {
      expect(isContractValidationError(error)).toBe(true);
      if (isContractValidationError(error)) {
        expect(error.label).toBe("sample.find");
        expect(error.issues.length).toBeGreaterThan(0);
      }
    }
  });

  it("acepta datetime ISO8601 con offset emitido por Laravel", () => {
    const payload = {
      token: "plain-text-token",
      token_type: "Bearer",
      user: {
        id: 1,
        name: "Admin",
        email: "admin@admin.com",
        email_verified_at: null,
        created_at: "2024-01-15T10:30:00+00:00",
      },
    };

    const result = parseApiResponse(LoginResponseSchema, payload, "auth.login");

    expect(result.user.created_at).toBe(payload.user.created_at);
  });
});
