<template>
  <div>
    <v-card class="pa-4" rounded="lg">
      <div class="d-flex justify-center mb-4">
        <div class="pastilla text-h6">Listado de usuarios</div>
      </div>

      <div class="d-flex justify-end mb-2">
        <v-btn color="secondary" prepend-icon="mdi-plus" @click="abrirNuevo">Registrar nuevo</v-btn>
      </div>

      <!-- Filtros -->
      <v-row dense align="center" class="mb-2">
        <v-col cols="12" sm="4">
          <v-text-field v-model="filtros.nombre" label="Nombre o correo" density="compact" variant="outlined" hide-details clearable @keyup.enter="filtrar" />
        </v-col>
        <v-col cols="12" sm="3">
          <v-select v-model="filtros.role" :items="tipos" item-title="t" item-value="v" label="Tipo de usuario" density="compact" variant="outlined" hide-details clearable />
        </v-col>
        <v-col cols="12" sm="3">
          <v-select v-model="filtros.estado" :items="estados" item-title="t" item-value="v" label="Estado" density="compact" variant="outlined" hide-details clearable />
        </v-col>
        <v-col cols="12" sm="2" class="d-flex ga-2 justify-end">
          <v-btn color="secondary" @click="filtrar">Filtrar</v-btn>
          <v-btn variant="tonal" @click="limpiar">Limpiar</v-btn>
        </v-col>
      </v-row>

      <v-progress-linear v-if="loading" indeterminate color="secondary" />
      <v-alert v-if="error" type="error" variant="tonal" class="my-2" density="compact">{{ error }}</v-alert>

      <v-table density="comfortable" class="tabla">
        <thead>
          <tr>
            <th>Nombre</th><th>Correo</th><th>Número documento</th><th>Tipo usuario</th><th>Estado</th><th class="text-center">Acciones</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="u in usuarios" :key="u.id">
            <td>{{ u.name }}</td>
            <td>{{ u.email }}</td>
            <td>{{ u.documento || '—' }}</td>
            <td>{{ u.role === 'admin' ? 'ADMIN' : 'REGISTRADOR' }}</td>
            <td>
              <v-chip size="small" :color="u.estado ? 'success' : 'error'" variant="flat" label>
                {{ u.estado ? 'Habilitado' : 'Inhabilitado' }}
              </v-chip>
            </td>
            <td class="text-center text-no-wrap">
              <v-btn icon="mdi-eye" size="x-small" variant="text" color="secondary" title="Ver" @click="abrirVer(u)" />
              <v-btn icon="mdi-pencil" size="x-small" variant="text" color="secondary" title="Editar" @click="abrirEditar(u)" />
              <v-btn
                :icon="u.estado ? 'mdi-cancel' : 'mdi-check-circle-outline'" size="x-small" variant="text"
                :color="u.estado ? 'error' : 'success'" :title="u.estado ? 'Inhabilitar' : 'Habilitar'"
                :disabled="u.id === auth.state.user?.id" @click="cambiarEstado(u)"
              />
            </td>
          </tr>
          <tr v-if="!loading && !usuarios.length">
            <td colspan="6" class="text-center text-medium-emphasis pa-6">No hay usuarios con esos filtros</td>
          </tr>
        </tbody>
      </v-table>

      <div class="d-flex justify-end align-center mt-3">
        <span class="text-caption mr-4">Página {{ page }} de {{ lastPage }} · {{ total }} usuarios</span>
        <v-pagination v-model="page" :length="lastPage" :total-visible="5" density="comfortable" />
      </div>
    </v-card>

    <!-- Ver / Editar / Nuevo -->
    <v-dialog v-model="dialogo" max-width="520">
      <v-card rounded="lg">
        <v-card-title class="bg-secondary text-white">{{ titulos[modo] }}</v-card-title>
        <v-card-text class="pt-4">
          <v-alert v-if="errorForm" type="error" variant="tonal" density="compact" class="mb-3">{{ errorForm }}</v-alert>
          <v-form ref="formRef">
            <v-text-field v-model="form.name" label="Nombre" variant="outlined" density="compact" :readonly="modo === 'ver'" :rules="[req, soloLetras]" :error-messages="erroresForm.name" />
            <v-text-field v-model="form.email" label="Correo" type="email" variant="outlined" density="compact" :readonly="modo === 'ver'" :rules="[req, emailRule]" :error-messages="erroresForm.email" />
            <v-text-field v-model="form.documento" label="Número de documento (00000000-0)" variant="outlined" density="compact" :readonly="modo === 'ver'" :rules="[docRule]" :error-messages="erroresForm.documento" />
            <v-select v-model="form.role" :items="tipos" item-title="t" item-value="v" label="Tipo de usuario" variant="outlined" density="compact" :readonly="modo === 'ver'" :rules="[req]" :error-messages="erroresForm.role" />
          </v-form>

          <div v-if="modo === 'ver'" class="text-body-2">
            <div v-if="form.role === 'registrador'">
              Login con QR: <strong>{{ form.qr_configurado ? 'Configurado' : 'Pendiente de configurar' }}</strong>
            </div>
            <div v-if="form.debe_cambiar_clave">Tiene una contraseña temporal pendiente de cambiar.</div>
          </div>

          <p v-if="modo === 'nuevo'" class="text-caption text-medium-emphasis">
            Se generará una contraseña temporal que el usuario deberá cambiar en su primer ingreso.
          </p>
        </v-card-text>
        <v-card-actions class="px-6 pb-4">
          <v-btn v-if="modo !== 'nuevo'" color="warning" variant="flat" prepend-icon="mdi-lock-reset" @click="restablecer">Restablecer contraseña</v-btn>
          <v-spacer />
          <v-btn variant="text" @click="dialogo = false">Cerrar</v-btn>
          <v-btn v-if="modo !== 'ver'" color="secondary" variant="flat" :loading="guardando" @click="guardar">Guardar</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Contraseña temporal (se muestra una sola vez) -->
    <v-dialog v-model="dialogoClave" max-width="460" persistent>
      <v-card rounded="lg">
        <v-card-title class="bg-secondary text-white">Contraseña temporal</v-card-title>
        <v-card-text class="pt-4">
          <p class="mb-3">Entrégala a <strong>{{ claveUsuario }}</strong>. Solo se muestra una vez y deberá cambiarla al ingresar.</p>
          <v-text-field :model-value="clave" readonly variant="outlined" density="compact" append-inner-icon="mdi-content-copy" @click:append-inner="copiar" hide-details />
          <p v-if="restablecido && form.role === 'registrador'" class="text-caption mt-2">
            El registrador deberá volver a escanear su código QR en el próximo ingreso.
          </p>
        </v-card-text>
        <v-card-actions><v-spacer /><v-btn color="secondary" variant="flat" @click="dialogoClave = false">Entendido</v-btn></v-card-actions>
      </v-card>
    </v-dialog>

    <v-snackbar v-model="snack.show" :color="snack.color" timeout="3000">{{ snack.text }}</v-snackbar>
  </div>
</template>

<script setup>
import { ref, reactive, watch, onMounted } from 'vue'
import api from '../services/api'
import { auth } from '../store/auth'

const tipos = [{ v: 'admin', t: 'ADMIN' }, { v: 'registrador', t: 'REGISTRADOR' }]
const estados = [{ v: 'habilitado', t: 'Habilitado' }, { v: 'inhabilitado', t: 'Inhabilitado' }]
const titulos = { ver: 'Detalle del usuario', editar: 'Editar usuario', nuevo: 'Registrar nuevo usuario' }

const usuarios = ref([])
const page = ref(1)
const lastPage = ref(1)
const total = ref(0)
const loading = ref(false)
const error = ref('')
const filtros = reactive({ nombre: '', role: null, estado: null })
const snack = reactive({ show: false, text: '', color: 'success' })

const dialogo = ref(false)
const modo = ref('ver')
const formRef = ref(null)
const form = reactive({ id: null, name: '', email: '', documento: '', role: 'registrador', qr_configurado: false, debe_cambiar_clave: false })
const erroresForm = ref({})
const errorForm = ref('')
const guardando = ref(false)

const dialogoClave = ref(false)
const clave = ref('')
const claveUsuario = ref('')
const restablecido = ref(false)

const req = (v) => !!v || 'Campo obligatorio'
const emailRule = (v) => /.+@.+\..+/.test(v || '') || 'Correo inválido'
const soloLetras = (v) => /^[\p{L}\s.'-]+$/u.test(v || '') || 'Solo letras'
const docRule = (v) => !v || /^\d{8}-\d$/.test(v) || 'Formato 00000000-0'

async function cargar() {
  loading.value = true
  error.value = ''
  try {
    const { data } = await api.get('/usuarios', {
      params: { page: page.value, nombre: filtros.nombre || undefined, role: filtros.role || undefined, estado: filtros.estado || undefined },
    })
    usuarios.value = data.data
    lastPage.value = data.last_page
    total.value = data.total
  } catch (e) {
    error.value = e.response?.data?.message || 'No se pudo cargar el listado.'
  } finally {
    loading.value = false
  }
}

function filtrar() { page.value === 1 ? cargar() : (page.value = 1) }
function limpiar() { filtros.nombre = ''; filtros.role = null; filtros.estado = null; filtrar() }
watch(page, cargar)
onMounted(cargar)

function avisar(text, color = 'success') { snack.text = text; snack.color = color; snack.show = true }

function abrir(m, u = null) {
  modo.value = m
  erroresForm.value = {}
  errorForm.value = ''
  Object.assign(form, { id: u?.id ?? null, name: u?.name ?? '', email: u?.email ?? '', documento: u?.documento ?? '', role: u?.role ?? 'registrador', qr_configurado: u?.qr_configurado ?? false, debe_cambiar_clave: u?.debe_cambiar_clave ?? false })
  dialogo.value = true
}
const abrirNuevo = () => abrir('nuevo')
const abrirVer = (u) => abrir('ver', u)
const abrirEditar = (u) => abrir('editar', u)

async function guardar() {
  errorForm.value = ''
  erroresForm.value = {}
  const { valid } = await formRef.value.validate()
  if (!valid) return
  guardando.value = true
  const payload = { name: form.name, email: form.email, documento: form.documento || null, role: form.role }
  try {
    if (modo.value === 'nuevo') {
      const { data } = await api.post('/usuarios', payload)
      dialogo.value = false
      mostrarClave(data.temporary_password, data.user.name, false)
    } else {
      await api.put(`/usuarios/${form.id}`, payload)
      dialogo.value = false
      avisar('Usuario actualizado')
    }
    cargar()
  } catch (e) {
    if (e.response?.status === 422 && e.response.data.errors) erroresForm.value = e.response.data.errors
    else errorForm.value = e.response?.data?.message || 'No se pudo guardar.'
  } finally {
    guardando.value = false
  }
}

async function cambiarEstado(u) {
  try {
    await api.put(`/usuarios/${u.id}`, { name: u.name, email: u.email, documento: u.documento, role: u.role, estado: !u.estado })
    avisar(u.estado ? 'Usuario inhabilitado' : 'Usuario habilitado')
    cargar()
  } catch (e) {
    avisar(e.response?.data?.message || 'No se pudo cambiar el estado', 'error')
  }
}

async function restablecer() {
  try {
    const { data } = await api.post(`/usuarios/${form.id}/restablecer`)
    dialogo.value = false
    mostrarClave(data.temporary_password, data.user.name, true)
    cargar()
  } catch (e) {
    errorForm.value = e.response?.data?.message || 'No se pudo restablecer la contraseña.'
  }
}

function mostrarClave(c, nombre, esReset) {
  clave.value = c
  claveUsuario.value = nombre
  restablecido.value = esReset
  dialogoClave.value = true
}

function copiar() {
  navigator.clipboard?.writeText(clave.value)
  avisar('Copiada al portapapeles')
}
</script>

<style scoped>
.pastilla { border: 1px solid #2d52a8; color: #2d52a8; border-radius: 999px; padding: 4px 48px; background: #eef2fb; }
.tabla :deep(thead th) { background: #2d52a8; color: #fff !important; font-weight: 700; }
</style>
