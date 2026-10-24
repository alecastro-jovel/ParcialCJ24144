<template>
  <div>
    <div class="d-flex align-center mb-4">
      <h1 class="text-h5">Constancias {{ estado === 'activas' ? 'activas' : 'inactivas' }}</h1>
      <v-chip class="ml-3" :color="estado === 'activas' ? 'success' : 'grey'">{{ total }} registros</v-chip>
    </div>

    <v-card>
      <v-progress-linear v-if="loading" indeterminate color="primary" />
      <v-table hover>
        <thead>
          <tr>
            <th>ID</th><th>Empleado</th><th>Correo</th><th>Tipo</th>
            <th class="text-center">Cant.</th><th>Fecha</th><th>Estado</th><th class="text-center">Detalle</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="c in items" :key="c.id_constancias">
            <td>{{ c.id_constancias }}</td>
            <td>{{ c.nombre_empleado }}</td>
            <td>{{ c.correo }}</td>
            <td>{{ c.tipo_de_constancia }}</td>
            <td class="text-center">{{ c.cantidad }}</td>
            <td>{{ c.fecha_de_solicitud }}</td>
            <td>
              <v-chip size="small" :color="c.estado_solicitud ? 'success' : 'grey'">
                {{ c.estado_solicitud ? 'Activa' : 'Inactiva' }}
              </v-chip>
            </td>
            <td class="text-center">
              <v-btn icon="mdi-eye" size="small" variant="tonal" color="primary"
                     :to="{ name: 'detalle', params: { id: c.id_constancias } }" />
            </td>
          </tr>
          <tr v-if="!loading && !items.length">
            <td colspan="8" class="text-center pa-6 text-medium-emphasis">No hay constancias en esta lista</td>
          </tr>
        </tbody>
      </v-table>

      <!-- Paginador: 5 registros por página (los define el backend con paginate(5)) -->
      <div class="pa-4 d-flex justify-center">
        <v-pagination v-model="page" :length="lastPage" :total-visible="5" rounded="circle" />
      </div>
    </v-card>
  </div>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue'
import api from '../services/api'

const props = defineProps({ estado: String })

const items = ref([])
const page = ref(1)
const lastPage = ref(1)
const total = ref(0)
const loading = ref(false)

async function cargar() {
  loading.value = true
  try {
    const { data } = await api.get('/constancias', { params: { estado: props.estado, page: page.value } })
    items.value = data.data
    lastPage.value = data.last_page
    total.value = data.total
  } finally {
    loading.value = false
  }
}

watch(page, cargar)
watch(() => props.estado, () => {
  // al cambiar de "activas" a "inactivas" se vuelve a la página 1
  if (page.value === 1) cargar()
  else page.value = 1
})
onMounted(cargar)
</script>
