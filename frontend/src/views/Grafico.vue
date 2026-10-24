<template>
  <div>
    <h1 class="text-h5 mb-4">Gráfico: constancias activas vs inactivas por mes</h1>

    <v-card class="pa-4 mb-4">
      <v-row align="center">
        <v-col cols="12" sm="4">
          <v-select v-model="mes" :items="meses" label="Mes" hide-details />
        </v-col>
        <v-col cols="12" sm="4">
          <v-select v-model="anio" :items="anios" label="Año" hide-details />
        </v-col>
        <v-col cols="12" sm="4">
          <v-btn color="primary" block size="large" prepend-icon="mdi-chart-bar" :loading="loading" @click="generar">
            Generar gráfico
          </v-btn>
        </v-col>
      </v-row>
    </v-card>

    <v-row v-if="resultado">
      <v-col cols="12" md="8">
        <v-card class="pa-4">
          <canvas ref="canvas" height="120"></canvas>
        </v-card>
      </v-col>
      <v-col cols="12" md="4">
        <v-card class="pa-4 mb-3 text-center">
          <div class="text-h4 text-success">{{ resultado.activas }}</div>
          <div class="text-medium-emphasis">Activas (solicitadas)</div>
        </v-card>
        <v-card class="pa-4 mb-3 text-center">
          <div class="text-h4 text-grey-darken-1">{{ resultado.inactivas }}</div>
          <div class="text-medium-emphasis">Inactivas</div>
        </v-card>
        <v-card class="pa-4 text-center">
          <div class="text-h4 text-primary">{{ resultado.total }}</div>
          <div class="text-medium-emphasis">Total en {{ resultado.nombre_mes }} {{ resultado.anio }}</div>
        </v-card>
      </v-col>
    </v-row>
  </div>
</template>

<script setup>
import { ref, nextTick, onMounted, onBeforeUnmount } from 'vue'
import Chart from 'chart.js/auto'
import api from '../services/api'

const nombres = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre']
const meses = nombres.map((title, i) => ({ title, value: i + 1 }))

const hoy = new Date()
const anio = ref(hoy.getFullYear())
const mes = ref(hoy.getMonth() + 1)
const anios = Array.from({ length: 4 }, (_, i) => hoy.getFullYear() - i)

const canvas = ref(null)
const resultado = ref(null)
const loading = ref(false)
let chart = null

async function generar() {
  loading.value = true
  try {
    const { data } = await api.get('/informes/grafico', { params: { anio: anio.value, mes: mes.value } })
    resultado.value = data
    await nextTick() // esperar a que el <canvas> exista en el DOM
    dibujar(data)
  } finally {
    loading.value = false
  }
}

function dibujar(d) {
  if (chart) chart.destroy()
  chart = new Chart(canvas.value, {
    type: 'bar',
    data: {
      labels: ['Activas (solicitadas)', 'Inactivas'],
      datasets: [{
        label: `Constancias de ${d.nombre_mes} ${d.anio}`,
        data: [d.activas, d.inactivas],
        backgroundColor: ['#2E7D32', '#757575'],
        borderRadius: 6,
      }],
    },
    options: {
      responsive: true,
      scales: { y: { beginAtZero: true, ticks: { precision: 0 } } },
    },
  })
}

onMounted(generar)
onBeforeUnmount(() => chart?.destroy())
</script>
