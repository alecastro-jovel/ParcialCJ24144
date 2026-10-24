<template>
  <!-- 6 casillas para el código del autenticador -->
  <div class="codigo" @paste.prevent="pegar">
    <input
      v-for="i in 6" :key="i"
      :ref="(el) => (cajas[i - 1] = el)"
      class="caja"
      :class="{ 'caja-error': error }"
      type="text" inputmode="numeric" maxlength="1" autocomplete="one-time-code"
      :value="valores[i - 1]"
      :aria-label="`Dígito ${i}`"
      @input="escribir(i - 1, $event)"
      @keydown="tecla(i - 1, $event)"
      @focus="$event.target.select()"
    />
  </div>
</template>

<script setup>
import { ref, watch } from 'vue'

const props = defineProps({ modelValue: { type: String, default: '' }, error: Boolean })
const emit = defineEmits(['update:modelValue', 'complete'])

const cajas = []
const valores = ref(Array.from({ length: 6 }, (_, i) => props.modelValue[i] || ''))

// Si desde afuera se limpia o cambia el código, se refleja en las casillas
watch(() => props.modelValue, (v) => {
  if (v !== valores.value.join('')) valores.value = Array.from({ length: 6 }, (_, i) => v[i] || '')
})

function emitir() {
  const codigo = valores.value.join('')
  emit('update:modelValue', codigo)
  if (codigo.length === 6) emit('complete', codigo)
}

function escribir(i, e) {
  const d = e.target.value.replace(/\D/g, '').slice(-1)
  e.target.value = d
  valores.value[i] = d
  emitir()
  if (d && i < 5) cajas[i + 1]?.focus()
}

function tecla(i, e) {
  if (e.key === 'Backspace' && !valores.value[i] && i > 0) {
    valores.value[i - 1] = ''
    cajas[i - 1]?.focus()
    emitir()
  } else if (e.key === 'ArrowLeft' && i > 0) cajas[i - 1]?.focus()
  else if (e.key === 'ArrowRight' && i < 5) cajas[i + 1]?.focus()
}

function pegar(e) {
  const d = (e.clipboardData?.getData('text') || '').replace(/\D/g, '').slice(0, 6)
  if (!d) return
  valores.value = Array.from({ length: 6 }, (_, i) => d[i] || '')
  emitir()
  cajas[Math.min(d.length, 5)]?.focus()
}

defineExpose({ focus: () => cajas[0]?.focus() })
</script>

<style scoped>
.codigo { display: flex; justify-content: center; gap: 8px; }
.caja {
  width: 40px; height: 48px; text-align: center; font-size: 22px; font-weight: 700;
  background: #fff; color: #313945; border: 1px solid #b8c2dc; border-radius: 4px; outline: none;
}
.caja:focus { border-color: #2d52a8; box-shadow: 0 0 0 2px rgba(45, 82, 168, 0.25); }
.caja-error { border-color: #ef5350; }
@media (max-width: 380px) { .caja { width: 34px; height: 44px; } }
</style>
