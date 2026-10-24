<template>
  <div>
    <v-btn variant="text" prepend-icon="mdi-arrow-left" class="mb-2" @click="volver">Volver al listado</v-btn>
    <h1 class="text-h5 mb-4">Detalle de la constancia #{{ id }}</h1>

    <v-progress-linear v-if="loading" indeterminate />

    <v-card v-if="c" class="pa-6" max-width="900">
      <!-- MODO EDICIÓN -->
      <template v-if="editando">
        <ConstanciaForm
          :initial="c" show-estado cancelable submit-text="Guardar cambios"
          :loading="guardando" :errors="errors" @submit="actualizar" @cancel="editando = false"
        />
      </template>

      <!-- MODO LECTURA -->
      <template v-else>
        <v-row>
          <v-col cols="12" md="8">
            <v-list density="comfortable">
              <v-list-item v-for="f in filas" :key="f.label" :title="f.value" :subtitle="f.label" />
              <v-list-item subtitle="Estado de la solicitud">
                <template #title>
                  <v-chip :color="c.estado_solicitud ? 'success' : 'grey'">
                    {{ c.estado_solicitud ? 'Activa (solicitada)' : 'Inactiva (lista)' }}
                  </v-chip>
                </template>
              </v-list-item>
            </v-list>
          </v-col>
          <v-col cols="12" md="4" class="text-center">
            <!-- Código QR con los datos de la constancia -->
            <v-img v-if="qr" :src="qr" width="180" class="mx-auto" />
            <div class="text-caption text-medium-emphasis">Código QR de la constancia</div>
          </v-col>
        </v-row>

        <v-divider class="my-4" />

        <div class="d-flex flex-wrap ga-2">
          <v-btn color="primary" prepend-icon="mdi-pencil" @click="editando = true">Actualizar</v-btn>
          <v-btn color="success" prepend-icon="mdi-email-check" :loading="enviando" @click="enviarCorreo">
            {{ c.estado_solicitud ? 'Marcar lista y enviar correo' : 'Reenviar correo' }}
          </v-btn>
          <v-btn color="error" variant="tonal" prepend-icon="mdi-delete" @click="dialogo = true">Eliminar</v-btn>
        </div>
      </template>
    </v-card>

    <!-- Confirmación de eliminación -->
    <v-dialog v-model="dialogo" max-width="420">
      <v-card>
        <v-card-title>¿Eliminar constancia?</v-card-title>
        <v-card-text>Esta acción no se puede deshacer.</v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn variant="text" @click="dialogo = false">Cancelar</v-btn>
          <v-btn color="error" :loading="eliminando" @click="eliminar">Eliminar</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <v-snackbar v-model="snack.show" :color="snack.color" timeout="4000">{{ snack.text }}</v-snackbar>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import QRCode from 'qrcode'
import api from '../services/api'
import ConstanciaForm from '../components/ConstanciaForm.vue'

const props = defineProps({ id: String })
const router = useRouter()

const c = ref(null)
const qr = ref('')
const loading = ref(false)
const editando = ref(false)
const guardando = ref(false)
const enviando = ref(false)
const eliminando = ref(false)
const dialogo = ref(false)
const errors = ref({})
const snack = reactive({ show: false, text: '', color: 'success' })

const filas = computed(() => !c.value ? [] : [
  { label: 'Nombre del empleado', value: c.value.nombre_empleado },
  { label: 'Correo', value: c.value.correo },
  { label: 'Móvil', value: c.value.movil },
  { label: 'Tipo de constancia', value: c.value.tipo_de_constancia },
  { label: 'Descripción', value: c.value.descripcion },
  { label: 'Cantidad', value: String(c.value.cantidad) },
  { label: 'Fecha de solicitud', value: c.value.fecha_de_solicitud },
  { label: 'Hora de solicitud', value: c.value.hora_de_solicitud },
])

function aviso(text, color = 'success') {
  snack.text = text; snack.color = color; snack.show = true
}

async function generarQR() {
  const d = c.value
  const texto = `CONSTANCIA #${d.id_constancias}\n${d.nombre_empleado}\n${d.tipo_de_constancia}\n${d.fecha_de_solicitud} ${d.hora_de_solicitud}`
  qr.value = await QRCode.toDataURL(texto, { width: 180, margin: 1 })
}

async function cargar() {
  loading.value = true
  try {
    const { data } = await api.get(`/constancias/${props.id}`)
    c.value = data
    await generarQR()
  } catch (e) {
    aviso('No se encontró la constancia', 'error')
  } finally {
    loading.value = false
  }
}

async function actualizar(payload) {
  guardando.value = true
  errors.value = {}
  try {
    const { data } = await api.put(`/constancias/${props.id}`, payload)
    c.value = data
    await generarQR()
    editando.value = false
    aviso('Constancia actualizada')
  } catch (e) {
    errors.value = e.response?.data?.errors || {}
    aviso(e.response?.data?.message || 'No se pudo actualizar', 'error')
  } finally {
    guardando.value = false
  }
}

async function enviarCorreo() {
  enviando.value = true
  try {
    const { data } = await api.post(`/constancias/${props.id}/enviar-correo`)
    c.value = data.constancia
    aviso(data.message)
  } catch (e) {
    if (e.response?.data?.message) await cargar() // el estado pudo cambiar aunque falle el correo
    aviso(e.response?.data?.message || 'No se pudo enviar el correo', 'error')
  } finally {
    enviando.value = false
  }
}

async function eliminar() {
  eliminando.value = true
  try {
    await api.delete(`/constancias/${props.id}`)
    router.replace({ name: 'constancias', params: { estado: 'activas' } })
  } catch (e) {
    aviso('No se pudo eliminar', 'error')
    eliminando.value = false
    dialogo.value = false
  }
}

function volver() {
  router.push({ name: 'constancias', params: { estado: c.value?.estado_solicitud === false ? 'inactivas' : 'activas' } })
}

onMounted(cargar)
</script>
