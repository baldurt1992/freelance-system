import { useApi } from "~/composables/api/useApi";
import type {
  Project,
  ProjectCreateInput,
  ProjectListResponse,
  ProjectPayment,
  RegisterPartialPaymentInput,
  MarkProjectPaidInput,
  MarkProjectPaidResponse,
  ProjectUpdateInput,
  ConvertQuoteToProjectResponse,
  CompleteProjectResponse,
} from "@freelance/contracts";

export function useProjectsApi() {
  const { api } = useApi();

  async function list(page = 1, search = ""): Promise<ProjectListResponse> {
    const qs = new URLSearchParams({ page: String(page) });
    if (search) qs.append("search", search);
    return api<ProjectListResponse>(`/projects?${qs.toString()}`);
  }

  async function find(id: number): Promise<Project> {
    return api<Project>(`/projects/${id}`);
  }

  async function create(input: ProjectCreateInput): Promise<Project> {
    return api<Project>("/projects", {
      method: "POST",
      body: input,
    });
  }

  async function update(id: number, input: ProjectUpdateInput): Promise<Project> {
    return api<Project>(`/projects/${id}`, {
      method: "PUT",
      body: input,
    });
  }

  async function remove(id: number): Promise<void> {
    return api<void>(`/projects/${id}`, {
      method: "DELETE",
    });
  }

  async function convertQuoteToProject(quoteId: number): Promise<ConvertQuoteToProjectResponse> {
    return api<ConvertQuoteToProjectResponse>(`/quotes/${quoteId}/convert-to-project`, {
      method: "POST",
    });
  }

  async function registerPayment(
    projectId: number,
    input: RegisterPartialPaymentInput,
  ): Promise<{ project: Project; payment: ProjectPayment }> {
    return api(`/projects/${projectId}/payments`, {
      method: "POST",
      body: input,
    });
  }

  async function markPaid(
    projectId: number,
    input: MarkProjectPaidInput,
  ): Promise<MarkProjectPaidResponse> {
    return api<MarkProjectPaidResponse>(`/projects/${projectId}/mark-paid`, {
      method: "POST",
      body: input,
    });
  }

  async function getPayments(projectId: number): Promise<{ data: ProjectPayment[] }> {
    return api<{ data: ProjectPayment[] }>(`/projects/${projectId}/payments`);
  }

  async function complete(projectId: number): Promise<CompleteProjectResponse> {
    return api<CompleteProjectResponse>(`/projects/${projectId}/complete`, {
      method: "POST",
    });
  }

  return { list, find, create, update, remove, convertQuoteToProject, registerPayment, markPaid, getPayments, complete };
}
