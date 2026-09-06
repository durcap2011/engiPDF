<script setup lang="ts">
import { computed } from 'vue'
import type { ProgressBarElement } from '../types'

defineProps<{
  element: ProgressBarElement
}>()

const style = computed(() => {
  return {
    width: '100%',
    height: '100%',
    display: 'flex',
    flexDirection: 'column' as const,
    justifyContent: 'center',
    gap: '2px',
  }
})
</script>

<template>
  <div :style="style">
    <div class="progress-track" :style="{ backgroundColor: `rgb(${element.bgColor[0] * 255}, ${element.bgColor[1] * 255}, ${element.bgColor[2] * 255})` }">
      <div class="progress-fill" :style="{
        width: Math.min(100, Math.max(0, element.value)) + '%',
        backgroundColor: `rgb(${element.color[0] * 255}, ${element.color[1] * 255}, ${element.color[2] * 255})`
      }"></div>
    </div>
    <div class="progress-label" v-if="element.label">{{ element.label }}</div>
  </div>
</template>

<style scoped>
.progress-track {
  width: 100%;
  height: 6px;
  border-radius: 3px;
  overflow: hidden;
  flex-shrink: 0;
}

.progress-fill {
  height: 100%;
  border-radius: 3px;
  transition: width 0.3s;
}

.progress-label {
  font-size: 9px;
  color: #666;
  text-align: center;
}
</style>
