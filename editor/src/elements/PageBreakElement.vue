<script setup lang="ts">
import type { PageBreakElement } from '../types'

defineProps<{
  element: PageBreakElement
}>()
</script>

<template>
  <div class="page-break-element">
    <div class="page-break-line"></div>
    <span class="page-break-label">Taglio Pagina</span>
    <div class="page-break-line"></div>
  </div>
</template>

<style scoped>
.page-break-element {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 0 8px;
  box-sizing: border-box;
}

.page-break-line {
  flex: 1;
  height: 0;
  border-top: 2px dashed #c00;
}

.page-break-label {
  font-size: 9px;
  color: #c00;
  white-space: nowrap;
  font-weight: bold;
  letter-spacing: 0.5px;
}
</style>
