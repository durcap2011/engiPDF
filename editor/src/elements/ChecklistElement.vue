<script setup lang="ts">
import type { ChecklistElement, RGB } from '../types'

defineProps<{
  element: ChecklistElement
}>()

function rgb(color: RGB): string {
  return `rgb(${Math.round(color[0] * 255)}, ${Math.round(color[1] * 255)}, ${Math.round(color[2] * 255)})`
}
</script>

<template>
  <div class="checklist" :style="{ gap: element.gap + 'px' }">
    <div
      v-for="(item, idx) in element.items"
      :key="idx"
      class="checklist-item"
    >
      <span class="checklist-box" :style="{
        borderColor: rgb(item.checked ? element.checkedColor : element.color),
        backgroundColor: item.checked ? rgb(element.checkedColor) : 'transparent',
      }">
        <svg v-if="item.checked" width="10" height="10" viewBox="0 0 10 10">
          <path d="M2 5l2 2 4-4" stroke="white" stroke-width="1.5" fill="none" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
      </span>
      <span class="checklist-text" :style="{
        fontSize: element.size + 'px',
        color: rgb(element.color),
        opacity: item.checked ? 0.5 : 1,
        textDecoration: item.checked ? 'line-through' : 'none',
      }">{{ item.text }}</span>
    </div>
  </div>
</template>

<style scoped>
.checklist {
  display: flex;
  flex-direction: column;
}

.checklist-item {
  display: flex;
  align-items: center;
  gap: 6px;
}

.checklist-box {
  width: 14px;
  height: 14px;
  min-width: 14px;
  border: 2px solid;
  border-radius: 2px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.checklist-text {
  line-height: 1.2;
}
</style>
