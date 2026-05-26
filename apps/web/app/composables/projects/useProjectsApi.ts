import { useApi } from "~/composables/api/useApi";
import { parseApiResponse } from "~/composables/api/parseApiResponse";
import {
  CompleteProjectResponseSchema,
  ConvertQuoteToProjectResponseSchema,
  MarkProjectPaidResponseSchema,
  ProjectListSchema,
  ProjectPaymentListSchema,
  ProjectSchema,
  RegisterPaymentResponseSchema,
  type CompleteProjectResponse,
  type ConvertQuoteToProjectResponse,
  type MarkProjectPaidInput,
  type MarkProjectPaidResponse,
  type Project,
  type ProjectCreateInput,
  type ProjectListResponse,
  type ProjectPaymentListResponse,
  type ProjectUpdateInput,
  type RegisterPartialPaymentInput,
  type RegisterPaymentResponse,
} from "@freelance/contracts";

export function useProjectsApi() {
  const { api } = useApi();

  async function list(page = 1, search = ""): Promise<ProjectListResponse> {
    const qs = new URLSearchParams({ page: String(page) });
    if (search) qs.append("search", search);
    const data = await api(`/projects?${qs.toString()}`);
    return parseApiResponse(ProjectListSchema, data, "projects.list");
  }

  async function find(id: number): Promise<Project> {
    const data = await api(`/projects/${id}`);
    return parseApiResponse(ProjectSchema, data, "projects.find");
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
    const data = await api(`/quotes/${quoteId}/convert-to-project`, {
      method: "POST",
    });
    return parseApiResponse(
      ConvertQuoteToProjectResponseSchema,
      data,
      "projects.convertQuoteToProject",
    );
  }

  async function registerPayment(
    projectId: number,
    input: RegisterPartialPaymentInput,
  ): Promise<RegisterPaymentResponse> {
    const data = await api(`/projects/${projectId}/payments`, {
      method: "POST",
      body: input,
    });
    return parseApiResponse(
      RegisterPaymentResponseSchema,
      data,
      "projects.registerPayment",
    );
  }

  async function markPaid(
    projectId: number,
    input: MarkProjectPaidInput,
  ): Promise<MarkProjectPaidResponse> {
    const data = await api(`/projects/${projectId}/mark-paid`, {
      method: "POST",
      body: input,
    });
    return parseApiResponse(MarkProjectPaidResponseSchema, data, "projects.markPaid");
  }

  async function getPayments(projectId: number): Promise<ProjectPaymentListResponse> {
    const data = await api(`/projects/${projectId}/payments`);
    return parseApiResponse(ProjectPaymentListSchema, data, "projects.getPayments");
  }

  async function complete(projectId: number): Promise<CompleteProjectResponse> {
    const data = await api(`/projects/${projectId}/complete`, {
      method: "POST",
    });
    return parseApiResponse(CompleteProjectResponseSchema, data, "projects.complete");
  }

  return { list, find, create, update, remove, convertQuoteToProject, registerPayment, markPaid, getPayments, complete };
}
