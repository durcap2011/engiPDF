<script setup lang="ts">
import type { GroupElement } from '../types'

defineProps<{
  element: GroupElement
}>()
</script>

<template>
  <div class="group-element">
    <div
      v-for="child in element.children"
      :key="child.id"
      class="group-child"
      :style="{
        position: 'absolute',
        left: child.x + 'mm',
        top: child.y + 'mm',
        width: child.width + 'mm',
        height: child.height + 'mm'
      }"
    >
      <div class="child-placeholder">{{ child.type }}</div>
    </div>
  </div>
</template>

<style scoped>
.group-element {
  width: 100%;
  height: 100%;
  position: relative;
  border: 1px dashed var(--selection-color);
  background: var(--bg-accent-subtle);
}

.group-child {
  overflow: hidden;
}

.child-placeholder {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 10px;
  color: var(--text-tertiary);
  background: var(--bg-hover);
}
</style>
