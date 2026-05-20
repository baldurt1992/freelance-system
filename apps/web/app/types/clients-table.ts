import type { Row } from "@tanstack/table-core";
import type { Client } from "@freelance/contracts";

/** Minimal UTable expose used by clients list page. */
export type ClientsTableColumnApi = {
  id: string;
  getCanHide: () => boolean;
  getIsVisible: () => boolean;
};

export type ClientsTableApi = {
  getFilteredSelectedRowModel: () => { rows: Array<Row<Client>> };
  getAllColumns: () => ClientsTableColumnApi[];
  getColumn: (
    id: string,
  ) => { toggleVisibility: (visible: boolean) => void } | undefined;
};

export type ClientsTableExpose = {
  tableApi?: ClientsTableApi;
};
