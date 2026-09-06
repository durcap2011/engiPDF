<script setup lang="ts">
import { computed } from 'vue'
import type { DataRepeatElement, Element } from '../types'
import { useEditorStore } from '../stores/editorStore'
import ElementWrapper from '../components/Canvas/ElementWrapper.vue'

const props = defineProps<{
  element: DataRepeatElement
}>()

const store = useEditorStore()

const repeatData = computed(() => {
  const field = props.element.repeatField
  const data = (store.document.sampleData || {}) as Record<string, unknown>
  const value = data[field]
  if (Array.isArray(value)) {
    return value.map((item, index) => ({ _index: index, ...item as Record<string, unknown> }))
  }
  return []
})

function resolveChild(child: Element, item: Record<string, unknown>, index: number): Element {
  const resolved = { ...child }

  if ('text' in resolved && typeof resolved.text === 'string') {
    resolved.text = resolved.text
      .replace(/\{\{\s*_index\s*\}\}/g, String(index))
      .replace(/\{\{\s*item\.(\w+)\s*\}\}/g, (_: string, key: string) => {
        return item[key] !== undefined ? String(item[key]) : ''
      })
  }

  return resolved
}
</script>

<template>
  <div
    class="data-repeat-container"
    :style="{
      display: 'flex',
      flexDirection: element.direction === 'horizontal' ? 'row' : 'column',
      gap: element.spacing + 'px',
    }"
  >
    <template v-for="(item, index) in repeatData" :key="index">
      <div class="repeat-item" :style="{ position: 'relative' }">
        <template v-for="child in element.children" :key="child.id">
          <ElementWrapper
            :element="resolveChild(child, item, index)"
            :selected="false"
            @select="() => {}"
          />
        </template>
      </div>
    </template>
    <div v-if="repeatData.length === 0" class="repeat-empty">
      Nessun dato per "{{ element.repeatField }}"
    </div>
  </div>
</template>

<style scoped>
.data-repeat-container {
  width: 100%;
  min-height: 20px;
}

.repeat-empty {
  font-size: 10px;
  color: #999;
  font-style: italic;
  padding: 8px;
  border: 1px dashed #ccc;
  border-radius: 4px;
}
</style>
