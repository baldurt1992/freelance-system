import { useApi } from "~/composables/api/useApi";
import type {
  FinanceEntry,
  FinanceEntryCreateInput,
  FinanceEntryListResponse,
  FinanceEntryUpdateInput,
  FinanceSummary,
} from "@freelance/contracts";

export function useFinancesApi() {
  const { api } = useApi();

  async function getSummary(month: string): Promise<FinanceSummary> {
    return api<FinanceSummary>(`/finances/summary?month=${month}`);
  }

  async function listEntries(
    page = 1,
    filters: { month?: string; type?: "income" | "expense" } = {},
  ): Promise<FinanceEntryListResponse> {
    const qs = new URLSearchParams({ page: String(page) });
    if (filters.month) qs.append("month", filters.month);
    if (filters.type) qs.append("type", filters.type);
    return api<FinanceEntryListResponse>(`/finances/entries?${qs.toString()}`);
  }

  async function findEntry(id: number): Promise<FinanceEntry> {
    return api<FinanceEntry>(`/finances/entries/${id}`);
  }

  async function createEntry(input: FinanceEntryCreateInput): Promise<FinanceEntry> {
    return api<FinanceEntry>("/finances/entries", {
      method: "POST",
      body: input,
    });
  }

  async function updateEntry(id: number, input: FinanceEntryUpdateInput): Promise<FinanceEntry> {
    return api<FinanceEntry>(`/finances/entries/${id}`, {
      method: "PATCH",
      body: input,
    });
  }

  async function removeEntry(id: number): Promise<void> {
    return api<void>(`/finances/entries/${id}`, {
      method: "DELETE",
    });
  }

  return { getSummary, listEntries, findEntry, createEntry, updateEntry, removeEntry };
}
