<script setup lang="ts">
import { computed } from 'vue'
import type { QrCodeElement } from '../types'

const props = defineProps<{
  element: QrCodeElement
}>()

const modules = computed(() => {
  const size = 21
  const grid: boolean[][] = []
  for (let r = 0; r < size; r++) {
    grid[r] = []
    for (let c = 0; c < size; c++) {
      const inFinder = (r < 7 && c < 7) || (r < 7 && c >= size - 7) || (r >= size - 7 && c < 7)
      const inBorder = inFinder && (r === 0 || r === 6 || c === 0 || c === 6 || (r >= 2 && r <= 4 && c >= 2 && c <= 4))
      const hash = ((r * 31 + c * 17 + props.element.text.length) % 7)
      grid[r][c] = inBorder || (!inFinder && hash < 3)
    }
  }
  return grid
})
</script>

<template>
  <div class="qr-container">
    <svg :viewBox="`0 0 21 21`" class="qr-svg">
      <template v-for="(row, r) in modules" :key="r">
        <rect
          v-for="(cell, c) in row"
          :key="`${r}-${c}`"
          :x="c"
          :y="r"
          width="1"
          height="1"
          :fill="cell ? '#000' : '#fff'"
        />
      </template>
    </svg>
  </div>
</template>

<style scoped>
.qr-container {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  background: white;
  padding: 4px;
  box-sizing: border-box;
}

.qr-svg {
  width: 100%;
  height: 100%;
}
</style>
