<template>
  <div>
    <h1 class="text-h5 font-weight-bold mb-4">Informe mensual - Impresión</h1>

    <div class="d-flex flex-wrap ga-2 mb-4">
      <v-btn color="secondary" prepend-icon="mdi-refresh" :loading="loading" @click="cargarPdf">Generar PDF</v-btn>
      <v-btn color="error" variant="tonal" prepend-icon="mdi-download" :disabled="!pdfUrl" @click="descargarPdf">Descargar PDF</v-btn>
      <v-btn color="success" variant="tonal" prepend-icon="mdi-file-delimited" :loading="csvLoading" @click="descargarCsv">Descargar CSV</v-btn>
    </div>

    <v-alert v-if="error" type="error" variant="tonal" class="mb-4">{{ error }}</v-alert>

    <!-- PDF VIEWER: el PDF se muestra embebido con el visor del navegador -->
    <v-card class="visor-pdf" theme="dark">
      <v-card-title class="text-subtitle-1"><v-icon class="mr-1">mdi-file-pdf-box</v-icon>Visor de PDF</v-card-title>
      <v-progress-linear v-if="loading" indeterminate />
      <iframe v-if="pdfUrl" :src="pdfUrl" class="visor" title="Informe PDF"></iframe>
      <div v-else-if="!loading" class="pa-8 text-center">Presiona «Generar PDF» para ver el informe</div>
    </v-card>
  </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue'
import api from '../services/api'

const pdfUrl = ref('')
const loading = ref(false)
const csvLoading = ref(false)
const error = ref('')

function guardarArchivo(blob, nombre) {
  const url = URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = nombre
  a.click()
  URL.revokeObjectURL(url)
}

async function cargarPdf() {
  loading.value = true
  error.value = ''
  try {
    // Se pide como "blob" (archivo binario) porque el iframe no puede enviar el token
    const { data } = await api.get('/informes/mensual/pdf', { responseType: 'blob' })
    if (pdfUrl.value) URL.revokeObjectURL(pdfUrl.value)
    pdfUrl.value = URL.createObjectURL(new Blob([data], { type: 'application/pdf' }))
  } catch (e) {
    error.value = 'No se pudo generar el PDF. Revisa que el backend esté corriendo.'
  } finally {
    loading.value = false
  }
}

function descargarPdf() {
  fetch(pdfUrl.value).then((r) => r.blob()).then((b) => guardarArchivo(b, 'informe_constancias_mensual.pdf'))
}

async function descargarCsv() {
  csvLoading.value = true
  error.value = ''
  try {
    const { data } = await api.get('/informes/mensual/csv', { responseType: 'blob' })
    guardarArchivo(new Blob([data], { type: 'text/csv;charset=utf-8' }), 'informe_constancias_mensual.csv')
  } catch (e) {
    error.value = 'No se pudo generar el CSV.'
  } finally {
    csvLoading.value = false
  }
}

onMounted(cargarPdf)
onBeforeUnmount(() => { if (pdfUrl.value) URL.revokeObjectURL(pdfUrl.value) })
</script>

<style scoped>
.visor { width: 100%; height: 75vh; border: 0; }
</style>
