import { useApi } from "~/composables/api/useApi";
import { parseApiResponse } from "~/composables/api/parseApiResponse";
import {
  FinanceEntryListSchema,
  FinanceEntrySchema,
  FinanceSummarySchema,
  type FinanceEntry,
  type FinanceEntryCreateInput,
  type FinanceEntryListResponse,
  type FinanceEntryUpdateInput,
  type FinanceSummary,
} from "@freelance/contracts";

export function useFinancesApi() {
  const { api } = useApi();

  async function getSummary(month: string): Promise<FinanceSummary> {
    const data = await api(`/finances/summary?month=${month}`);
    return parseApiResponse(FinanceSummarySchema, data, "finances.getSummary");
  }

  async function listEntries(
    page = 1,
    filters: { month?: string; type?: "income" | "expense" } = {},
  ): Promise<FinanceEntryListResponse> {
    const qs = new URLSearchParams({ page: String(page) });
    if (filters.month) qs.append("month", filters.month);
    if (filters.type) qs.append("type", filters.type);
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

  return { getSummary, listEntries, findEntry, createEntry, updateEntry, removeEntry };
}
