import type { TableColumn } from "@nuxt/ui";
import type { FinanceEntry } from "@freelance/contracts";

export const financesTabItems = [
  { value: "summary", label: "Resumen" },
  { value: "income", label: "Ingresos" },
  { value: "expense", label: "Gastos" },
] as const;

export type FinancesTab = (typeof financesTabItems)[number]["value"];

/**
 * Columnas de la tabla de movimientos financieros (presentación; sin llamadas API).
 */
export function useFinancesTableColumns() {
  const columns: TableColumn<FinanceEntry>[] = [
    { accessorKey: "occurred_on", header: "Fecha" },
    { accessorKey: "description", header: "Descripción" },
    { accessorKey: "category", header: "Categoría" },
    { accessorKey: "amount_cents", header: "Monto" },
    { accessorKey: "type", header: "Tipo" },
    { accessorKey: "is_manual", header: "Origen" },
    { id: "actions", header: "" },
  ];

  function entriesTableTitle(tab: FinancesTab): string {
    if (tab === "summary") return "Movimientos del mes";
    if (tab === "income") return "Ingresos";
    return "Gastos";
  }

  return { columns, entriesTableTitle };
}
