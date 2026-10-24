<template>
  <v-form ref="formRef" @submit.prevent="enviar">
    <v-row>
      <v-col cols="12" md="6">
        <v-text-field
          v-model="form.nombre_empleado" label="Nombre del empleado" prepend-inner-icon="mdi-account"
          :rules="rules.nombre" :error-messages="errors?.nombre_empleado" counter maxlength="100"
        />
      </v-col>
      <v-col cols="12" md="6">
        <v-text-field
          v-model="form.correo" label="Correo" type="email" prepend-inner-icon="mdi-email"
          :rules="rules.correo" :error-messages="errors?.correo"
        />
      </v-col>
      <v-col cols="12" md="6">
        <v-text-field
          v-model="form.movil" label="Móvil (8 dígitos)" prepend-inner-icon="mdi-cellphone"
          :rules="rules.movil" :error-messages="errors?.movil" counter maxlength="8"
        />
      </v-col>
      <v-col cols="12" md="6">
        <v-select
          v-model="form.tipo_de_constancia" :items="tipos" label="Tipo de constancia"
          prepend-inner-icon="mdi-file-document" :rules="rules.tipo" :error-messages="errors?.tipo_de_constancia"
        />
      </v-col>
      <v-col cols="12" md="9">
        <v-textarea
          v-model="form.descripcion" label="Descripción" rows="2" auto-grow prepend-inner-icon="mdi-text"
          :rules="rules.descripcion" :error-messages="errors?.descripcion" counter maxlength="255"
        />
      </v-col>
      <v-col cols="12" md="3">
        <v-text-field
          v-model="form.cantidad" label="Cantidad" type="number" min="1" max="10" prepend-inner-icon="mdi-counter"
          :rules="rules.cantidad" :error-messages="errors?.cantidad"
        />
      </v-col>

      <v-col v-if="showEstado" cols="12">
        <v-switch
          v-model="form.estado_solicitud" color="success" hide-details
          :label="form.estado_solicitud ? 'Estado: Activa (solicitada)' : 'Estado: Inactiva (lista)'"
        />
      </v-col>
    </v-row>

    <v-alert v-if="!showEstado" type="info" variant="tonal" density="compact" class="mb-4">
      La fecha, la hora y el estado de la solicitud se generan automáticamente al guardar.
    </v-alert>

    <div class="d-flex ga-2">
      <v-btn type="submit" color="primary" :loading="loading" prepend-icon="mdi-content-save">{{ submitText }}</v-btn>
      <v-btn v-if="cancelable" variant="text" @click="$emit('cancel')">Cancelar</v-btn>
    </div>
  </v-form>
</template>

<script setup>
import { reactive, ref } from 'vue'

const props = defineProps({
  initial: { type: Object, default: () => ({}) },
  submitText: { type: String, default: 'Guardar' },
  loading: Boolean,
  errors: { type: Object, default: () => ({}) }, // errores 422 que devuelve Laravel
  showEstado: Boolean,
  cancelable: Boolean,
})
const emit = defineEmits(['submit', 'cancel'])

const tipos = [
  'Constancia de trabajo',
  'Constancia de salario',
  'Constancia de antigüedad',
  'Constancia de vacaciones',
  'Constancia de estudios',
]

const form = reactive({
  nombre_empleado: '',
  correo: '',
  movil: '',
  tipo_de_constancia: null,
  descripcion: '',
  cantidad: 1,
  estado_solicitud: true,
  ...props.initial,
})
form.movil = String(form.movil ?? '')

const formRef = ref(null)

// ---- Validaciones del lado del cliente (el servidor las repite) ----
const req = (v) => (v !== null && v !== undefined && v !== '') || 'Campo obligatorio'
const rules = {
  nombre: [
    req,
    (v) => (v || '').length >= 3 || 'Mínimo 3 caracteres',
    (v) => /^[\p{L}\s.'-]+$/u.test(v || '') || 'Solo se permiten letras',
  ],
  correo: [req, (v) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v || '') || 'Correo inválido'],
  movil: [req, (v) => /^\d{8}$/.test(v || '') || 'Debe tener exactamente 8 dígitos'],
  tipo: [req],
  descripcion: [
    req,
    (v) => (v || '').length >= 5 || 'Mínimo 5 caracteres',
    (v) => (v || '').length <= 255 || 'Máximo 255 caracteres',
  ],
  cantidad: [
    req,
    (v) => (Number.isInteger(Number(v)) && Number(v) >= 1 && Number(v) <= 10) || 'Debe ser un entero entre 1 y 10',
  ],
}

async function enviar() {
  const { valid } = await formRef.value.validate()
  if (!valid) return
  const payload = {
    nombre_empleado: form.nombre_empleado.trim(),
    correo: form.correo.trim(),
    movil: form.movil,
    tipo_de_constancia: form.tipo_de_constancia,
    descripcion: form.descripcion.trim(),
    cantidad: Number(form.cantidad),
  }
  if (props.showEstado) payload.estado_solicitud = form.estado_solicitud
  emit('submit', payload)
}
</script>
