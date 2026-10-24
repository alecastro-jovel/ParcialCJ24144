<template>
  <div>
    <h1 class="text-h5 mb-4">Registrar constancia</h1>
    <v-card class="pa-6" max-width="900">
      <ConstanciaForm
        :key="formKey" :loading="loading" :errors="errors"
        submit-text="Registrar constancia" @submit="guardar"
      />
    </v-card>

    <v-snackbar v-model="snack.show" :color="snack.color" timeout="3500">{{ snack.text }}</v-snackbar>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue'
import api from '../services/api'
import ConstanciaForm from '../components/ConstanciaForm.vue'

const loading = ref(false)
const errors = ref({})
const formKey = ref(0) // al cambiarlo se reinicia el formulario
const snack = reactive({ show: false, text: '', color: 'success' })

async function guardar(payload) {
  loading.value = true
  errors.value = {}
  try {
    const { data } = await api.post('/constancias', payload)
    snack.text = `Constancia #${data.id_constancias} registrada correctamente`
    snack.color = 'success'
    formKey.value++
  } catch (e) {
    if (e.response?.status === 422) {
      errors.value = e.response.data.errors || {}
      snack.text = 'Revisa los campos marcados'
    } else {
      snack.text = e.response?.data?.message || 'Error al registrar'
    }
    snack.color = 'error'
  } finally {
    loading.value = false
    snack.show = true
  }
}
</script>
