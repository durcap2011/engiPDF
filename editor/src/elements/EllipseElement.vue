<script setup lang="ts">
import { computed } from 'vue'
import type { EllipseElement } from '../types'

const props = defineProps<{
  element: EllipseElement
}>()

const MM_TO_PX = 96 / 25.4

const style = computed(() => {
  const e = props.element
  const fill = e.fill ? `rgb(${e.fill[0] * 255}, ${e.fill[1] * 255}, ${e.fill[2] * 255})` : 'transparent'
  const stroke = e.stroke ? `rgb(${e.stroke[0] * 255}, ${e.stroke[1] * 255}, ${e.stroke[2] * 255})` : 'transparent'
  const sw = (e.strokeWidth || 0) * MM_TO_PX

  return {
    width: '100%',
    height: '100%',
    borderRadius: '50%',
    backgroundColor: fill,
    border: `${sw}px solid ${stroke}`,
    boxSizing: 'border-box' as const,
  }
})
</script>

<template>
  <div :style="style"></div>
</template>
