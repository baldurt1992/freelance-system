<script setup lang="ts">
import { z } from 'zod'

definePageMeta({
  layout: 'auth'
})

const auth = useAuthStore()
const tenant = useTenantStore()
const toast = useToast()

const schema = z.object({
  email: z.string().email('Email inválido'),
  password: z.string().min(1, 'La contraseña es obligatoria')
})

const state = reactive({
  email: 'admin@admin.com',
  password: ''
})

const { parseApiError } = useApiError()

const errorMessage = ref<string | null>(null)

async function onSubmit() {
  errorMessage.value = null

  const parsed = schema.safeParse(state)
  if (!parsed.success) {
    errorMessage.value = parsed.error.issues[0]?.message ?? 'Datos inválidos'
    return
  }

  try {
    await auth.login(parsed.data.email, parsed.data.password)
    toast.add({
      title: 'Sesión iniciada',
      description: `Bienvenido a ${tenant.displayName}`,
      color: 'success'
    })
    await navigateTo('/')
  } catch (error: unknown) {
    const parsed = parseApiError(error)
    errorMessage.value = parsed.message
    console.error('[AuthLogin] Error al iniciar sesión', {
      email: state.email,
      kind: parsed.kind,
      message: parsed.message
    })
  }
}
</script>

<template>
  <UCard class="w-full max-w-md">
    <template #header>
      <div class="space-y-1">
        <p class="text-lg font-semibold text-highlighted">
          Freelance System
        </p>
        <p class="text-sm text-muted">
          Inicia sesión en tu workspace
        </p>
      </div>
    </template>

    <UForm
      :state="state"
      class="space-y-4"
      @submit.prevent="onSubmit"
    >
      <UFormField label="Email" name="email" required>
        <UInput
          v-model="state.email"
          type="email"
          autocomplete="email"
          placeholder="tu@email.com"
        />
      </UFormField>

      <UFormField label="Contraseña" name="password" required>
        <UInput
          v-model="state.password"
          type="password"
          autocomplete="current-password"
        />
      </UFormField>

      <p v-if="errorMessage" class="text-sm text-error">
        {{ errorMessage }}
      </p>

      <UButton
        type="submit"
        block
        :loading="auth.loading"
        label="Entrar"
      />
    </UForm>
  </UCard>
</template>
