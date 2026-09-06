<script setup lang="ts">
import { computed } from 'vue'
import type { SignatureElement } from '../types'

defineProps<{
  element: SignatureElement
}>()

const style = computed(() => {
  return {
    width: '100%',
    height: '100%',
    display: 'flex',
    flexDirection: 'column' as const,
    justifyContent: 'flex-end',
    alignItems: 'center',
    gap: '4px',
  }
})
</script>

<template>
  <div :style="style">
    <div class="signature-line" :style="{ borderColor: `rgb(${element.color[0] * 255}, ${element.color[1] * 255}, ${element.color[2] * 255})` }"></div>
    <span class="signature-label" :style="{ color: `rgb(${element.color[0] * 255}, ${element.color[1] * 255}, ${element.color[2] * 255})` }">{{ element.label }}</span>
  </div>
</template>

<style scoped>
.signature-line {
  width: 100%;
  border-bottom: 1px dashed;
  margin-bottom: 2px;
}

.signature-label {
  font-size: 10px;
  text-align: center;
}
</style>
