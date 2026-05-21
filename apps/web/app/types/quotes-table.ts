import type { Row } from "@tanstack/table-core";
import type { Quote } from "@freelance/contracts";

export type QuotesTableColumnApi = {
  id: string;
  getCanHide: () => boolean;
  getIsVisible: () => boolean;
};

export type QuotesTableApi = {
  getFilteredSelectedRowModel: () => { rows: Array<Row<Quote>> };
  getAllColumns: () => QuotesTableColumnApi[];
  getColumn: (
    id: string,
  ) => { toggleVisibility: (visible: boolean) => void } | undefined;
};

export type QuotesTableExpose = {
  tableApi?: QuotesTableApi;
};
