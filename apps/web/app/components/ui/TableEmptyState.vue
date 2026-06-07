<script setup lang="ts">
defineProps<{
  message: string;
}>();
</script>

<template>
  <div class="py-8 text-center text-muted">
    {{ message }}
  </div>
</template>
