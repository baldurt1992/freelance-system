import type { Quote, QuoteCreateInput, QuoteUpdateInput } from "@freelance/contracts";
import type { QuoteLineForm } from "./useQuoteLines";

import type { QuoteFormState } from "./useQuoteForm";

export function hydrateQuoteForm(quote: Quote): Partial<QuoteFormState> {
  return {
    clientId: quote.client_id,
    title: quote.title,
    notes: quote.notes,
    validUntil: quote.valid_until ? quote.valid_until.substring(0, 10) : undefined,
    lines: (quote.lines ?? []).map((line) => ({
      description: line.description,
      quantity: line.quantity,
      unit_amount: line.unit_amount_cents / 100,
      sort_order: line.sort_order,
    })),
  };
}

export function serializeQuotePayload(
  title: string | null,
  notes: string | null,
  validUntil: string | undefined,
  lines: QuoteLineForm[],
): Omit<QuoteCreateInput, "client_id"> {
  return {
    title: title || null,
    notes: notes || null,
    valid_until: validUntil || null,
    lines: lines.map((line) => ({
      description: line.description,
      quantity: line.quantity,
      unit_amount_cents: Math.round((line.unit_amount ?? 0) * 100),
      sort_order: line.sort_order,
    })),
  };
}

export function serializeQuoteUpdatePayload(
  title: string | null,
  notes: string | null,
  validUntil: string | undefined,
  lines: QuoteLineForm[] | undefined,
): QuoteUpdateInput {
  return {
    title: title || null,
    notes: notes || null,
    valid_until: validUntil || null,
    lines: lines?.map((line) => ({
      description: line.description,
      quantity: line.quantity,
      unit_amount_cents: Math.round((line.unit_amount ?? 0) * 100),
      sort_order: line.sort_order,
    })),
  };
}
