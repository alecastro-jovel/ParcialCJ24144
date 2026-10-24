<template>
  <svg :width="size" :height="size" viewBox="0 0 100 100" fill="none" role="img" aria-label="Logo ULS">
    <g v-for="(giro, i) in [0, 120, 240]" :key="i" :transform="`rotate(${giro} 50 50)`">
      <path d="M50 14 A36 36 0 0 1 81 32" :stroke="colores[i]" stroke-width="9" stroke-linecap="round" />
      <path d="M72 30 L86 33 L80 20 Z" :fill="colores[i]" :stroke="colores[i]" stroke-width="3" stroke-linejoin="round" />
    </g>
  </svg>
</template>

<script setup>
defineProps({ size: { type: [Number, String], default: 72 } })
const colores = ['#2D52A8', '#5C7BD1', '#A9B8E6']
</script>
