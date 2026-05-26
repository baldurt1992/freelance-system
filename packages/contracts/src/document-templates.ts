import { z } from "zod";

export const DocumentTemplateTypeSchema = z.enum(["quote", "billing"]);

export type DocumentTemplateType = z.infer<typeof DocumentTemplateTypeSchema>;

export const DocumentTemplateSchema = z.object({
  id: z.number().int().positive(),
  type: DocumentTemplateTypeSchema,
  client_id: z.number().int().positive().nullable(),
  name: z.string().min(1).max(255),
  html_body: z.string().min(1),
  is_default: z.boolean(),
  created_at: z.string().datetime(),
  updated_at: z.string().datetime(),
});

export type DocumentTemplate = z.infer<typeof DocumentTemplateSchema>;

export const DocumentTemplateListSchema = z.object({
  data: z.array(DocumentTemplateSchema),
});

export type DocumentTemplateListResponse = z.infer<typeof DocumentTemplateListSchema>;

export const DocumentTemplateUpdateSchema = z.object({
  name: z.string().min(1).max(255).optional(),
  html_body: z.string().min(1).optional(),
  is_default: z.boolean().optional(),
});

export type DocumentTemplateUpdateInput = z.infer<typeof DocumentTemplateUpdateSchema>;

export const DocumentTemplatePreviewSchema = z.object({
  type: DocumentTemplateTypeSchema,
  html_body: z.string().min(1).optional(),
  template_id: z.number().int().positive().optional(),
}).refine((value) => value.html_body !== undefined || value.template_id !== undefined, {
  message: "Debe enviar html_body o template_id.",
  path: ["html_body"],
});

export type DocumentTemplatePreviewInput = z.infer<typeof DocumentTemplatePreviewSchema>;
