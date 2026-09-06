<script setup lang="ts">
import { computed } from 'vue'
import type { DividerElement } from '../types'

const props = defineProps<{
  element: DividerElement
}>()

const MM_TO_PX = 96 / 25.4

const style = computed(() => {
  const e = props.element
  const color = `rgb(${e.color[0] * 255}, ${e.color[1] * 255}, ${e.color[2] * 255})`
  const lw = e.lineWidth * MM_TO_PX

  const borderStyle = e.lineStyle === 'dashed' ? 'dashed' : e.lineStyle === 'dotted' ? 'dotted' : 'solid'

  return {
    width: '100%',
    height: `${lw}px`,
    borderBottom: `${lw}px ${borderStyle} ${color}`,
    marginTop: '0',
  }
})
</script>

<template>
  <div :style="style"></div>
</template>
