import { useApi } from "~/composables/api/useApi";
import { parseApiResponse } from "~/composables/api/parseApiResponse";
import {
  QuoteListSchema,
  QuoteSchema,
  QuoteStatusTransitionSchema,
  type Quote,
  type QuoteCreateInput,
  type QuoteListResponse,
  type QuoteStatusTransitionResponse,
  type QuoteUpdateInput,
} from "@freelance/contracts";

export function useQuotesApi() {
  const { api } = useApi();

  async function list(page = 1, search = ""): Promise<QuoteListResponse> {
    const qs = new URLSearchParams({ page: String(page) });
    if (search) qs.append("search", search);
    const data = await api(`/quotes?${qs.toString()}`);
    return parseApiResponse(QuoteListSchema, data, "quotes.list");
  }

  async function find(id: number): Promise<Quote> {
    const data = await api(`/quotes/${id}`);
    return parseApiResponse(QuoteSchema, data, "quotes.find");
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
    const data = await api(`/quotes/${id}/send`, {
      method: "POST",
    });
    return parseApiResponse(QuoteStatusTransitionSchema, data, "quotes.send");
  }

  async function accept(id: number): Promise<QuoteStatusTransitionResponse> {
    const data = await api(`/quotes/${id}/accept`, {
      method: "POST",
    });
    return parseApiResponse(QuoteStatusTransitionSchema, data, "quotes.accept");
  }

  async function reject(id: number): Promise<QuoteStatusTransitionResponse> {
    const data = await api(`/quotes/${id}/reject`, {
      method: "POST",
    });
    return parseApiResponse(QuoteStatusTransitionSchema, data, "quotes.reject");
  }

  async function downloadPdf(id: number): Promise<Blob> {
    return api<Blob>(`/quotes/${id}/pdf`, {
      responseType: "blob",
    });
  }

  return { list, find, create, update, remove, send, accept, reject, downloadPdf };
}
