<template>
  <!-- Login con QR: SOLO registrador -->
  <v-main class="fondo-acceso">
    <v-container class="fill-height d-flex justify-center align-center">
      <v-card class="tarjeta pa-8 text-center" elevation="10" rounded="xl">

        <v-progress-circular v-if="cargando" indeterminate color="secondary" class="my-8" />

        <template v-else>
          <!-- Primera vez: aún no se ha vinculado la app -->
          <template v-if="!yaConfigurado && qr">
            <img :src="qr" alt="Código QR" class="qr" />
            <p class="text-body-1 text-secondary mt-3">
              Escanea el código QR en tu APP.<br />
              Una vez configurado ingresa el código generado para iniciar sesión
            </p>
            <p v-if="secreto" class="text-caption text-medium-emphasis mt-2">
              ¿No puedes escanear? Clave manual: <code>{{ secreto }}</code>
            </p>
          </template>

          <!-- Siguientes veces: solo se pide el código -->
          <template v-else>
            <LogoUls :size="84" />
            <p class="text-body-1 text-secondary font-weight-bold mt-4">
              Ingresa el código generado en tu app para tu inicio de sesión
            </p>
          </template>

          <p class="text-caption text-medium-emphasis mt-1" v-if="correo">Cuenta: {{ correo }}</p>

          <div class="mt-5">
            <CodigoInput ref="codigoRef" v-model="codigo" :error="!!error" @complete="ingresar" />
          </div>

          <v-alert v-if="error" type="error" variant="tonal" density="compact" class="mt-4">{{ error }}</v-alert>

          <v-btn color="secondary" size="large" class="mt-5 px-10" :loading="verificando" :disabled="codigo.length !== 6" @click="ingresar">
            Ingresar
          </v-btn>
          <div class="mt-3">
            <v-btn variant="text" size="small" prepend-icon="mdi-arrow-left" @click="volver">Volver a iniciar sesión</v-btn>
          </div>
        </template>
      </v-card>
    </v-container>
  </v-main>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import QRCode from 'qrcode'
import { auth } from '../store/auth'
import LogoUls from '../components/LogoUls.vue'
import CodigoInput from '../components/CodigoInput.vue'

const router = useRouter()
const cargando = ref(true)
const verificando = ref(false)
const yaConfigurado = ref(false)
const qr = ref('')
const secreto = ref('')
const codigo = ref('')
const error = ref('')
const codigoRef = ref(null)
const correo = auth.correoPendiente()

onMounted(async () => {
  try {
    const data = await auth.generarQr()
    if (data.already_configured) {
      yaConfigurado.value = true
    } else {
      secreto.value = data.secret
      qr.value = await QRCode.toDataURL(data.otpauth_url, { width: 200, margin: 1 })
    }
  } catch (e) {
    error.value = e.response?.data?.message || 'No se pudo cargar la verificación. Inicia sesión de nuevo.'
  } finally {
    cargando.value = false
    setTimeout(() => codigoRef.value?.focus(), 50)
  }
})

async function ingresar() {
  if (codigo.value.length !== 6 || verificando.value) return
  error.value = ''
  verificando.value = true
  try {
    await auth.verificarCodigo(codigo.value)
    router.push({ name: 'dashboard' })
  } catch (e) {
    if (e.response?.status === 401) {
      // El ticket venció: hay que volver a poner la contraseña
      auth.cancelarVerificacion()
      router.push({ name: 'login', params: { rol: 'registrador' } })
      return
    }
    error.value = e.response?.data?.message || e.response?.data?.errors?.code?.[0] || 'No se pudo conectar con el servidor.'
    codigo.value = ''
    codigoRef.value?.focus()
  } finally {
    verificando.value = false
  }
}

function volver() {
  auth.cancelarVerificacion()
  router.push({ name: 'login', params: { rol: 'registrador' } })
}
</script>

<style scoped>
.tarjeta { width: 100%; max-width: 440px; background: #fff; }
.qr { width: 200px; height: 200px; }
code { word-break: break-all; }
</style>
