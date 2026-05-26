import { useApi } from "~/composables/api/useApi";
import type {
  DocumentTemplate,
  DocumentTemplateListResponse,
  DocumentTemplatePreviewInput,
  DocumentTemplateType,
  DocumentTemplateUpdateInput,
} from "@freelance/contracts";

/** API de plantillas HTML para PDF (cotización y cuenta de cobro). */
export function useDocumentTemplatesApi() {
  const { api } = useApi();

  async function list(type: DocumentTemplateType): Promise<DocumentTemplateListResponse> {
    return api<DocumentTemplateListResponse>(`/document-templates?type=${type}`);
  }

  async function update(id: number, input: DocumentTemplateUpdateInput): Promise<DocumentTemplate> {
    return api<DocumentTemplate>(`/document-templates/${id}`, {
      method: "PUT",
      body: input,
    });
  }

  async function preview(input: DocumentTemplatePreviewInput): Promise<Blob> {
    return api<Blob>("/document-templates/preview", {
      method: "POST",
      body: input,
      responseType: "blob",
    });
  }

  return { list, update, preview };
}
