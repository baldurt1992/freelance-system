import { useApi } from "~/composables/api/useApi";
import type { Client, ClientCreateInput, ClientListResponse, ClientUpdateInput } from "@freelance/contracts";

export function useClientsApi() {
  const { api } = useApi();

  async function list(page = 1, search = ""): Promise<ClientListResponse> {
    const qs = new URLSearchParams({ page: String(page) });
    if (search) qs.append("search", search);
    return api<ClientListResponse>(`/clients?${qs.toString()}`);
  }

  async function find(id: number): Promise<Client> {
    return api<Client>(`/clients/${id}`);
  }

  async function create(input: ClientCreateInput): Promise<Client> {
    return api<Client>("/clients", {
      method: "POST",
      body: input,
    });
  }

  async function update(id: number, input: ClientUpdateInput): Promise<Client> {
    return api<Client>(`/clients/${id}`, {
      method: "PUT",
      body: input,
    });
  }

  async function remove(id: number): Promise<void> {
    return api<void>(`/clients/${id}`, {
      method: "DELETE",
    });
  }

  async function uploadAvatar(id: number, file: File): Promise<Client> {
    const formData = new FormData();
    formData.append("avatar", file);
    return api<Client>(`/clients/${id}/avatar`, {
      method: "POST",
      body: formData,
    });
  }

  return { list, find, create, update, remove, uploadAvatar };
}
