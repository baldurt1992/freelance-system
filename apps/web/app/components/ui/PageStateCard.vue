<script setup lang="ts">
import PageSectionCard from "~/components/ui/PageSectionCard.vue";

defineProps<{
  message: string;
}>();
</script>

<template>
  <PageSectionCard>
    <div class="py-8 text-center text-muted">
      {{ message }}
    </div>
  </PageSectionCard>
</template>
