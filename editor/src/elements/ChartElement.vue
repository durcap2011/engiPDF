<script setup lang="ts">
import { computed } from 'vue'
import type { ChartElement } from '../types'

const props = defineProps<{
  element: ChartElement
}>()

const maxValue = computed(() => {
  return Math.max(...props.element.data.map(d => d.value), 1)
})

const barData = computed(() => {
  return props.element.data.map((d, i) => ({
    ...d,
    color: props.element.colors[i % props.element.colors.length],
    height: (d.value / maxValue.value) * 100,
  }))
})

const pieData = computed(() => {
  const total = props.element.data.reduce((sum, d) => sum + d.value, 0)
  let cumPercent = 0
  return props.element.data.map((d, i) => {
    const percent = (d.value / total) * 100
    const start = cumPercent
    cumPercent += percent
    return {
      ...d,
      color: props.element.colors[i % props.element.colors.length],
      start,
      end: cumPercent,
    }
  })
})
</script>

<template>
  <div class="chart-container">
    <svg class="chart-svg" viewBox="0 0 100 100" preserveAspectRatio="none">
      <template v-if="element.chartType === 'bar'">
        <template v-for="(bar, idx) in barData" :key="idx">
          <rect
            :x="(idx / element.data.length) * 100 + 2"
            :y="100 - bar.height"
            :width="(1 / element.data.length) * 100 - 4"
            :height="bar.height"
            :fill="`rgb(${bar.color[0] * 255}, ${bar.color[1] * 255}, ${bar.color[2] * 255})`"
            rx="1"
          />
        </template>
      </template>
      <template v-else-if="element.chartType === 'line'">
        <polyline
          :points="barData.map((bar, idx) => `${(idx / (element.data.length - 1 || 1)) * 100},${100 - bar.height}`).join(' ')"
          fill="none"
          :stroke="`rgb(${element.colors[0]?.[0] * 255 || 0}, ${element.colors[0]?.[1] * 255 || 0}, ${element.colors[0]?.[2] * 255 || 0})`"
          stroke-width="2"
          stroke-linecap="round"
          stroke-linejoin="round"
        />
        <template v-for="(bar, idx) in barData" :key="idx">
          <circle
            :cx="(idx / (element.data.length - 1 || 1)) * 100"
            :cy="100 - bar.height"
            r="2"
            :fill="`rgb(${bar.color[0] * 255}, ${bar.color[1] * 255}, ${bar.color[2] * 255})`"
          />
        </template>
      </template>
      <template v-else-if="element.chartType === 'pie'">
        <template v-for="slice in pieData" :key="slice.label">
          <circle
            cx="50" cy="50" r="45"
            fill="none"
            :stroke="`rgb(${slice.color[0] * 255}, ${slice.color[1] * 255}, ${slice.color[2] * 255})`"
            stroke-width="10"
            :stroke-dasharray="`${(slice.end - slice.start) * 2.827} ${282.7 - (slice.end - slice.start) * 2.827}`"
            :stroke-dashoffset="`${-slice.start * 2.827 + 70.7}`"
          />
        </template>
      </template>
    </svg>
    <div class="chart-legend">
      <template v-for="(d, idx) in element.data" :key="idx">
        <span class="legend-item">
          <span class="legend-color" :style="{ backgroundColor: `rgb(${element.colors[idx % element.colors.length][0] * 255}, ${element.colors[idx % element.colors.length][1] * 255}, ${element.colors[idx % element.colors.length][2] * 255})` }"></span>
          {{ d.label }}
        </span>
      </template>
    </div>
  </div>
</template>

<style scoped>
.chart-container {
  width: 100%;
  height: 100%;
  display: flex;
  flex-direction: column;
  gap: 4px;
  padding: 4px;
  box-sizing: border-box;
}

.chart-svg {
  flex: 1;
  width: 100%;
}

.chart-legend {
  display: flex;
  gap: 8px;
  justify-content: center;
  flex-wrap: wrap;
}

.legend-item {
  display: flex;
  align-items: center;
  gap: 3px;
  font-size: 8px;
  color: #666;
}

.legend-color {
  width: 8px;
  height: 8px;
  border-radius: 2px;
  flex-shrink: 0;
}
</style>
