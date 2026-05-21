<script setup lang="ts">
withDefaults(defineProps<{
  count?: number;
}>(), {
  count: 0,
});

const open = ref(false);

const emit = defineEmits<{
  confirm: [];
}>();

async function onSubmit() {
  emit("confirm");
  open.value = false;
}
</script>

<template>
  <UModal
    v-model:open="open"
    :title="`Eliminar ${count} cotización${count > 1 ? 'es' : ''}`"
    :description="`¿Estás seguro? Esta acción no se puede deshacer.`"
  >
    <slot />

    <template #body>
      <div class="flex justify-end gap-2">
        <UButton
          label="Cancelar"
          color="neutral"
          variant="subtle"
          @click="open = false"
        />
        <UButton
          label="Eliminar"
          color="error"
          variant="solid"
          loading-auto
          @click="onSubmit"
        />
      </div>
    </template>
  </UModal>
</template>
