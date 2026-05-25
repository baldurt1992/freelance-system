import { z } from "zod";
import { PaginationMetaSchema } from "./common/pagination.js";

export const ProjectTypeSchema = z.enum(["freelance", "fixed", "retainer"]);

export type ProjectType = z.infer<typeof ProjectTypeSchema>;

export const ProjectStatusSchema = z.enum([
  "active",
  "on_hold",
  "completed",
  "cancelled",
]);

export type ProjectStatus = z.infer<typeof ProjectStatusSchema>;

export const ProjectSchema = z.object({
  id: z.number().int().positive(),
  client_id: z.number().int().positive(),
  quote_id: z.number().int().positive().nullable(),
  name: z.string().min(1).max(255),
  notes: z.string().nullable(),
  type: ProjectTypeSchema,
  status: ProjectStatusSchema,
  quote_number: z.string().min(1).nullable(),
  client_name: z.string().min(1),
  client_email: z.string().email().nullable(),
  client_tax_id: z.string().max(50).nullable(),
  client_address: z.string().max(500).nullable(),
  currency: z.string().min(3).max(3),
  agreed_total_cents: z.number().int().nonnegative(),
  paid_total_cents: z.number().int().nonnegative(),
  balance_due_cents: z.number().int().nonnegative(),
  is_fully_paid: z.boolean(),
  started_at: z.string().regex(/^\d{4}-\d{2}-\d{2}$/).nullable(),
  completed_at: z.string().regex(/^\d{4}-\d{2}-\d{2}$/).nullable(),
  created_at: z.string().datetime(),
  updated_at: z.string().datetime(),
});

export type Project = z.infer<typeof ProjectSchema>;

export const ProjectCreateSchema = z.object({
  client_id: z.number().int().positive(),
  name: z.string().min(1).max(255),
  notes: z.string().nullable().optional(),
  type: ProjectTypeSchema,
  agreed_total_cents: z.number().int().positive(),
  started_at: z.string().regex(/^\d{4}-\d{2}-\d{2}$/).nullable().optional(),
});

export type ProjectCreateInput = z.infer<typeof ProjectCreateSchema>;

export const ProjectUpdateSchema = z.object({
  name: z.string().min(1).max(255).optional(),
  notes: z.string().nullable().optional(),
  type: ProjectTypeSchema.optional(),
  status: ProjectStatusSchema.optional(),
  started_at: z.string().regex(/^\d{4}-\d{2}-\d{2}$/).nullable().optional(),
  completed_at: z.string().regex(/^\d{4}-\d{2}-\d{2}$/).nullable().optional(),
});

export type ProjectUpdateInput = z.infer<typeof ProjectUpdateSchema>;

export const ProjectListSchema = z.object({
  data: z.array(ProjectSchema),
  meta: PaginationMetaSchema,
});

export type ProjectListResponse = z.infer<typeof ProjectListSchema>;

export const ConvertQuoteToProjectResponseSchema = ProjectSchema;

export type ConvertQuoteToProjectResponse = z.infer<
  typeof ConvertQuoteToProjectResponseSchema
>;
