import { z } from "zod";
import {
  IsoDateTimeStringSchema,
  NullableIsoDateTimeStringSchema,
} from "./common/datetime.js";
import { PaginationMetaSchema } from "./common/pagination.js";

export const QuoteStatusSchema = z.enum([
  "draft",
  "sent",
  "accepted",
  "rejected",
  "converted",
]);

export type QuoteStatus = z.infer<typeof QuoteStatusSchema>;

export const QuoteLineSchema = z.object({
  id: z.number().int().positive(),
  quote_id: z.number().int().positive(),
  description: z.string().min(1),
  quantity: z.number().positive(),
  unit_amount_cents: z.number().int().nonnegative(),
  tax_rate: z.number().min(0).max(100).default(0),
  tax_cents: z.number().int().nonnegative(),
  line_total_cents: z.number().int().nonnegative(),
  sort_order: z.number().int().nonnegative(),
});

export type QuoteLine = z.infer<typeof QuoteLineSchema>;

export const QuoteSchema = z.object({
  id: z.number().int().positive(),
  client_id: z.number().int().positive(),
  number: z.string().min(1),
  status: QuoteStatusSchema,
  title: z.string().max(255).nullable(),
  notes: z.string().nullable(),
  client_name: z.string().min(1),
  client_email: z.string().email().nullable(),
  client_tax_id: z.string().max(50).nullable(),
  client_address: z.string().max(500).nullable(),
  currency: z.string().min(3).max(3),
  subtotal_cents: z.number().int().nonnegative(),
  tax_cents: z.number().int().nonnegative(),
  total_cents: z.number().int().nonnegative(),
  tax_rate: z.number().min(0).max(100).default(0),
  valid_until: z.string().regex(/^\d{4}-\d{2}-\d{2}$/).nullable(),
  sent_at: NullableIsoDateTimeStringSchema,
  accepted_at: NullableIsoDateTimeStringSchema,
  rejected_at: NullableIsoDateTimeStringSchema,
  created_at: IsoDateTimeStringSchema,
  updated_at: IsoDateTimeStringSchema,
  lines: z.array(QuoteLineSchema).optional(),
});

export type Quote = z.infer<typeof QuoteSchema>;

export const QuoteLineInputSchema = z.object({
  description: z.string().min(1),
  quantity: z.number().positive(),
  unit_amount_cents: z.number().int().nonnegative(),
  sort_order: z.number().int().nonnegative(),
});

export type QuoteLineInput = z.infer<typeof QuoteLineInputSchema>;

export const QuoteCreateSchema = z.object({
  client_id: z.number().int().positive(),
  title: z.string().max(255).nullable().optional(),
  notes: z.string().nullable().optional(),
  valid_until: z.string().regex(/^\d{4}-\d{2}-\d{2}$/).nullable().optional(),
  lines: z.array(QuoteLineInputSchema).min(1, "Debe incluir al menos una línea"),
});

export type QuoteCreateInput = z.infer<typeof QuoteCreateSchema>;

export const QuoteUpdateSchema = z.object({
  title: z.string().max(255).nullable().optional(),
  notes: z.string().nullable().optional(),
  valid_until: z.string().regex(/^\d{4}-\d{2}-\d{2}$/).nullable().optional(),
  lines: z.array(QuoteLineInputSchema).min(1, "Debe incluir al menos una línea").optional(),
});

export type QuoteUpdateInput = z.infer<typeof QuoteUpdateSchema>;

export const QuoteListSchema = z.object({
  data: z.array(QuoteSchema),
  meta: PaginationMetaSchema,
});

export type QuoteListResponse = z.infer<typeof QuoteListSchema>;

export const QuoteStatusTransitionSchema = z.object({
  id: z.number().int().positive(),
  status: QuoteStatusSchema,
  sent_at: NullableIsoDateTimeStringSchema,
  accepted_at: NullableIsoDateTimeStringSchema,
  rejected_at: NullableIsoDateTimeStringSchema,
});

export type QuoteStatusTransitionResponse = z.infer<typeof QuoteStatusTransitionSchema>;
