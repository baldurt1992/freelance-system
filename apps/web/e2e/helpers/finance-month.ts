export interface IsolatedFinanceMonth {
  year: number;
  month: number;
  monthLabel: string;
  isoMonth: string;
  otherIsoMonth: string;
  otherMonthLabel: string;
}

function formatSpanishMonthLabel(month: number): string {
  const formatter = new Intl.DateTimeFormat("es", { month: "long" });
  const label = formatter.format(new Date(2000, month - 1, 1));
  return label.charAt(0).toUpperCase() + label.slice(1);
}

function formatIsoMonth(year: number, month: number): string {
  return `${String(year).padStart(4, "0")}-${String(month).padStart(2, "0")}`;
}

/**
 * Mes aislado por corrida: año siguiente + mes derivado del timestamp.
 * Evita colisión con movimientos históricos del tenant de dev en meses pasados/actuales.
 */
export function buildIsolatedFinanceMonth(seed: number = Date.now()): IsolatedFinanceMonth {
  const currentYear = new Date().getFullYear();
  const year = currentYear + 1;
  const month = (seed % 12) + 1;
  const otherMonth = month === 12 ? 1 : month + 1;

  return {
    year,
    month,
    monthLabel: formatSpanishMonthLabel(month),
    isoMonth: formatIsoMonth(year, month),
    otherIsoMonth: formatIsoMonth(year, otherMonth),
    otherMonthLabel: formatSpanishMonthLabel(otherMonth),
  };
}
