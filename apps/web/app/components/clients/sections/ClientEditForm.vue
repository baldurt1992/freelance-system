<script setup lang="ts">
import ClientFormFields from "~/components/clients/ui/ClientFormFields.vue";

defineProps<{
  name: string;
  email: string;
  phone: string;
  taxId: string;
  address: string;
  notes: string;
  avatarPreview: string | null;
  saving: boolean;
}>();

defineEmits<{
  "update:name": [value: string];
  "update:email": [value: string];
  "update:phone": [value: string];
  "update:taxId": [value: string];
  "update:address": [value: string];
  "update:notes": [value: string];
  save: [];
  cancel: [];
  "file-change": [event: Event];
}>();

const fileInput = useTemplateRef<HTMLInputElement>("fileInput");
</script>

<template>
  <form class="space-y-6" @submit.prevent="$emit('save')">
    <ClientFormFields
      :name="name"
      :email="email"
      :phone="phone"
      :tax-id="taxId"
      :address="address"
      :notes="notes"
      :avatar-preview="avatarPreview"
      @update:name="$emit('update:name', $event)"
      @update:email="$emit('update:email', $event)"
      @update:phone="$emit('update:phone', $event)"
      @update:tax-id="$emit('update:taxId', $event)"
      @update:address="$emit('update:address', $event)"
      @update:notes="$emit('update:notes', $event)"
    />

    <div class="flex items-center gap-3">
      <input
        ref="fileInput"
        type="file"
        accept="image/*"
        class="hidden"
        @change="$emit('file-change', $event)"
      />
      <UButton
        label="Subir Avatar"
        icon="i-lucide-upload"
        variant="outline"
        @click="fileInput?.click()"
      />
      <div class="flex-1" />
      <UButton variant="outline" @click="$emit('cancel')">
        Cancelar
      </UButton>
      <UButton type="submit" :loading="saving">
        Guardar
      </UButton>
    </div>
  </form>
</template>
