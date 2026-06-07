import { h } from "vue";
import { UButton, UDropdownMenu } from "#components";
import type { DropdownMenuItem, TableColumn } from "@nuxt/ui";
import type { Row } from "@tanstack/table-core";
import type { FinanceEntry } from "@freelance/contracts";

export const financesTabItems = [
  { value: "summary", label: "Resumen" },
  { value: "income", label: "Ingresos" },
  { value: "expense", label: "Gastos" },
] as const;

export type FinancesTab = (typeof financesTabItems)[number]["value"];

export type FinancesTableColumnHandlers = {
  onNavigateDetail: (id: number) => void;
  onEdit: (id: number) => void;
  onDelete: (row: Row<FinanceEntry>) => void;
};

/**
 * Columnas de la tabla de movimientos financieros (presentación; sin llamadas API).
 */
export function useFinancesTableColumns(handlers: FinancesTableColumnHandlers) {
  function getRowItems(row: Row<FinanceEntry>): DropdownMenuItem[] {
    const items: DropdownMenuItem[] = [
      { type: "label" as const, label: "Acciones" },
      {
        label: "Ver detalle",
        icon: "i-lucide-list",
        onSelect: () => handlers.onNavigateDetail(row.original.id),
      },
    ];

    if (row.original.is_manual) {
      items.push(
        {
          label: "Editar",
          icon: "i-lucide-pencil",
          onSelect: () => handlers.onEdit(row.original.id),
        },
        { type: "separator" as const },
        {
          label: "Eliminar",
          icon: "i-lucide-trash",
          color: "error" as const,
          onSelect: () => handlers.onDelete(row),
        },
      );
    }

    return items;
  }

  const columns: TableColumn<FinanceEntry>[] = [
    { accessorKey: "occurred_on", header: "Fecha" },
    {
      accessorKey: "name",
      header: "Nombre",
      cell: ({ row }) =>
        h(
          "span",
          { class: "font-medium cursor-pointer", onClick: () => handlers.onNavigateDetail(row.original.id) },
          row.original.name,
        ),
    },
    { accessorKey: "category_name", header: "Categoría" },
    { accessorKey: "amount_cents", header: "Monto" },
    { accessorKey: "type", header: "Tipo" },
    { accessorKey: "is_manual", header: "Origen" },
    {
      id: "actions",
      header: "",
      cell: ({ row }: { row: Row<FinanceEntry> }) =>
        h(
          "div",
          { class: "text-right" },
          h(
            UDropdownMenu,
            {
              content: { align: "end" },
              items: getRowItems(row),
            },
            () =>
              h(UButton, {
                icon: "i-lucide-ellipsis-vertical",
                color: "neutral",
                variant: "ghost",
                class: "ml-auto",
              }),
          ),
        ),
    },
  ];

  function entriesTableTitle(tab: FinancesTab): string {
    if (tab === "summary") return "Movimientos del mes";
    if (tab === "income") return "Ingresos";
    return "Gastos";
  }

  return { columns, entriesTableTitle };
}
