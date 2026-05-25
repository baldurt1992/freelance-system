<script setup lang="ts">
import { formatMoney } from "~/utils/formatMoney";
import type { Project, ProjectPayment } from "@freelance/contracts";

const props = defineProps<{
  project: Project;
  payments: ProjectPayment[];
}>();

const emit = defineEmits<{
  refresh: [];
}>();

const { toastApiError } = useApiError();
const { registerPayment, markPaid } = useProjectsApi();
const toast = useToast();

const amount = ref<number | null>(null);
const loadingPayment = ref(false);
const loadingMark = ref(false);
const showMarkPaidDialog = ref(false);

const sortedPayments = computed(() => {
  return [...props.payments].sort((a, b) => {
    const dateA = new Date(a.paid_at + "T00:00:00").getTime();
    const dateB = new Date(b.paid_at + "T00:00:00").getTime();
    return dateB - dateA;
  });
});

async function onRegisterPayment() {
  if (!amount.value || amount.value <= 0) return;

  loadingPayment.value = true;
  try {
    const amountCents = Math.round(amount.value * 100);
    await registerPayment(props.project.id, { amount_cents: amountCents });
    toast.add({ title: "Pago parcial registrado" });
    amount.value = null;
    emit("refresh");
  } catch (error) {
    toastApiError(error, { fallback: "No se pudo registrar el pago." });
  } finally {
    loadingPayment.value = false;
  }
}

async function onMarkPaid() {
  loadingMark.value = true;
  try {
    await markPaid(props.project.id, {});
    toast.add({ title: "Proyecto marcado como pagado" });
    showMarkPaidDialog.value = false;
    emit("refresh");
  } catch (error) {
    toastApiError(error, { fallback: "No se pudo marcar como pagado." });
  } finally {
    loadingMark.value = false;
  }
}

const paidLabel = computed(() => {
  const kindLabel: Record<string, string> = { partial: "Pago parcial", closure: "Pagado totalmente" };
  return (payment: ProjectPayment) => kindLabel[payment.kind] || payment.kind;
});
</script>

<template>
  <UCard class="sticky top-4">
    <template #header>
      <h3 class="font-semibold">Cobros</h3>
    </template>

    <div class="space-y-4">
      <div class="grid grid-cols-3 gap-2 text-center">
        <div class="rounded-lg bg-elevated/50 p-3">
          <p class="text-xs text-muted">Total</p>
          <p class="text-lg font-semibold">{{ formatMoney(project.agreed_total_cents, project.currency) }}</p>
        </div>
        <div class="rounded-lg bg-elevated/50 p-3">
          <p class="text-xs text-muted">Cobrado</p>
          <p class="text-lg font-semibold text-success">{{ formatMoney(project.paid_total_cents, project.currency) }}</p>
        </div>
        <div class="rounded-lg bg-elevated/50 p-3">
          <p class="text-xs text-muted">Por cobrar</p>
          <p class="text-lg font-semibold" :class="project.is_fully_paid ? 'text-success' : 'text-warning'">
            {{ formatMoney(project.balance_due_cents, project.currency) }}
          </p>
        </div>
      </div>

      <div v-if="!project.is_fully_paid" class="space-y-2">
        <div class="flex items-center gap-2">
          <UInput
            id="payment-amount"
            name="payment-amount"
            v-model="amount"
            type="number"
            placeholder="Monto"
            class="flex-1"
            :min="0"
            :step="0.01"
            aria-label="Monto del pago"
          />
          <UButton
            label="Registrar pago parcial"
            icon="i-lucide-coins"
            :loading="loadingPayment"
            :disabled="!amount || (amount as number) <= 0"
            @click="onRegisterPayment"
          />
        </div>
        <UButton
          block
          label="Marcar como pagado"
          icon="i-lucide-check-circle"
          color="success"
          variant="outline"
          :loading="loadingMark"
          @click="showMarkPaidDialog = true"
        />
      </div>

      <UDivider v-if="sortedPayments.length" />

      <div v-if="sortedPayments.length">
        <h4 class="text-sm font-semibold mb-2">Historial de pagos</h4>
        <div class="space-y-2 max-h-64 overflow-y-auto">
          <div
            v-for="payment in sortedPayments"
            :key="payment.id"
            class="flex items-center justify-between rounded-lg border border-default p-2 text-sm"
          >
            <div>
              <p class="font-medium">{{ formatMoney(payment.amount_cents, project.currency) }}</p>
              <p class="text-xs text-muted">
                {{ new Date(payment.paid_at + 'T00:00:00').toLocaleDateString('es-CO') }}
                &middot; {{ paidLabel(payment) }}
              </p>
            </div>
            <UBadge
              :label="payment.kind === 'closure' ? 'Cierre' : 'Parcial'"
              :color="payment.kind === 'closure' ? 'success' : 'info'"
              variant="subtle"
              size="sm"
            />
          </div>
        </div>
      </div>

      <div v-else class="text-sm text-muted text-center py-2">
        Sin pagos registrados.
      </div>
    </div>

    <template #footer>
      <div v-if="project.is_fully_paid" class="flex items-center gap-2 text-sm text-success">
        <UIcon name="i-lucide-check-circle" />
        <span>Pagado totalmente</span>
      </div>
    </template>
  </UCard>

  <UModal v-model:open="showMarkPaidDialog" :ui="{ content: 'max-w-sm' }">
    <template #body>
      <p class="text-sm">
        Vas a marcar este proyecto como <strong>pagado totalmente</strong>.
        <template v-if="project.balance_due_cents > 0">
          Se registrará un ingreso por <strong>{{ formatMoney(project.balance_due_cents, project.currency) }}</strong>.
        </template>
      </p>
    </template>
    <template #footer>
      <div class="flex justify-end gap-2">
        <UButton label="Cancelar" variant="outline" @click="showMarkPaidDialog = false" />
        <UButton
          label="Confirmar"
          color="success"
          @click="onMarkPaid"
        />
      </div>
    </template>
  </UModal>
</template>
