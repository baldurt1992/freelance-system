<script setup lang="ts">
import type { DashboardRecentActivityItem } from "@freelance/contracts";

const props = defineProps<{
  items: DashboardRecentActivityItem[];
}>();

const formatter = new Intl.DateTimeFormat("es-CO", {
  dateStyle: "medium",
  timeStyle: "short",
});

function kindLabel(kind: DashboardRecentActivityItem["kind"]): string {
  if (kind === "finance_entry") return "Finanzas";
  if (kind === "quote") return "Cotización";
  return "Proyecto";
}

function kindColor(kind: DashboardRecentActivityItem["kind"]): "success" | "info" | "warning" {
  if (kind === "finance_entry") return "success";
  if (kind === "quote") return "info";
  return "warning";
}

function formatOccurredAt(value: string): string {
  return formatter.format(new Date(value));
}
</script>

<template>
  <UPageCard
    title="Actividad reciente"
    description="Últimos movimientos y registros relevantes del mes seleccionado."
    variant="subtle"
  >
    <div v-if="items.length" class="space-y-3">
      <NuxtLink
        v-for="item in items"
        :key="item.id"
        :to="item.to"
        class="flex items-start justify-between gap-4 rounded-lg border border-default px-4 py-3 transition-colors hover:bg-elevated/50"
      >
        <div class="min-w-0 space-y-1">
          <div class="flex flex-wrap items-center gap-2">
            <p class="font-medium text-highlighted">
              {{ item.title }}
            </p>
            <UBadge :label="kindLabel(item.kind)" :color="kindColor(item.kind)" variant="subtle" />
          </div>
          <p class="text-sm text-muted">
            {{ item.description }}
          </p>
        </div>

        <time class="shrink-0 text-xs text-muted" :datetime="item.occurred_at">
          {{ formatOccurredAt(item.occurred_at) }}
        </time>
      </NuxtLink>
    </div>

    <div v-else class="rounded-lg border border-dashed border-default px-4 py-8 text-center text-sm text-muted">
      No hay actividad reciente para este mes.
    </div>
  </UPageCard>
</template>
