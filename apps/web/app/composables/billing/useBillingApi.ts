import { useApi } from "~/composables/api/useApi";
import type { BillingDocumentListResponse } from "@freelance/contracts";

/** API de cuentas de cobro (PDF y lectura por ID). */
export function useBillingApi() {
  const { api } = useApi();

  async function downloadPdf(billingDocumentId: number): Promise<Blob> {
    return api<Blob>(`/billing-documents/${billingDocumentId}/pdf`, {
      responseType: "blob",
    });
  }

  async function listByProject(projectId: number): Promise<BillingDocumentListResponse> {
    return api<BillingDocumentListResponse>(`/projects/${projectId}/billing-documents`);
  }

  return { downloadPdf, listByProject };
}
