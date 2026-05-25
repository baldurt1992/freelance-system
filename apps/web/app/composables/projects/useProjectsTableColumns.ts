import { h } from "vue";
import { UBadge, UButton, UCheckbox, UDropdownMenu } from "#components";
import type { TableColumn } from "@nuxt/ui";
import type { Row } from "@tanstack/table-core";
import type { Project } from "@freelance/contracts";
import { formatMoney } from "~/utils/formatMoney";

export type ProjectsTableColumnHandlers = {
  onNavigateDetail: (id: number) => void;
  onDelete: (row: Row<Project>) => void;
};

export function useProjectsTableColumns(handlers: ProjectsTableColumnHandlers) {
  function getRowItems(row: Row<Project>) {
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

  const columns: TableColumn<Project>[] = [
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
      accessorKey: "name",
      header: "Proyecto",
      cell: ({ row }) =>
        h(
          "span",
          { class: "font-medium cursor-pointer", onClick: () => handlers.onNavigateDetail(row.original.id) },
          row.original.name,
        ),
    },
    {
      accessorKey: "client_name",
      header: "Cliente",
    },
    {
      accessorKey: "status",
      header: "Estado",
      cell: ({ row }: { row: Row<Project> }) => {
        const color = row.original.is_fully_paid
          ? "success"
          : row.original.status === "active"
            ? "info"
            : row.original.status === "on_hold"
              ? "warning"
              : row.original.status === "completed"
                ? "success"
                : "neutral";
        const label = row.original.is_fully_paid
          ? "Pagado totalmente"
          : row.original.status === "active"
            ? "Activo"
            : row.original.status === "on_hold"
              ? "En pausa"
              : row.original.status === "completed"
                ? "Completado"
                : "Cancelado";
        return h(UBadge, { label, color, variant: "subtle" });
      },
    },
    {
      accessorKey: "balance_due_cents",
      header: "Por cobrar",
      cell: ({ row }: { row: Row<Project> }) => {
        const color = row.original.is_fully_paid ? "success" : "warning";
        const label = row.original.is_fully_paid
          ? "Pagado totalmente"
          : formatMoney(row.original.balance_due_cents, row.original.currency);
        return h(UBadge, { label, color, variant: "subtle" });
      },
    },
    {
      accessorKey: "agreed_total_cents",
      header: "Total",
      cell: ({ row }: { row: Row<Project> }) =>
        h("span", {}, formatMoney(row.original.agreed_total_cents, row.original.currency)),
    },
    {
      accessorKey: "quote_number",
      header: "Cotización",
      cell: ({ row }: { row: Row<Project> }) =>
        h("span", {}, row.original.quote_number || "—"),
    },
    {
      id: "actions",
      header: "",
      cell: ({ row }: { row: Row<Project> }) =>
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
