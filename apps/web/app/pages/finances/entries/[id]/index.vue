<script setup lang="ts">
definePageMeta({ layout: "default" });

import PageContentNarrow from "~/components/ui/PageContentNarrow.vue";
import PageSectionCard from "~/components/ui/PageSectionCard.vue";
import PageStateCard from "~/components/ui/PageStateCard.vue";
import { formatMoney } from "~/utils/formatMoney";

const route = useRoute();
const router = useRouter();
const { toastApiError } = useApiError();
const toast = useToast();
const { findEntry, removeEntry } = useFinancesApi();

const entryId = computed(() => Number(route.params.id));
const deleting = ref(false);

const { data: entry, status } = useAsyncData(
  () => `finance-entry-detail-${entryId.value}`,
  () => findEntry(entryId.value),
  { server: false, watch: [entryId] },
);

async function onDelete() {
  if (!entry.value?.is_manual) return;

  deleting.value = true;
  try {
    await removeEntry(entryId.value);
    toast.add({ title: "Movimiento eliminado", color: "success" });
    await router.push("/finances");
  } catch (error) {
    toastApiError(error, { fallback: "No se pudo eliminar el movimiento." });
  } finally {
    deleting.value = false;
  }
}
</script>

<template>
  <UDashboardPanel id="finance-entry-detail">
    <template #header>
      <UDashboardNavbar :title="entry ? entry.name : 'Detalle de movimiento'">
        <template #leading>
          <UDashboardSidebarCollapse />
        </template>
        <template #right>
          <div class="flex items-center gap-2">
            <UButton
              label="Volver"
              icon="i-lucide-arrow-left"
              variant="ghost"
              @click="router.push('/finances')"
            />
            <UButton
              v-if="entry?.is_manual"
              label="Editar"
              icon="i-lucide-pencil"
              variant="outline"
              @click="router.push(`/finances/entries/${entry.id}/edit`)"
            />
            <UButton
              v-if="entry?.is_manual"
              label="Eliminar"
              icon="i-lucide-trash"
              color="error"
              variant="outline"
              :loading="deleting"
              @click="onDelete"
            />
          </div>
        </template>
      </UDashboardNavbar>
    </template>

    <template #body>
      <PageContentNarrow>
        <PageStateCard v-if="status === 'pending'" message="Cargando..." />

        <PageStateCard v-else-if="!entry" message="Movimiento no encontrado." />

        <div v-else class="space-y-6">
          <div class="flex flex-wrap items-center gap-3">
            <UBadge
              :label="entry.type === 'income' ? 'Ingreso' : 'Gasto'"
              :color="entry.type === 'income' ? 'success' : 'error'"
              variant="subtle"
            />
            <UBadge
              :label="entry.is_manual ? 'Manual' : 'Automático'"
              :color="entry.is_manual ? 'info' : 'neutral'"
              variant="subtle"
            />
          </div>

          <PageSectionCard>
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
              <div>
                <p class="text-sm text-muted">Nombre</p>
                <p class="mt-1 text-base font-medium text-highlighted">{{ entry.name }}</p>
              </div>

              <div>
                <p class="text-sm text-muted">Monto</p>
                <p class="mt-1 text-base font-medium text-highlighted">
                  {{ formatMoney(entry.amount_cents, 'COP') }}
                </p>
              </div>

              <div>
                <p class="text-sm text-muted">Fecha</p>
                <p class="mt-1 text-base">{{ entry.occurred_on }}</p>
              </div>

              <div>
                <p class="text-sm text-muted">Categoría</p>
                <p class="mt-1 text-base">{{ entry.category_name ?? 'Sin categoría' }}</p>
              </div>

              <div class="md:col-span-2">
                <p class="text-sm text-muted">Descripción</p>
                <p class="mt-1 text-base whitespace-pre-line">
                  {{ entry.description || 'Sin descripción adicional.' }}
                </p>
              </div>
            </div>
          </PageSectionCard>
        </div>
      </PageContentNarrow>
    </template>
  </UDashboardPanel>
</template>
