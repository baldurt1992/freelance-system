<script setup lang="ts">
import { formatMoney } from "~/utils/formatMoney";
import type { ProjectType } from "@freelance/contracts";

const props = defineProps<{
  clientName?: string;
  name: string;
  type: ProjectType;
  startedAt?: string;
  agreedTotalCents: number;
  currency?: string;
}>();

const typeLabel = computed(() => {
  const labels: Record<ProjectType, string> = {
    freelance: "Freelance",
    fixed: "Precio fijo",
    retainer: "Retainer",
  };

  return labels[props.type];
});

const startedAtLabel = computed(() => {
  if (!props.startedAt) return "Sin definir";

  const [yearString, monthString, dayString] = props.startedAt.split("-");
  const year = Number(yearString);
  const month = Number(monthString);
  const day = Number(dayString);

  if ([year, month, day].some((value) => Number.isNaN(value))) {
    return props.startedAt;
  }

  return new Intl.DateTimeFormat("es-CO", {
    year: "numeric",
    month: "2-digit",
    day: "2-digit",
  }).format(new Date(year, month - 1, day));
});

</script>

<template>
  <UCard class="w-full shadow-sm xl:sticky xl:top-6">
    <div class="space-y-6">
      <div>
        <p class="text-xs font-semibold uppercase tracking-wide text-muted">Resumen</p>
        <h2 class="mt-1 text-xl font-semibold text-highlighted">
          {{ name.trim() || "Nuevo proyecto" }}
        </h2>
        <p class="mt-2 text-sm leading-6 text-muted">
          {{ clientName || "Selecciona un cliente para completar el proyecto." }}
        </p>
      </div>

      <div class="space-y-4 rounded-xl border border-default bg-elevated/30 p-5 shadow-sm">
        <div class="flex items-center justify-between text-sm">
          <span class="text-muted">Tipo</span>
          <span class="font-medium">{{ typeLabel }}</span>
        </div>
        <div class="flex items-center justify-between text-sm">
          <span class="text-muted">Fecha de inicio</span>
          <span class="font-medium">{{ startedAtLabel }}</span>
        </div>
        <div class="space-y-2 border-t border-default pt-4">
          <p class="text-sm text-muted">Total acordado</p>
          <span class="block text-2xl font-semibold text-highlighted sm:text-3xl">
            {{ formatMoney(agreedTotalCents, currency || "COP") }}
          </span>
        </div>
      </div>

      <div class="rounded-xl border border-default/70 bg-muted/20 p-4 shadow-sm">
        <p class="text-sm leading-6 text-muted">
          El alcance y las notas del proyecto te ayudan a dejar claro qué se va a entregar, qué incluye el cobro y
          qué contexto operativo necesitas conservar antes de guardarlo.
        </p>
      </div>
    </div>
  </UCard>
</template>
