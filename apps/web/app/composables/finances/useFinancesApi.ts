import { useApi } from "~/composables/api/useApi";
import { parseApiResponse } from "~/composables/api/parseApiResponse";
import {
  FinanceCategoryListSchema,
  FinanceCategorySchema,
  FinanceEntryListSchema,
  FinanceEntrySchema,
  FinanceSummarySchema,
  type FinanceCategory,
  type FinanceCategoryCreateInput,
  type FinanceCategoryListResponse,
  type FinanceCategoryUpdateInput,
  type FinanceEntry,
  type FinanceEntryCreateInput,
  type FinanceEntryListResponse,
  type FinanceEntryType,
  type FinanceEntryUpdateInput,
  type FinanceSummary,
} from "@freelance/contracts";

export function useFinancesApi() {
  const { api } = useApi();

  async function getSummary(month: string): Promise<FinanceSummary> {
    const data = await api(`/finances/summary?month=${month}`);
    return parseApiResponse(FinanceSummarySchema, data, "finances.getSummary");
  }

  async function listCategories(type?: FinanceEntryType): Promise<FinanceCategoryListResponse> {
    const qs = new URLSearchParams();
    if (type) qs.append("type", type);

    const suffix = qs.size > 0 ? `?${qs.toString()}` : "";
    const data = await api(`/finances/categories${suffix}`);
    return parseApiResponse(FinanceCategoryListSchema, data, "finances.listCategories");
  }

  async function createCategory(input: FinanceCategoryCreateInput): Promise<FinanceCategory> {
    const data = await api("/finances/categories", {
      method: "POST",
      body: input,
    });

    return parseApiResponse(FinanceCategorySchema, data, "finances.createCategory");
  }

  async function updateCategory(id: number, input: FinanceCategoryUpdateInput): Promise<FinanceCategory> {
    const data = await api(`/finances/categories/${id}`, {
      method: "PATCH",
      body: input,
    });

    return parseApiResponse(FinanceCategorySchema, data, "finances.updateCategory");
  }

  async function removeCategory(id: number): Promise<void> {
    return api<void>(`/finances/categories/${id}`, {
      method: "DELETE",
    });
  }

  async function listEntries(
    page = 1,
    filters: { month?: string; type?: "income" | "expense"; search?: string } = {},
  ): Promise<FinanceEntryListResponse> {
    const qs = new URLSearchParams({ page: String(page) });
    if (filters.month) qs.append("month", filters.month);
    if (filters.type) qs.append("type", filters.type);
    if (filters.search) qs.append("search", filters.search);
    const data = await api(`/finances/entries?${qs.toString()}`);
    return parseApiResponse(FinanceEntryListSchema, data, "finances.listEntries");
  }

  async function findEntry(id: number): Promise<FinanceEntry> {
    const data = await api(`/finances/entries/${id}`);
    return parseApiResponse(FinanceEntrySchema, data, "finances.findEntry");
  }

  async function createEntry(input: FinanceEntryCreateInput): Promise<FinanceEntry> {
    const data = await api("/finances/entries", {
      method: "POST",
      body: input,
    });
    return parseApiResponse(FinanceEntrySchema, data, "finances.createEntry");
  }

  async function updateEntry(id: number, input: FinanceEntryUpdateInput): Promise<FinanceEntry> {
    const data = await api(`/finances/entries/${id}`, {
      method: "PATCH",
      body: input,
    });
    return parseApiResponse(FinanceEntrySchema, data, "finances.updateEntry");
  }

  async function removeEntry(id: number): Promise<void> {
    return api<void>(`/finances/entries/${id}`, {
      method: "DELETE",
    });
  }

  return {
    getSummary,
    listCategories,
    createCategory,
    updateCategory,
    removeCategory,
    listEntries,
    findEntry,
    createEntry,
    updateEntry,
    removeEntry,
  };
}
