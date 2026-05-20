import { z } from "zod";

export const MoneyCentsSchema = z.number().int().nonnegative();
export const CurrencyCodeSchema = z.string().min(3).max(3);
