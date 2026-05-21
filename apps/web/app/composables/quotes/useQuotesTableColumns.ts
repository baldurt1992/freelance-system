import { h } from "vue";
import { UBadge, UButton, UCheckbox, UDropdownMenu } from "#components";
import type { TableColumn } from "@nuxt/ui";
import type { Row } from "@tanstack/table-core";
import type { Quote } from "@freelance/contracts";
import { formatMoney } from "~/utils/formatMoney";
import { getQuoteStatusLabel, getQuoteStatusColor } from "~/utils/quoteStatus";

export type QuotesTableColumnHandlers = {
  onNavigateDetail: (id: number) => void;
  onDelete: (row: Row<Quote>) => void;
};

export function useQuotesTableColumns(handlers: QuotesTableColumnHandlers) {
  function getRowItems(row: Row<Quote>) {
    return [
      { type: "label" as const, label: "Acciones" },
      {
        label: "Ver detalle",
        icon: "i-lucide-list",
        onSelect: () => handlers.onNavigateDetail(row.original.id),
      },
      { type: "separator" as const },
      {
        label: "Eliminar",
        icon: "i-lucide-trash",
        color: "error" as const,
        onSelect: () => handlers.onDelete(row),
      },
    ];
  }

  const columns: TableColumn<Quote>[] = [
    {
      id: "select",
      header: ({ table }) =>
        h(UCheckbox, {
          modelValue: table.getIsSomePageRowsSelected()
            ? "indeterminate"
            : table.getIsAllPageRowsSelected(),
          "onUpdate:modelValue": (value: unknown) =>
            table.toggleAllPageRowsSelected(!!value),
          ariaLabel: "Seleccionar todos",
        }),
      cell: ({ row }) =>
        h(UCheckbox, {
          modelValue: row.getIsSelected(),
          "onUpdate:modelValue": (value: unknown) =>
            row.toggleSelected(!!value),
          ariaLabel: "Seleccionar fila",
        }),
    },
    {
      accessorKey: "number",
      header: "Número",
    },
    {
      accessorKey: "client_name",
      header: "Cliente",
    },
    {
      accessorKey: "status",
      header: "Estado",
      cell: ({ row }: { row: Row<Quote> }) =>
        h(UBadge, {
          label: getQuoteStatusLabel(row.original.status),
          color: getQuoteStatusColor(row.original.status),
          variant: "subtle",
        }),
    },
    {
      accessorKey: "total_cents",
      header: "Total",
      cell: ({ row }: { row: Row<Quote> }) =>
        h("span", {}, formatMoney(row.original.total_cents, row.original.currency)),
    },
    {
      accessorKey: "created_at",
      header: "Fecha",
      cell: ({ row }: { row: Row<Quote> }) => {
        const date = row.original.created_at ? new Date(row.original.created_at) : null;
        return h("span", {}, date ? date.toLocaleDateString("es-CO") : "—");
      },
    },
    {
      id: "actions",
      header: "",
      cell: ({ row }: { row: Row<Quote> }) =>
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

  return { columns };
}
