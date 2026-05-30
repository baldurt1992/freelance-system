<script setup lang="ts">
import { UpdatePasswordInputSchema } from '@freelance/contracts'
import type { FormError } from '@nuxt/ui'

const { updatePassword } = useAuthApi()
const { toastApiError, getFieldError, logApiError } = useApiError()
const toast = useToast()

type PasswordSchema = {
  current_password: string
  password: string
}

const passwordSchema = UpdatePasswordInputSchema

const password = reactive<PasswordSchema>({
  current_password: '',
  password: ''
})

const loading = ref(false)
const fieldErrors = ref<Record<string, string[]>>({})

const validate = (state: Partial<PasswordSchema>): FormError[] => {
  const errors: FormError[] = []
  if (state.current_password && state.password && state.current_password === state.password) {
    errors.push({ name: 'password', message: 'La nueva contraseña debe ser diferente a la actual.' })
  }
  return errors
}

async function onSubmit() {
  fieldErrors.value = {}

  const parsed = passwordSchema.safeParse(password)
  if (!parsed.success) {
    return
  }

  loading.value = true

  try {
    const response = await updatePassword(parsed.data)
    password.current_password = ''
    password.password = ''
    toast.add({
      title: 'Seguridad actualizada',
      description: response.message,
      color: 'success'
    })
  } catch (error: unknown) {
    const parsedError = toastApiError(error, {
      fallback: 'No se pudo actualizar la contraseña.'
    })
    fieldErrors.value = parsedError.fieldErrors
    logApiError('SettingsSecurityUpdatePassword', error)
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <UPageCard
    title="Contraseña"
    description="Confirma tu contraseña actual antes de definir una nueva."
    variant="subtle"
  >
    <UForm
      :schema="passwordSchema"
      :state="password"
      :validate="validate"
      class="flex flex-col gap-4 max-w-md"
      @submit.prevent="onSubmit"
    >
      <UFormField
        label="Contraseña actual"
        name="current_password"
        required
        :error="getFieldError(fieldErrors, 'current_password')"
      >
        <UInput
          id="current-password"
          v-model="password.current_password"
          name="current_password"
          type="password"
          autocomplete="current-password"
          placeholder="Ingresa tu contraseña actual"
          class="w-full"
        />
      </UFormField>

      <UFormField
        label="Nueva contraseña"
        name="password"
        required
        :error="getFieldError(fieldErrors, 'password')"
      >
        <UInput
          id="new-password"
          v-model="password.password"
          name="password"
          type="password"
          autocomplete="new-password"
          placeholder="Mínimo 8 caracteres"
          class="w-full"
        />
      </UFormField>

      <UButton label="Actualizar contraseña" class="w-fit" type="submit" :loading="loading" />
    </UForm>
  </UPageCard>
</template>
