<script setup lang="ts">
const props = defineProps<{
  name: string;
  email: string;
  phone: string;
  taxId: string;
  address: string;
  notes: string;
  avatarPreview?: string | null;
}>();

const emit = defineEmits<{
  "update:name": [value: string];
  "update:email": [value: string];
  "update:phone": [value: string];
  "update:taxId": [value: string];
  "update:address": [value: string];
  "update:notes": [value: string];
}>();
</script>

<template>
  <div class="space-y-4">
    <div class="flex items-center gap-4">
      <UAvatar
        :src="avatarPreview || undefined"
        :alt="name"
        size="3xl"
      />
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
      <UFormField label="Nombre" required>
        <UInput
          :model-value="name"
          placeholder="Nombre del cliente"
          @update:model-value="emit('update:name', $event)"
        />
      </UFormField>

      <UFormField label="Email">
        <UInput
          :model-value="email"
          type="email"
          placeholder="correo@ejemplo.com"
          @update:model-value="emit('update:email', $event)"
        />
      </UFormField>

      <UFormField label="Teléfono">
        <UInput
          :model-value="phone"
          placeholder="+57 300 123 4567"
          @update:model-value="emit('update:phone', $event)"
        />
      </UFormField>

      <UFormField label="NIT / CC">
        <UInput
          :model-value="taxId"
          placeholder="900.123.456-7"
          @update:model-value="emit('update:taxId', $event)"
        />
      </UFormField>

      <UFormField label="Dirección" class="md:col-span-2">
        <UInput
          :model-value="address"
          placeholder="Calle 100 # 15-20"
          @update:model-value="emit('update:address', $event)"
        />
      </UFormField>

      <UFormField label="Notas" class="md:col-span-2">
        <UTextarea
          :model-value="notes"
          placeholder="Notas internas..."
          @update:model-value="emit('update:notes', $event)"
        />
      </UFormField>
    </div>
  </div>
</template>
