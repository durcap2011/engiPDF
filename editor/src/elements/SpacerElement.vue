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
  border: 1px dashed rgba(128, 128, 128, 0.3);
  background: repeating-linear-gradient(
    45deg,
    transparent,
    transparent 4px,
    rgba(128, 128, 128, 0.05) 4px,
    rgba(128, 128, 128, 0.05) 8px
  );
}
</style>
