<script setup lang="ts">
import { computed } from 'vue'
import type { LineElement } from '../types'

const props = defineProps<{
  element: LineElement
}>()

const style = computed(() => {
  const e = props.element
  const color = `rgb(${e.color[0] * 255}, ${e.color[1] * 255}, ${e.color[2] * 255})`
  const widthPx = e.lineWidth * (96 / 25.4)

  return {
    width: '100%',
    height: `${widthPx}px`,
    backgroundColor: color,
    marginTop: '0',
  }
})
</script>

<template>
  <div :style="style"></div>
</template>
