import type { FinanceCategory, FinanceCategoryCreateInput, FinanceCategoryUpdateInput, FinanceEntryType } from "@freelance/contracts";
import { useFinancesApi } from "~/composables/finances/useFinancesApi";

export function useFinanceCategories(type: Ref<FinanceEntryType>) {
  const { listCategories, createCategory, updateCategory, removeCategory } = useFinancesApi();

  const { data, status, refresh } = useAsyncData(
    () => `finance-categories-${type.value}`,
    async () => {
      const response = await listCategories(type.value);
      return response.data;
    },
    { watch: [type], default: () => [] },
  );

  const categories = computed(() => data.value ?? []);

  async function createFinanceCategory(input: FinanceCategoryCreateInput): Promise<FinanceCategory> {
    const category = await createCategory(input);
    await refresh();
    return category;
  }

  async function updateFinanceCategory(id: number, input: FinanceCategoryUpdateInput): Promise<FinanceCategory> {
    const category = await updateCategory(id, input);
    await refresh();
    return category;
  }

  async function deleteFinanceCategory(id: number): Promise<void> {
    await removeCategory(id);
    await refresh();
  }

  return {
    categories,
    categoriesStatus: status,
    refreshCategories: refresh,
    createFinanceCategory,
    updateFinanceCategory,
    deleteFinanceCategory,
  };
}
