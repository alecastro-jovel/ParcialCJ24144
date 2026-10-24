<template>
  <v-dialog :model-value="modelValue" persistent max-width="640" @update:model-value="$emit('update:modelValue', $event)">
    <v-card rounded="lg" class="overflow-hidden">
      <!-- Encabezado -->
      <div class="barra d-flex align-center px-4">
        <span class="flex-grow-1 text-center font-weight-bold">Cambio de contraseña</span>
        <v-btn icon="mdi-close" size="x-small" variant="text" color="white" aria-label="Cerrar" @click="cerrar" />
      </div>

      <v-card-text class="pa-6">
        <v-row align="center">
          <v-col cols="12" sm="4" class="text-center d-none d-sm-block">
            <v-avatar size="110" color="background"><v-icon size="64" color="secondary">mdi-shield-lock</v-icon></v-avatar>
          </v-col>
          <v-col cols="12" sm="8">
            <p class="text-body-2 mb-4">Por seguridad, debes cambiar tu contraseña temporal antes de continuar usando el sistema.</p>
            <v-alert v-if="errorGeneral" type="error" variant="tonal" density="compact" class="mb-3">{{ errorGeneral }}</v-alert>

            <v-form ref="formRef" @submit.prevent="guardar">
              <v-text-field
                v-model="actual" label="Contraseña actual" density="compact" variant="outlined"
                prepend-inner-icon="mdi-lock" autocomplete="current-password"
                :type="ver.actual ? 'text' : 'password'"
                :append-inner-icon="ver.actual ? 'mdi-eye-off' : 'mdi-eye'" @click:append-inner="ver.actual = !ver.actual"
                :rules="[req]" :error-messages="errores.current_password"
              />
              <v-text-field
                v-model="nueva" label="Nueva contraseña" density="compact" variant="outlined"
                prepend-inner-icon="mdi-lock" autocomplete="new-password"
                :type="ver.nueva ? 'text' : 'password'"
                :append-inner-icon="ver.nueva ? 'mdi-eye-off' : 'mdi-eye'" @click:append-inner="ver.nueva = !ver.nueva"
                :rules="[req, min8, letrasNumeros, distinta]" :error-messages="errores.password"
                hint="Mínimo 8 caracteres, con letras y números" persistent-hint
              />
              <v-text-field
                v-model="confirmar" label="Confirmar nueva contraseña" density="compact" variant="outlined" class="mt-3"
                prepend-inner-icon="mdi-lock" autocomplete="new-password"
                :type="ver.confirmar ? 'text' : 'password'"
                :append-inner-icon="ver.confirmar ? 'mdi-eye-off' : 'mdi-eye'" @click:append-inner="ver.confirmar = !ver.confirmar"
                :rules="[req, coincide]"
              />
            </v-form>
          </v-col>
        </v-row>
      </v-card-text>

      <!-- Pie -->
      <div class="barra d-flex justify-center ga-3 pa-3">
        <v-btn color="white" variant="flat" class="text-secondary" :loading="loading" @click="guardar">Guardar</v-btn>
        <v-btn color="white" variant="outlined" @click="cerrar">Cerrar</v-btn>
      </div>
    </v-card>
  </v-dialog>
</template>

<script setup>
import { ref, reactive } from 'vue'
import api from '../services/api'
import { auth } from '../store/auth'

defineProps({ modelValue: Boolean })
const emit = defineEmits(['update:modelValue', 'guardado'])

const formRef = ref(null)
const actual = ref('')
const nueva = ref('')
const confirmar = ref('')
const ver = reactive({ actual: false, nueva: false, confirmar: false })
const loading = ref(false)
const errorGeneral = ref('')
const errores = ref({})

const req = (v) => !!v || 'Campo obligatorio'
const min8 = (v) => (v || '').length >= 8 || 'Mínimo 8 caracteres'
const letrasNumeros = (v) => (/[A-Za-z]/.test(v) && /\d/.test(v)) || 'Debe incluir letras y números'
const distinta = (v) => v !== actual.value || 'Debe ser distinta a la actual'
const coincide = (v) => v === nueva.value || 'Las contraseñas no coinciden'

function cerrar() { emit('update:modelValue', false) }

async function guardar() {
  errorGeneral.value = ''
  errores.value = {}
  const { valid } = await formRef.value.validate()
  if (!valid) return

  loading.value = true
  try {
    const { data } = await api.post('/password', {
      current_password: actual.value,
      password: nueva.value,
      password_confirmation: confirmar.value,
    })
    auth.setUser(data.user)
    actual.value = nueva.value = confirmar.value = ''
    emit('guardado')
    emit('update:modelValue', false)
  } catch (e) {
    if (e.response?.status === 422) errores.value = e.response.data.errors || {}
    else errorGeneral.value = e.response?.data?.message || 'No se pudo cambiar la contraseña.'
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.barra { background: #2d52a8; color: #fff; min-height: 44px; }
</style>
