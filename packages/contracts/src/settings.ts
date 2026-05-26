import { z } from "zod";

export const TenantSettingsSchema = z.object({
  id: z.string().min(1),
  name: z.string().min(1),
  tax_enabled: z.boolean(),
  currency: z.string().min(3).max(3),
  tax_rate: z.number().min(0).max(100),
});

export type TenantSettings = z.infer<typeof TenantSettingsSchema>;

export const UpdateTenantSettingsSchema = z.object({
  tax_enabled: z.boolean(),
});

export type UpdateTenantSettingsInput = z.infer<typeof UpdateTenantSettingsSchema>;
