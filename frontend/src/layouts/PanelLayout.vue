<template>
  <v-layout>
    <!-- Menú lateral: se contrae a solo íconos (rail) con el botón ≡ -->
    <v-navigation-drawer
      v-model="drawer" color="primary" theme="dark"
      :rail="rail && $vuetify.display.lgAndUp" :permanent="$vuetify.display.lgAndUp"
    >
      <v-list nav density="comfortable">
        <v-list-item prepend-icon="mdi-menu" :title="rail ? '' : 'Menú'" @click="alternarMenu" />
      </v-list>
      <v-divider />
      <v-list nav density="comfortable">
        <v-list-item
          v-for="item in menu" :key="item.to"
          :to="item.to" :prepend-icon="item.icon" :title="item.title" exact
        >
          <v-tooltip v-if="rail" activator="parent" location="end">{{ item.title }}</v-tooltip>
        </v-list-item>
      </v-list>
      <template #append>
        <v-list nav density="comfortable">
          <v-list-item prepend-icon="mdi-logout" title="Cerrar sesión" @click="salir">
            <v-tooltip v-if="rail" activator="parent" location="end">Cerrar sesión</v-tooltip>
          </v-list-item>
        </v-list>
      </template>
    </v-navigation-drawer>

    <v-app-bar color="surface" flat border density="comfortable">
      <v-app-bar-nav-icon v-if="!$vuetify.display.lgAndUp" @click="drawer = !drawer" />
      <v-app-bar-title class="text-subtitle-2 font-weight-bold">
        BIENVENIDO {{ (auth.state.user?.name || '').toUpperCase() }}
      </v-app-bar-title>
      <v-chip size="small" :color="esAdmin ? 'primary' : 'secondary'" class="mr-4">
        {{ esAdmin ? 'Administrador' : 'Registrador' }}
      </v-chip>
    </v-app-bar>

    <v-main>
      <v-container fluid class="pa-6">
        <!-- Aviso si cerró el modal sin cambiar la contraseña temporal -->
        <v-alert
          v-if="debeCambiar && !mostrarCambio" type="warning" variant="flat" class="mb-4"
          icon="mdi-alert"
        >
          Tienes una contraseña temporal. Debes cambiarla antes de continuar usando el sistema.
          <template #append>
            <v-btn size="small" variant="flat" color="secondary" @click="mostrarCambio = true">Cambiar ahora</v-btn>
          </template>
        </v-alert>

        <router-view />
      </v-container>
    </v-main>

    <CambioPasswordDialog v-model="mostrarCambio" />
  </v-layout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useDisplay } from 'vuetify'
import { auth } from '../store/auth'
import CambioPasswordDialog from '../components/CambioPasswordDialog.vue'

const router = useRouter()
const { lgAndUp } = useDisplay()
const drawer = ref(true)
const rail = ref(false)
const esAdmin = computed(() => auth.role() === 'admin')
const debeCambiar = computed(() => !!auth.state.user?.must_change_password)
const mostrarCambio = ref(debeCambiar.value)

function alternarMenu() {
  if (lgAndUp.value) rail.value = !rail.value
  else drawer.value = !drawer.value
}

// Menú completo para el administrador; reducido para el registrador
const menuAdmin = [
  { title: 'Inicio', icon: 'mdi-home', to: '/panel' },
  { title: 'Registrar constancia', icon: 'mdi-file-plus', to: '/panel/registrar' },
  { title: 'Constancias activas', icon: 'mdi-check-circle', to: '/panel/constancias/activas' },
  { title: 'Constancias inactivas', icon: 'mdi-close-circle', to: '/panel/constancias/inactivas' },
  { title: 'Informe PDF / CSV', icon: 'mdi-file-pdf-box', to: '/panel/informes' },
  { title: 'Gráfico por mes', icon: 'mdi-chart-bar', to: '/panel/grafico' },
  { title: 'Usuarios', icon: 'mdi-account-group', to: '/panel/usuarios' },
]
const menuRegistrador = [
  { title: 'Inicio', icon: 'mdi-home', to: '/panel' },
  { title: 'Registrar constancia', icon: 'mdi-file-plus', to: '/panel/registrar' },
  { title: 'Informe mensual PDF / CSV', icon: 'mdi-file-pdf-box', to: '/panel/informes' },
]
const menu = computed(() => (esAdmin.value ? menuAdmin : menuRegistrador))

async function salir() {
  await auth.logout()
  router.push({ name: 'home' })
}
</script>
