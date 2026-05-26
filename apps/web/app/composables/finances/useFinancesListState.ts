import type { FinancesTab } from "./useFinancesTableColumns";
import { useFinanceSummary } from "./useFinanceSummary";

/**
 * Estado de listado de finanzas: mes, pestaña activa y paginación.
 */
export function useFinancesListState() {
  const { monthInputDefault } = useFinanceSummary();

  const month = ref(monthInputDefault());
  const tab = ref<FinancesTab>("summary");
  const page = ref(1);

  const activeType = computed<"income" | "expense">(() =>
    tab.value === "expense" ? "expense" : "income",
  );

  watch(tab, () => {
    page.value = 1;
  });

  return { month, tab, page, activeType, monthInputDefault };
}
