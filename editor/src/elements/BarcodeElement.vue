<script setup lang="ts">
import { computed } from 'vue'
import type { BarcodeElement } from '../types'

const props = defineProps<{
  element: BarcodeElement
}>()

const barcodePatterns: Record<string, (text: string) => number[]> = {
  code128: (text: string) => {
    const bars: number[] = []
    for (let i = 0; i < text.length; i++) {
      const code = text.charCodeAt(i) % 2 === 0 ? 1 : 0
      bars.push(code, code ? 0 : 1, code ? 1 : 0, code ? 0 : 1)
    }
    return bars
  },
  code39: (text: string) => {
    const bars: number[] = []
    for (let i = 0; i < text.length; i++) {
      bars.push(1, 0, 1, 1, 0, 1, 0, 0, 1)
    }
    return bars
  },
  ean13: (text: string) => {
    const bars: number[] = []
    for (let i = 0; i < 13; i++) {
      const n = parseInt(text[i] || '0', 10)
      bars.push(n % 2 === 0 ? 1 : 0, n % 3 === 0 ? 1 : 0)
    }
    return bars
  },
}

const bars = computed(() => {
  const gen = barcodePatterns[props.element.format] || barcodePatterns.code128
  return gen(props.element.text)
})
</script>

<template>
  <div class="barcode-container">
    <svg class="barcode-svg" :viewBox="`0 0 ${bars.length} 100`" preserveAspectRatio="none">
      <template v-for="(bar, idx) in bars" :key="idx">
        <rect
          v-if="bar"
          :x="idx"
          y="0"
          width="1"
          height="80"
          fill="#000"
        />
      </template>
    </svg>
    <div class="barcode-text" v-if="element.showText">{{ element.text }}</div>
  </div>
</template>

<style scoped>
.barcode-container {
  width: 100%;
  height: 100%;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 2px;
  background: white;
  padding: 4px;
  box-sizing: border-box;
}

.barcode-svg {
  width: 100%;
  flex: 1;
}

.barcode-text {
  font-size: 9px;
  font-family: monospace;
  color: #000;
  text-align: center;
}
</style>
