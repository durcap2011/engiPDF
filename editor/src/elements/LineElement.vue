<script setup lang="ts">
import { computed } from 'vue'
import type { LineElement } from '../types'

const props = defineProps<{
  element: LineElement
}>()

const MM_TO_PX = 96 / 25.4

const style = computed(() => {
  const e = props.element
  const color = `rgb(${e.color[0] * 255}, ${e.color[1] * 255}, ${e.color[2] * 255})`
  const widthPx = e.lineWidth * MM_TO_PX

  const dx = e.x2 - e.x
  const dy = e.y2 - e.y
  const lengthPx = Math.sqrt(dx * dx + dy * dy) * MM_TO_PX
  const angle = Math.atan2(dy, dx) * (180 / Math.PI)

  return {
    width: `${lengthPx}px`,
    height: `${widthPx}px`,
    backgroundColor: color,
    transformOrigin: '0 50%',
    transform: `rotate(${angle}deg)`,
    position: 'absolute' as const,
    left: '0',
    top: `${(e.height * MM_TO_PX - widthPx) / 2}px`,
  }
})
</script>

<template>
  <div :style="style"></div>
</template>
