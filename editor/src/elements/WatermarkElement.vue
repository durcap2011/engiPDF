<script setup lang="ts">
import { computed } from 'vue'
import type { WatermarkElement } from '../types'

const props = defineProps<{
  element: WatermarkElement
}>()

const style = computed(() => {
  const e = props.element
  const color = `rgba(${Math.round(e.color[0] * 255)}, ${Math.round(e.color[1] * 255)}, ${Math.round(e.color[2] * 255)}, ${e.opacity})`

  return {
    width: '100%',
    height: '100%',
    display: 'flex',
    alignItems: 'center',
    justifyContent: 'center',
    fontSize: `${e.fontSize}pt`,
    fontWeight: 'bold',
    color: color,
    transform: `rotate(${e.rotation}deg)`,
    whiteSpace: 'nowrap' as const,
    pointerEvents: 'none' as const,
    userSelect: 'none' as const,
  }
})
</script>

<template>
  <div :style="style">{{ element.text }}</div>
</template>
