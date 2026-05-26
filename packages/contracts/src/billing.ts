import { z } from "zod";
import {
  IsoDateTimeStringSchema,
  NullableIsoDateTimeStringSchema,
} from "./common/datetime.js";
import { ProjectSchema } from "./projects.js";

export const BillingDocumentStatusSchema = z.enum([
  "draft",
  "issued",
  "sent",
  "paid",
]);

export type BillingDocumentStatus = z.infer<typeof BillingDocumentStatusSchema>;

export const BillingDocumentSchema = z.object({
  id: z.number().int().positive(),
  project_id: z.number().int().positive(),
  client_id: z.number().int().positive(),
  number: z.string().min(1),
  status: BillingDocumentStatusSchema,
  project_name: z.string().min(1),
  client_name: z.string().min(1),
  client_email: z.string().email().nullable(),
  client_tax_id: z.string().max(50).nullable(),
  client_address: z.string().max(500).nullable(),
  currency: z.string().min(3).max(3),
  agreed_total_cents: z.number().int().nonnegative(),
  paid_total_cents: z.number().int().nonnegative(),
  balance_due_cents: z.number().int().nonnegative(),
  pdf_path: z.string().nullable(),
  issued_at: NullableIsoDateTimeStringSchema,
  sent_at: NullableIsoDateTimeStringSchema,
  created_at: IsoDateTimeStringSchema,
  updated_at: IsoDateTimeStringSchema,
});

export type BillingDocument = z.infer<typeof BillingDocumentSchema>;

export const BillingDocumentListSchema = z.object({
  data: z.array(BillingDocumentSchema),
});

export type BillingDocumentListResponse = z.infer<typeof BillingDocumentListSchema>;

export const CompleteProjectResponseSchema = z.object({
  project: ProjectSchema,
  billing_document: BillingDocumentSchema,
});

export type CompleteProjectResponse = z.infer<typeof CompleteProjectResponseSchema>;
