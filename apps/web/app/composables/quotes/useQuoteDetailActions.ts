import { useQuotesApi } from "~/composables/quotes/useQuotesApi";
import { useProjectsApi } from "~/composables/projects/useProjectsApi";

/** Acciones de detalle de cotización: transiciones, PDF y conversión a proyecto. */
export function useQuoteDetailActions(
  quoteId: Ref<number>,
  refresh: () => Promise<void>,
  quoteNumber: Ref<string | undefined>,
) {
  const router = useRouter();
  const toast = useToast();
  const { toastApiError } = useApiError();
  const { send, accept, reject, downloadPdf } = useQuotesApi();
  const { convertQuoteToProject } = useProjectsApi();

  const loadingAction = ref<string | null>(null);
  const converting = ref(false);

  async function doAction(action: "send" | "accept" | "reject") {
    loadingAction.value = action;

    try {
      const handlers = { send, accept, reject };
      await handlers[action](quoteId.value);
      toast.add({
        title: action === "send" ? "Enviada" : action === "accept" ? "Aceptada" : "Rechazada",
      });
      await refresh();
    } catch (error) {
      toastApiError(error, { fallback: `No se pudo ${action} la cotización.` });
    } finally {
      loadingAction.value = null;
    }
  }

  async function onDownloadPdf() {
    try {
      const blob = await downloadPdf(quoteId.value);
      const url = URL.createObjectURL(blob);
      const a = document.createElement("a");
      a.href = url;
      a.download = `${quoteNumber.value ?? "cotizacion"}.pdf`;
      document.body.appendChild(a);
      a.click();
      document.body.removeChild(a);
      URL.revokeObjectURL(url);
    } catch (error) {
      toastApiError(error, { fallback: "No se pudo descargar el PDF." });
    }
  }

  async function onConvertToProject() {
    converting.value = true;

    try {
      const project = await convertQuoteToProject(quoteId.value);
      toast.add({ title: "Proyecto creado desde cotización" });
      await router.push(`/projects/${project.id}`);
    } catch (error) {
      toastApiError(error, { fallback: "No se pudo convertir la cotización a proyecto." });
    } finally {
      converting.value = false;
    }
  }

  return {
    loadingAction,
    converting,
    doAction,
    onDownloadPdf,
    onConvertToProject,
  };
}
