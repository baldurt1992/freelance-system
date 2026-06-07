import type { FinanceEntry } from "@freelance/contracts";
import type { Row } from "@tanstack/table-core";

export type FinancesTableColumnApi = {
  id: string;
  getCanHide: () => boolean;
  getIsVisible: () => boolean;
};

export type FinancesTableApi = {
  getAllColumns: () => FinancesTableColumnApi[];
  getColumn: (
    id: string,
  ) => { toggleVisibility: (visible: boolean) => void } | undefined;
  getFilteredSelectedRowModel?: () => { rows: Array<Row<FinanceEntry>> };
};

export type FinancesTableExpose = {
  tableApi?: FinancesTableApi;
};
