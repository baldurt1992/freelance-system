import { z } from "zod";
import {
  IsoDateTimeStringSchema,
  NullableIsoDateTimeStringSchema,
} from "./common/datetime.js";

export const AuthUserSchema = z.object({
  id: z.number().int().positive(),
  name: z.string().min(1),
  email: z.string().email(),
  email_verified_at: NullableIsoDateTimeStringSchema,
  created_at: NullableIsoDateTimeStringSchema,
});

export type AuthUser = z.infer<typeof AuthUserSchema>;

/** Tenant visible en sesión (`GET /auth/me`). No acoplado al contrato de `/settings`. */
export const SessionTenantSchema = z.object({
  id: z.string().min(1),
  name: z.string().min(1),
  tax_enabled: z.boolean(),
  currency: z.string().min(3).max(3),
  tax_rate: z.number().min(0).max(100),
});

export type SessionTenant = z.infer<typeof SessionTenantSchema>;

export const LoginResponseSchema = z.object({
  token: z.string().min(1),
  token_type: z.string().min(1),
  user: AuthUserSchema,
});

export type LoginResponse = z.infer<typeof LoginResponseSchema>;

export const MeResponseSchema = z.object({
  user: AuthUserSchema,
  tenant: SessionTenantSchema,
});

export type MeResponse = z.infer<typeof MeResponseSchema>;
