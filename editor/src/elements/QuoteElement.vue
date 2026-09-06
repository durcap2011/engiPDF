<script setup lang="ts">
import { computed } from 'vue'
import type { QuoteElement } from '../types'

defineProps<{
  element: QuoteElement
}>()

const style = computed(() => {
  return {
    width: '100%',
    height: '100%',
    display: 'flex',
    flexDirection: 'row' as const,
    gap: '8px',
    paddingLeft: '12px',
  }
})
</script>

<template>
  <div :style="style">
    <div class="quote-bar" :style="{ backgroundColor: `rgb(${element.barColor[0] * 255}, ${element.barColor[1] * 255}, ${element.barColor[2] * 255})` }"></div>
    <div class="quote-content">
      <div class="quote-text" :style="{
        fontFamily: element.style.font,
        fontSize: element.style.size + 'pt',
        fontStyle: element.style.style === 'italic' ? 'italic' : 'normal',
        color: `rgb(${element.style.color[0] * 255}, ${element.style.color[1] * 255}, ${element.style.color[2] * 255})`
      }">{{ element.text }}</div>
      <div class="quote-author" v-if="element.author">— {{ element.author }}</div>
    </div>
  </div>
</template>

<style scoped>
.quote-bar {
  width: 4px;
  flex-shrink: 0;
  border-radius: 2px;
}

.quote-content {
  display: flex;
  flex-direction: column;
  gap: 4px;
  min-width: 0;
}

.quote-text {
  line-height: 1.4;
}

.quote-author {
  font-size: 10px;
  color: #888;
  font-style: italic;
}
</style>
