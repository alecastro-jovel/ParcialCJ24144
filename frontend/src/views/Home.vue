<template>
  <v-main class="fondo-acceso">
    <v-container class="fill-height" style="max-width: 900px">
      <v-row justify="center" align="center">
        <v-col cols="12" class="text-center text-white mb-4">
          <LogoUls :size="84" />
          <h1 class="text-h4 font-weight-bold mt-2">Sistema de Gestión de Constancias o Notas ULS</h1>
          <p class="text-subtitle-1">Selecciona tu tipo de acceso</p>
        </v-col>

        <v-col cols="12" sm="6">
          <v-card class="pa-6 text-center" hover :to="{ name: 'login', params: { rol: 'admin' } }">
            <v-icon size="56" color="primary">mdi-shield-account</v-icon>
            <h2 class="text-h6 mt-3">Administrador</h2>
            <p class="text-body-2 text-medium-emphasis">Gestión completa, informes y gráficos</p>
          </v-card>
        </v-col>

        <v-col cols="12" sm="6">
          <v-card class="pa-6 text-center" hover :to="{ name: 'login', params: { rol: 'registrador' } }">
            <v-icon size="56" color="secondary">mdi-account-edit</v-icon>
            <h2 class="text-h6 mt-3">Registrador</h2>
            <p class="text-body-2 text-medium-emphasis">Registrar constancias e informe mensual</p>
          </v-card>
        </v-col>
      </v-row>
    </v-container>
  </v-main>
</template>

<script setup>
import LogoUls from '../components/LogoUls.vue'
</script>
