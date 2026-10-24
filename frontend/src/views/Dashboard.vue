<template>
  <div>
    <h1 class="text-h5 font-weight-bold mb-1">Inicio · Bienvenido, {{ auth.state.user?.name }}</h1>
    <p class="text-medium-emphasis mb-6">¿Qué deseas hacer hoy?</p>

    <v-row>
      <v-col v-for="c in cards" :key="c.to" cols="12" sm="6" md="4">
        <v-card :to="c.to" hover class="pa-5 h-100">
          <v-icon size="40" :color="c.color">{{ c.icon }}</v-icon>
          <div class="text-h6 mt-3">{{ c.title }}</div>
          <div class="text-body-2 text-medium-emphasis">{{ c.text }}</div>
        </v-card>
      </v-col>
    </v-row>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { auth } from '../store/auth'

const todas = [
  { title: 'Registrar constancia', text: 'Nueva solicitud de empleado', icon: 'mdi-file-plus', color: 'primary', to: '/panel/registrar', roles: ['admin', 'registrador'] },
  { title: 'Constancias activas', text: 'Solicitudes pendientes', icon: 'mdi-check-circle', color: 'success', to: '/panel/constancias/activas', roles: ['admin'] },
  { title: 'Constancias inactivas', text: 'Solicitudes ya atendidas', icon: 'mdi-close-circle', color: 'grey', to: '/panel/constancias/inactivas', roles: ['admin'] },
  { title: 'Informe PDF / CSV', text: 'Constancias registradas por mes', icon: 'mdi-file-pdf-box', color: 'error', to: '/panel/informes', roles: ['admin', 'registrador'] },
  { title: 'Usuarios', text: 'Listado, registro y estado de usuarios', icon: 'mdi-account-group', color: 'secondary', to: '/panel/usuarios', roles: ['admin'] },
  { title: 'Gráfico por mes', text: 'Activas vs inactivas', icon: 'mdi-chart-bar', color: 'secondary', to: '/panel/grafico', roles: ['admin'] },
]
const cards = computed(() => todas.filter((c) => c.roles.includes(auth.role())))
</script>
