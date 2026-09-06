<script setup lang="ts">
import { computed } from 'vue'
import type { CodeBlockElement } from '../types'

defineProps<{
  element: CodeBlockElement
}>()

const style = computed(() => {
  return {
    width: '100%',
    height: '100%',
    backgroundColor: '#1e1e1e',
    borderRadius: '6px',
    padding: '10px',
    boxSizing: 'border-box' as const,
    overflow: 'auto' as const,
  }
})
</script>

<template>
  <div :style="style">
    <div class="code-lang">{{ element.language }}</div>
    <pre class="code-text">{{ element.text }}</pre>
  </div>
</template>

<style scoped>
.code-lang {
  font-size: 9px;
  color: #666;
  margin-bottom: 4px;
  text-transform: uppercase;
  letter-spacing: 1px;
}

.code-text {
  font-family: 'Courier New', Courier, monospace;
  font-size: 10px;
  color: #d4d4d4;
  margin: 0;
  white-space: pre-wrap;
  word-break: break-all;
  line-height: 1.4;
}
</style>
