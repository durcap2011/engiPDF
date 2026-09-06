<script setup lang="ts">
import type { SpacerElement } from '../types'

defineProps<{
  element: SpacerElement
}>()
</script>

<template>
  <div class="spacer-element"></div>
</template>

<style scoped>
.spacer-element {
  width: 100%;
  height: 100%;
  border: 1px dashed var(--border-subtle);
  background: repeating-linear-gradient(
    45deg,
    transparent,
    transparent 4px,
    var(--canvas-grid) 4px,
    var(--canvas-grid) 8px
  );
}
</style>
