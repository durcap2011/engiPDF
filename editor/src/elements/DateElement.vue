<script setup lang="ts">
import { computed } from 'vue'
import type { DateElement } from '../types'

const props = defineProps<{
  element: DateElement
}>()

const style = computed(() => {
  const e = props.element
  const s = e.style
  const color = `rgb(${s.color[0] * 255}, ${s.color[1] * 255}, ${s.color[2] * 255})`
  const weight = s.weight === 'bold' ? 'bold' : 'normal'
  const fontStyle = s.style === 'italic' ? 'italic' : 'normal'
  const align = s.align || 'left'

  return {
    width: '100%',
    height: '100%',
    display: 'flex',
    alignItems: 'center',
    justifyContent: align === 'center' ? 'center' : align === 'right' ? 'flex-end' : 'flex-start',
    fontFamily: s.font,
    fontSize: `${s.size}pt`,
    fontWeight: weight,
    fontStyle: fontStyle,
    color: color,
    textDecoration: s.underline ? 'underline' : 'none',
  }
})
</script>

<template>
  <div :style="style">01/01/2025</div>
</template>
