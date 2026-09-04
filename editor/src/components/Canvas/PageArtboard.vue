<script setup lang="ts">
import { ref, computed } from 'vue'
import { useEditorStore } from '../../stores/editorStore'
import { getDefaultElement } from '../../utils/getDefaultElement'
import { snapToGrid } from '../../utils/snapToGrid'
import ElementWrapper from './ElementWrapper.vue'

defineProps<{
  width: number
  height: number
}>()

const store = useEditorStore()
const isDragOver = ref(false)
const MM_TO_PX = 96 / 25.4

const headerHeightPx = computed(() => store.document.page.headerHeight * MM_TO_PX)
const footerHeightPx = computed(() => store.document.page.footerHeight * MM_TO_PX)
const pageHeightPx = computed(() => store.document.page.height * MM_TO_PX)

function onDragOver(e: DragEvent) {
  if (e.dataTransfer?.types.includes('component-type')) {
    e.preventDefault()
    e.dataTransfer.dropEffect = 'copy'
    isDragOver.value = true
  }
}

function onDragLeave() {
  isDragOver.value = false
}

function onDrop(e: DragEvent) {
  isDragOver.value = false
  const type = e.dataTransfer?.getData('component-type')
  if (!type) return

  const rect = (e.currentTarget as HTMLElement).getBoundingClientRect()
  const dropX = e.clientX - rect.left
  const dropY = e.clientY - rect.top

  const xMm = snapToGrid(dropX / MM_TO_PX, store.gridSize)
  const yMm = snapToGrid(dropY / MM_TO_PX, store.gridSize)

  const el = getDefaultElement(type, xMm, yMm)
  store.addElement(el)
}
</script>

<template>
  <div
    class="page-artboard"
    :class="{ 'drag-over': isDragOver }"
    :style="{ width: width + 'px', height: height + 'px' }"
    @click="store.selectElement(null)"
    @dragover="onDragOver"
    @dragleave="onDragLeave"
    @drop="onDrop"
  >
    <div
      v-if="headerHeightPx > 0"
      class="guide-line header-line"
      :style="{ top: headerHeightPx + 'px', width: '100%' }"
    ></div>
    <div
      v-if="footerHeightPx > 0"
      class="guide-line footer-line"
      :style="{ top: (pageHeightPx - footerHeightPx) + 'px', width: '100%' }"
    ></div>
    <ElementWrapper
      v-for="el in store.document.elements"
      :key="el.id"
      :element="el"
      :selected="el.id === store.selectedId"
      @select="store.selectElement(el.id)"
    />
  </div>
</template>

<style scoped>
.page-artboard {
  background: white;
  box-shadow: 0 4px 24px rgba(0, 0, 0, 0.4);
  position: relative;
  overflow: hidden;
  transition: outline 0.15s;
}

.page-artboard.drag-over {
  outline: 2px dashed #4A90D9;
  outline-offset: -2px;
}

.guide-line {
  position: absolute;
  left: 0;
  height: 0;
  border-top: 2px dashed rgba(74, 144, 217, 0.8);
  pointer-events: none;
  z-index: 100;
}
</style>
