export interface QuoteLineForm {
  description: string;
  quantity: number;
  unit_amount: number | null;
  sort_order: number;
}

export function useQuoteLines(initial: QuoteLineForm[] = []) {
  const lines = ref<QuoteLineForm[]>(initial.length > 0 ? initial : [emptyLine(0)]);

  function emptyLine(order: number): QuoteLineForm {
    return {
      description: "",
      quantity: 1,
      unit_amount: null,
      sort_order: order,
    };
  }

  function addLine() {
    lines.value.push(emptyLine(lines.value.length));
    reindex();
  }

  function removeLine(index: number) {
    lines.value.splice(index, 1);
    reindex();
  }

  function reindex() {
    lines.value.forEach((line, idx) => {
      line.sort_order = idx;
    });
  }

  function reset() {
    lines.value = [emptyLine(0)];
  }

  const subtotalCents = computed(() => {
    return lines.value.reduce((sum, line) => {
      return sum + Math.round(line.quantity * (line.unit_amount ?? 0) * 100);
    }, 0);
  });

  return {
    lines,
    addLine,
    removeLine,
    reset,
    subtotalCents,
  };
}
