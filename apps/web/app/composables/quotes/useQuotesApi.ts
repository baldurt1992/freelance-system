import { useApi } from "~/composables/api/useApi";
import type {
  Quote,
  QuoteCreateInput,
  QuoteListResponse,
  QuoteStatusTransitionResponse,
  QuoteUpdateInput,
} from "@freelance/contracts";

export function useQuotesApi() {
  const { api } = useApi();

  async function list(page = 1, search = ""): Promise<QuoteListResponse> {
    const qs = new URLSearchParams({ page: String(page) });
    if (search) qs.append("search", search);
    return api<QuoteListResponse>(`/quotes?${qs.toString()}`);
  }

  async function find(id: number): Promise<Quote> {
    return api<Quote>(`/quotes/${id}`);
  }

  async function create(input: QuoteCreateInput): Promise<Quote> {
    return api<Quote>("/quotes", {
      method: "POST",
      body: input,
    });
  }

  async function update(id: number, input: QuoteUpdateInput): Promise<Quote> {
    return api<Quote>(`/quotes/${id}`, {
      method: "PUT",
      body: input,
    });
  }

  async function remove(id: number): Promise<void> {
    return api<void>(`/quotes/${id}`, {
      method: "DELETE",
    });
  }

  async function send(id: number): Promise<QuoteStatusTransitionResponse> {
    return api<QuoteStatusTransitionResponse>(`/quotes/${id}/send`, {
      method: "POST",
    });
  }

  async function accept(id: number): Promise<QuoteStatusTransitionResponse> {
    return api<QuoteStatusTransitionResponse>(`/quotes/${id}/accept`, {
      method: "POST",
    });
  }

  async function reject(id: number): Promise<QuoteStatusTransitionResponse> {
    return api<QuoteStatusTransitionResponse>(`/quotes/${id}/reject`, {
      method: "POST",
    });
  }

  async function downloadPdf(id: number): Promise<Blob> {
    return api<Blob>(`/quotes/${id}/pdf`, {
      responseType: "blob",
    });
  }

  return { list, find, create, update, remove, send, accept, reject, downloadPdf };
}
