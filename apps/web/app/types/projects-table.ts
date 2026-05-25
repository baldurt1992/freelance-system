import type { Row } from "@tanstack/table-core";
import type { Project } from "@freelance/contracts";

export type ProjectsTableColumnApi = {
  id: string;
  getCanHide: () => boolean;
  getIsVisible: () => boolean;
};

export type ProjectsTableApi = {
  getFilteredSelectedRowModel: () => { rows: Array<Row<Project>> };
  getAllColumns: () => ProjectsTableColumnApi[];
  getColumn: (
    id: string,
  ) => { toggleVisibility: (visible: boolean) => void } | undefined;
};

export type ProjectsTableExpose = {
  tableApi?: ProjectsTableApi;
};
