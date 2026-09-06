<script setup lang="ts">
import { computed } from 'vue'
import type { CalloutElement } from '../types'

const props = defineProps<{
  element: CalloutElement
}>()

const styleIcons: Record<string, string> = {
  info: 'ℹ',
  warning: '⚠',
  error: '✕',
  success: '✓',
}

const style = computed(() => {
  const e = props.element
  const bg = `rgb(${e.bgColor[0] * 255}, ${e.bgColor[1] * 255}, ${e.bgColor[2] * 255})`
  const border = `rgb(${e.borderColor[0] * 255}, ${e.borderColor[1] * 255}, ${e.borderColor[2] * 255})`

  return {
    width: '100%',
    height: '100%',
    display: 'flex',
    flexDirection: 'row' as const,
    alignItems: 'center',
    gap: '8px',
    padding: '6px 10px',
    backgroundColor: bg,
    borderLeft: `4px solid ${border}`,
    borderRadius: '4px',
    fontSize: '11px',
    boxSizing: 'border-box' as const,
  }
})
</script>

<template>
  <div :style="style">
    <span class="callout-icon" :style="{ color: `rgb(${element.borderColor[0] * 255}, ${element.borderColor[1] * 255}, ${element.borderColor[2] * 255})` }">{{ element.icon || styleIcons[element.style] }}</span>
    <span class="callout-text">{{ element.text }}</span>
  </div>
</template>

<style scoped>
.callout-icon {
  font-size: 16px;
  font-weight: bold;
  flex-shrink: 0;
}

.callout-text {
  line-height: 1.3;
  min-width: 0;
}
</style>
