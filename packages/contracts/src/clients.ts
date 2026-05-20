import { z } from "zod";
import { PaginationMetaSchema } from "./common/pagination.js";

export const ClientSchema = z.object({
  id: z.number().int().positive(),
  name: z.string().min(1).max(255),
  email: z.string().email().nullable(),
  phone: z.string().max(50).nullable(),
  tax_id: z.string().max(50).nullable(),
  address: z.string().max(500).nullable(),
  notes: z.string().max(2000).nullable(),
  avatar: z.string().max(2048).nullable(),
  created_at: z.string().datetime(),
  updated_at: z.string().datetime(),
});

export type Client = z.infer<typeof ClientSchema>;

export const ClientCreateSchema = ClientSchema.omit({
  id: true,
  created_at: true,
  updated_at: true,
});

export type ClientCreateInput = z.infer<typeof ClientCreateSchema>;

export const ClientUpdateSchema = ClientCreateSchema.partial();

export type ClientUpdateInput = z.infer<typeof ClientUpdateSchema>;

export const ClientListSchema = z.object({
  data: z.array(ClientSchema),
  meta: PaginationMetaSchema,
});

export type ClientListResponse = z.infer<typeof ClientListSchema>;