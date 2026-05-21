import type { QuoteLineForm } from "~/composables/quotes/useQuoteLines";

export interface QuoteFormState {
  clientId?: number;
  title: string | null;
  notes: string | null;
  validUntil?: string;
  lines: QuoteLineForm[];
}

export function useQuoteForm(initial?: Partial<QuoteFormState>) {
  const clientId = ref<number | undefined>(initial?.clientId);
  const title = ref<string | null>(initial?.title ?? null);
  const notes = ref<string | null>(initial?.notes ?? null);
  const validUntil = ref<string | undefined>(initial?.validUntil);
  const lines = ref<QuoteLineForm[]>(
    initial?.lines && initial.lines.length > 0
      ? initial.lines
      : [{ description: "", quantity: 1, unit_amount: null, sort_order: 0 }],
  );

  function reset() {
    clientId.value = undefined;
    title.value = null;
    notes.value = null;
    validUntil.value = undefined;
    lines.value = [{ description: "", quantity: 1, unit_amount: null, sort_order: 0 }];
  }

  return {
    clientId,
    title,
    notes,
    validUntil,
    lines,
    reset,
  };
}
