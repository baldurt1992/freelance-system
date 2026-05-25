import { z } from "zod";

export const ProjectPaymentKindSchema = z.enum(["partial", "closure"]);

export type ProjectPaymentKind = z.infer<typeof ProjectPaymentKindSchema>;

export const ProjectPaymentSchema = z.object({
  id: z.number().int().positive(),
  project_id: z.number().int().positive(),
  amount_cents: z.number().int().nonnegative(),
  kind: ProjectPaymentKindSchema,
  paid_at: z.string().regex(/^\d{4}-\d{2}-\d{2}$/),
  created_at: z.string().datetime(),
});

export type ProjectPayment = z.infer<typeof ProjectPaymentSchema>;

export const RegisterPartialPaymentInputSchema = z.object({
  amount_cents: z.number().int().positive(),
  paid_at: z.string().regex(/^\d{4}-\d{2}-\d{2}$/).optional(),
});

export type RegisterPartialPaymentInput = z.infer<
  typeof RegisterPartialPaymentInputSchema
>;

export const MarkProjectPaidInputSchema = z.object({
  paid_at: z.string().regex(/^\d{4}-\d{2}-\d{2}$/).optional(),
});

export type MarkProjectPaidInput = z.infer<typeof MarkProjectPaidInputSchema>;

export const MarkProjectPaidResponseSchema = z.object({
  project: z.object({
    id: z.number().int().positive(),
    is_fully_paid: z.boolean(),
    paid_total_cents: z.number().int().nonnegative(),
    balance_due_cents: z.number().int().nonnegative(),
  }),
  payment: ProjectPaymentSchema.nullable(),
});

export type MarkProjectPaidResponse = z.infer<
  typeof MarkProjectPaidResponseSchema
>;

export const ProjectPaymentListSchema = z.object({
  data: z.array(ProjectPaymentSchema),
});

export type ProjectPaymentListResponse = z.infer<
  typeof ProjectPaymentListSchema
>;
