<script setup lang="ts">
import type { RadioElement, RGB } from '../types'

defineProps<{
  element: RadioElement
}>()

function rgb(color: RGB): string {
  return `rgb(${Math.round(color[0] * 255)}, ${Math.round(color[1] * 255)}, ${Math.round(color[2] * 255)})`
}
</script>

<template>
  <div class="radio" :style="{ gap: element.gap + 'px' }">
    <div
      v-for="(item, idx) in element.items"
      :key="idx"
      class="radio-item"
    >
      <span class="radio-circle" :style="{
        borderColor: rgb(item.selected ? element.selectedColor : element.color),
        backgroundColor: item.selected ? rgb(element.selectedColor) : 'transparent',
      }">
        <span v-if="item.selected" class="radio-dot" :style="{ backgroundColor: rgb(element.selectedColor) }"></span>
      </span>
      <span class="radio-text" :style="{
        fontSize: element.size + 'px',
        color: rgb(element.color),
        opacity: item.selected ? 1 : 0.8,
      }">{{ item.text }}</span>
    </div>
  </div>
</template>

<style scoped>
.radio {
  display: flex;
  flex-direction: column;
}

.radio-item {
  display: flex;
  align-items: center;
  gap: 6px;
}

.radio-circle {
  width: 14px;
  height: 14px;
  min-width: 14px;
  border: 2px solid;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  box-sizing: border-box;
}

.radio-dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
}

.radio-text {
  line-height: 1.2;
}
</style>
