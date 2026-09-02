<script setup lang="ts">
import { computed } from 'vue'
import type { RectangleElement } from '../types'

const props = defineProps<{
  element: RectangleElement
}>()

const style = computed(() => {
  const e = props.element
  const fill = e.fill ? `rgb(${e.fill[0] * 255}, ${e.fill[1] * 255}, ${e.fill[2] * 255})` : 'transparent'
  const stroke = e.stroke ? `rgb(${e.stroke[0] * 255}, ${e.stroke[1] * 255}, ${e.stroke[2] * 255})` : 'transparent'
  const border = e.strokeWidth ? `${e.strokeWidth * (96 / 25.4)}px solid ${stroke}` : 'none'

  return {
    width: '100%',
    height: '100%',
    backgroundColor: fill,
    border,
    boxSizing: 'border-box' as const,
  }
})
</script>

<template>
  <div :style="style"></div>
</template>
