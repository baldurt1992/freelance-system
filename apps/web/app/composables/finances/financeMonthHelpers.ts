export interface FinanceMonthParts {
  year: number;
  month: number;
}

export interface FinanceMonthSelectItem {
  label: string;
  value: number;
}

/** Valor por defecto `YYYY-MM` (mes actual en hora local). */
export function defaultFinanceMonth(): string {
  const now = new Date();
  return formatFinanceMonth(now.getFullYear(), now.getMonth() + 1);
}

export function parseFinanceMonth(value: string): FinanceMonthParts | null {
  const parts = value.split("-").map((segment) => parseInt(segment, 10));
  if (parts.length !== 2 || parts.some((part) => Number.isNaN(part))) return null;

  const [year, month] = parts as [number, number];
  if (month < 1 || month > 12) return null;

  return { year, month };
}

export function formatFinanceMonth(year: number, month: number): string {
  return `${String(year).padStart(4, "0")}-${String(month).padStart(2, "0")}`;
}

export function normalizeFinanceMonth(value: string, fallback: string): FinanceMonthParts {
  return parseFinanceMonth(value) ?? parseFinanceMonth(fallback) ?? parseFinanceMonth(defaultFinanceMonth())!;
}

export function buildFinanceYearSelectItems(options?: {
  yearsBefore?: number;
  yearsAfter?: number;
}): FinanceMonthSelectItem[] {
  const currentYear = new Date().getFullYear();
  const from = currentYear - (options?.yearsBefore ?? 10);
  const to = currentYear + (options?.yearsAfter ?? 1);

  const items: FinanceMonthSelectItem[] = [];
  for (let year = to; year >= from; year -= 1) {
    items.push({ label: String(year), value: year });
  }

  return items;
}

export function buildFinanceMonthSelectItems(): FinanceMonthSelectItem[] {
  const formatter = new Intl.DateTimeFormat("es", { month: "long" });

  return Array.from({ length: 12 }, (_, index) => {
    const month = index + 1;
    const label = formatter.format(new Date(2000, index, 1));
    return {
      label: label.charAt(0).toUpperCase() + label.slice(1),
      value: month,
    };
  });
}
