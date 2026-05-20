import { z } from "zod";

export const ApiErrorSchema = z.object({
  message: z.string(),
  errors: z.record(z.array(z.string())).optional(),
});
