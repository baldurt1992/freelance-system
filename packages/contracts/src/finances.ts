import { z } from "zod";
import { IsoDateTimeStringSchema } from "./common/datetime.js";
import { PaginationMetaSchema } from "./common/pagination.js";

export const FinanceEntryTypeSchema = z.enum(["income", "expense"]);
export type FinanceEntryType = z.infer<typeof FinanceEntryTypeSchema>;

export const FinanceSummaryLabelSchema = z.enum(["surplus", "shortfall", "balanced"]);
export type FinanceSummaryLabel = z.infer<typeof FinanceSummaryLabelSchema>;

export const FinanceEntrySchema = z.object({
  id: z.number().int().positive(),
  type: FinanceEntryTypeSchema,
  amount_cents: z.number().int().positive(),
  occurred_on: z.string().regex(/^\d{4}-\d{2}-\d{2}$/),
  description: z.string().nullable(),
  category: z.string().nullable(),
  source_type: z.string().nullable(),
  source_id: z.number().int().positive().nullable(),
  is_manual: z.boolean(),
  created_at: IsoDateTimeStringSchema,
  updated_at: IsoDateTimeStringSchema,
});

export type FinanceEntry = z.infer<typeof FinanceEntrySchema>;

export const FinanceEntryCreateSchema = z.object({
  type: FinanceEntryTypeSchema,
  amount_cents: z.number().int().positive(),
  occurred_on: z.string().regex(/^\d{4}-\d{2}-\d{2}$/),
  description: z.string().min(1),
  category: z.string().nullable().optional(),
});

export type FinanceEntryCreateInput = z.infer<typeof FinanceEntryCreateSchema>;

export const FinanceEntryUpdateSchema = z.object({
  amount_cents: z.number().int().positive().optional(),
  occurred_on: z.string().regex(/^\d{4}-\d{2}-\d{2}$/).optional(),
  description: z.string().min(1).optional(),
  category: z.string().nullable().optional(),
});

export type FinanceEntryUpdateInput = z.infer<typeof FinanceEntryUpdateSchema>;

export const FinanceEntryListSchema = z.object({
  data: z.array(FinanceEntrySchema),
  meta: PaginationMetaSchema,
});

export type FinanceEntryListResponse = z.infer<typeof FinanceEntryListSchema>;

export const FinanceSummarySchema = z.object({
  month: z.string().regex(/^\d{4}-\d{2}$/),
  total_income_cents: z.number().int().nonnegative(),
  total_expense_cents: z.number().int().nonnegative(),
  net_cents: z.number().int(),
  label: FinanceSummaryLabelSchema,
});

export type FinanceSummary = z.infer<typeof FinanceSummarySchema>;
