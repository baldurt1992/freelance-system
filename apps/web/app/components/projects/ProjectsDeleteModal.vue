<script setup lang="ts">
defineProps<{
  count: number;
}>();

const emit = defineEmits<{
  confirm: [];
}>();

const open = ref(false);
</script>

<template>
  <UModal v-model:open="open" :ui="{ content: 'max-w-md' }">
    <slot />

    <template #body>
      <p class="text-sm">
        Vas a eliminar {{ count }} proyecto(s). Esta acción no se puede deshacer.
      </p>
    </template>

    <template #footer>
      <div class="flex justify-end gap-2">
        <UButton label="Cancelar" variant="outline" @click="open = false" />
        <UButton
          label="Eliminar"
          color="error"
          @click="emit('confirm'); open = false"
        />
      </div>
    </template>
  </UModal>
</template>
