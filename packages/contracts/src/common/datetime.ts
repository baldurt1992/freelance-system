import { z } from "zod";

/** ISO8601 emitido por Laravel Carbon::toIso8601String() (UTC `Z` u offset). */
export const IsoDateTimeStringSchema = z.string().datetime({ offset: true });

export const NullableIsoDateTimeStringSchema = IsoDateTimeStringSchema.nullable();
