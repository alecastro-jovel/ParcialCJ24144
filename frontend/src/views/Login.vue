<template>
  <v-main class="fondo-acceso">
    <v-container class="fill-height d-flex justify-center align-center">
      <v-card class="tarjeta pa-8" elevation="10" rounded="xl">
        <div class="text-center">
          <LogoUls :size="84" />
          <h1 class="text-h5 titulo mt-4">Sistema de Gestión de Constancias</h1>
          <v-chip size="small" :color="esAdmin ? 'primary' : 'secondary'" class="mt-3">
            Acceso {{ esAdmin ? 'Administrador' : 'Registrador' }}
          </v-chip>
          <p class="text-caption text-medium-emphasis mt-3">
            Ingrese su correo electrónico y contraseña para acceder a su cuenta
          </p>
        </div>

        <v-alert v-if="error" type="error" variant="tonal" class="mt-4" density="compact">{{ error }}</v-alert>

        <v-form ref="formRef" class="mt-4" @submit.prevent="entrar">
          <v-text-field
            v-model="email" placeholder="Correo electrónico" type="email" autocomplete="username"
            prepend-inner-icon="mdi-account" variant="outlined" density="compact"
            :rules="[req, emailRule]"
          />
          <v-text-field
            v-model="password" placeholder="Contraseña" autocomplete="current-password"
            prepend-inner-icon="mdi-lock"
            :type="ver ? 'text' : 'password'"
            :append-inner-icon="ver ? 'mdi-eye-off' : 'mdi-eye'"
            @click:append-inner="ver = !ver"
            variant="outlined" density="compact" :rules="[req]"
          />
          <v-btn type="submit" block color="secondary" size="large" :loading="loading">Ingresar</v-btn>
        </v-form>

        <div class="text-center mt-4">
          <a href="#" class="enlace" @click.prevent="recuperar = !recuperar">Recuperar tu contraseña</a>
          <v-expand-transition>
            <v-alert v-if="recuperar" type="warning" variant="flat" density="compact" class="mt-3 text-left">
              Solicita al administrador que restablezca tu contraseña. Recibirás una contraseña
              temporal que deberás cambiar al ingresar.
            </v-alert>
          </v-expand-transition>
        </div>

        <div class="text-center mt-4">
          <v-btn variant="text" size="small" :to="{ name: 'home' }" prepend-icon="mdi-arrow-left">Volver</v-btn>
        </div>

        <div class="text-caption text-center text-medium-emphasis mt-2">
          Gestión y administración de Constancias o Notas ULS.
        </div>
        <div class="text-caption text-center text-medium-emphasis mt-1">
          Demo: {{ esAdmin ? 'admin@parcial.com / Admin123*' : 'registrador@parcial.com / Registrador123*' }}
        </div>
      </v-card>
    </v-container>
  </v-main>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import { auth } from '../store/auth'
import LogoUls from '../components/LogoUls.vue'

const props = defineProps({ rol: String })
const router = useRouter()

const esAdmin = computed(() => props.rol === 'admin')
const email = ref('')
const password = ref('')
const ver = ref(false)
const loading = ref(false)
const error = ref('')
const recuperar = ref(false)
const formRef = ref(null)

const req = (v) => !!v || 'Campo obligatorio'
const emailRule = (v) => /.+@.+\..+/.test(v) || 'Correo inválido'

async function entrar() {
  error.value = ''
  const { valid } = await formRef.value.validate()
  if (!valid) return

  loading.value = true
  try {
    const { requires2fa } = await auth.login(props.rol, email.value, password.value)
    // Solo el registrador pasa por el código del QR
    router.push(requires2fa ? { name: 'verificacion' } : { name: 'dashboard' })
  } catch (e) {
    error.value = e.response?.data?.message || 'No se pudo conectar con el servidor.'
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.tarjeta { width: 100%; max-width: 440px; background: #fff; }
.titulo { color: #2d52a8; font-weight: 800; line-height: 1.25; }
.enlace { color: #2d52a8; font-size: 14px; text-decoration: underline; }
</style>
