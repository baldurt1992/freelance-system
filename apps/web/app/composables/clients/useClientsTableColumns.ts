import { h } from "vue";
import { UAvatar, UButton, UCheckbox, UDropdownMenu } from "#components";
import type { TableColumn } from "@nuxt/ui";
import type { Row } from "@tanstack/table-core";
import type { Client } from "@freelance/contracts";

export type ClientsTableColumnHandlers = {
  onNavigateDetail: (id: number) => void;
  onEdit: (id: number) => void;
  onDelete: (row: Row<Client>) => void;
};

/**
 * Columnas de la tabla de clientes (presentación; sin llamadas API).
 */
export function useClientsTableColumns(handlers: ClientsTableColumnHandlers) {
  function getRowItems(row: Row<Client>) {
    return [
      { type: "label" as const, label: "Acciones" },
      {
        label: "Ver detalle",
        icon: "i-lucide-list",
        onSelect: () => handlers.onNavigateDetail(row.original.id),
      },
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
    ];
  }

  const columns: TableColumn<Client>[] = [
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
      header: "Nombre",
      cell: ({ row }) =>
        h(
          "div",
          {
            class: "flex items-center gap-3 cursor-pointer",
            onClick: () => handlers.onNavigateDetail(row.original.id),
          },
          [
            h(UAvatar, {
              src: row.original.avatar || undefined,
              alt: row.original.name,
              size: "lg",
            }),
            h("div", undefined, [
              h(
                "p",
                { class: "font-medium text-highlighted" },
                row.original.name,
              ),
              h(
                "p",
                { class: "text-sm text-muted" },
                row.original.email || "—",
              ),
            ]),
          ],
        ),
    },
    {
      accessorKey: "email",
      header: ({ column }) => {
        const isSorted = column.getIsSorted();
        return h(UButton, {
          color: "neutral",
          variant: "ghost",
          label: "Email",
          icon: isSorted
            ? isSorted === "asc"
              ? "i-lucide-arrow-up-narrow-wide"
              : "i-lucide-arrow-down-wide-narrow"
            : "i-lucide-arrow-up-down",
          class: "-mx-2.5",
          onClick: () => column.toggleSorting(column.getIsSorted() === "asc"),
        });
      },
      cell: ({ row }) => row.original.email || "—",
    },
    {
      accessorKey: "phone",
      header: "Teléfono",
      cell: ({ row }) => row.original.phone || "—",
    },
    {
      accessorKey: "tax_id",
      header: "NIT / CC",
      cell: ({ row }) => row.original.tax_id || "—",
    },
    {
      id: "actions",
      header: "",
      cell: ({ row }) =>
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
