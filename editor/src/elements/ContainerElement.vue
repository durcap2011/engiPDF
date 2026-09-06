<script setup lang="ts">
import { computed } from 'vue'
import type { ContainerElement } from '../types'

const props = defineProps<{
  element: ContainerElement
}>()

const MM_TO_PX = 96 / 25.4

const style = computed(() => {
  const e = props.element
  const fill = e.fill ? `rgb(${e.fill[0] * 255}, ${e.fill[1] * 255}, ${e.fill[2] * 255})` : 'transparent'
  const borderColor = `rgb(${e.borderColor[0] * 255}, ${e.borderColor[1] * 255}, ${e.borderColor[2] * 255})`
  const bw = e.borderWidth * MM_TO_PX

  return {
    width: '100%',
    height: '100%',
    backgroundColor: fill,
    border: `${bw}px solid ${borderColor}`,
    boxSizing: 'border-box' as const,
    borderRadius: '2px',
  }
})
</script>

<template>
  <div :style="style"></div>
</template>
