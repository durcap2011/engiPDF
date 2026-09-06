<script setup lang="ts">
import { ref, computed } from 'vue'
import { useEditorStore } from '../../stores/editorStore'
import { getDefaultElement } from '../../utils/getDefaultElement'
import { snapToGrid } from '../../utils/snapToGrid'
import ElementWrapper from './ElementWrapper.vue'
import type { Page } from '../../types'

const props = defineProps<{
  page: Page
  width: number
  height: number
  isActive: boolean
}>()

const emit = defineEmits<{
  selectPage: []
}>()

const store = useEditorStore()
const isDragOver = ref(false)
const MM_TO_PX = 96 / 25.4

const headerHeightPx = computed(() => props.page.settings.headerHeight * MM_TO_PX)
const footerHeightPx = computed(() => props.page.settings.footerHeight * MM_TO_PX)
const pageHeightPx = computed(() => props.page.settings.height * MM_TO_PX)

const marginTopPx = computed(() => props.page.settings.margins.top * MM_TO_PX)
const marginBottomPx = computed(() => props.page.settings.margins.bottom * MM_TO_PX)
const marginLeftPx = computed(() => props.page.settings.margins.left * MM_TO_PX)
const marginRightPx = computed(() => props.page.settings.margins.right * MM_TO_PX)

const pageElements = computed(() => {
  return store.document.elements.filter(el => el.pageId === props.page.id)
})

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

  const el = getDefaultElement(type, xMm, yMm, props.page.id)
  store.addElement(el)
}

function onPageClick() {
  store.clearSelection()
  emit('selectPage')
}
</script>

<template>
  <div
    class="page-artboard"
    :class="{
      'drag-over': isDragOver,
      'is-active': isActive
    }"
    :style="{ width: width + 'px', height: height + 'px' }"
    @click="onPageClick"
    @dragover="onDragOver"
    @dragleave="onDragLeave"
    @drop="onDrop"
  >
    <div class="page-label">Pagina {{ store.document.pages.indexOf(page) + 1 }}</div>
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
    <div class="margin-guide margin-top" :style="{ top: marginTopPx + 'px', left: marginLeftPx + 'px', right: marginRightPx + 'px' }"></div>
    <div class="margin-guide margin-bottom" :style="{ bottom: marginBottomPx + 'px', left: marginLeftPx + 'px', right: marginRightPx + 'px' }"></div>
    <div class="margin-guide margin-left" :style="{ top: marginTopPx + 'px', bottom: marginBottomPx + 'px', left: marginLeftPx + 'px' }"></div>
    <div class="margin-guide margin-right" :style="{ top: marginTopPx + 'px', bottom: marginBottomPx + 'px', right: marginRightPx + 'px' }"></div>
    <ElementWrapper
      v-for="el in pageElements"
      :key="el.id"
      :element="el"
      :selected="store.selectedIds.includes(el.id)"
      @select="(e: MouseEvent) => {
        if (e.shiftKey) {
          store.toggleSelection(el.id)
        } else {
          store.selectElement(el.id)
        }
      }"
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

.page-artboard.is-active {
  outline: 2px solid #4A90D9;
  outline-offset: 2px;
}

.page-artboard.drag-over {
  outline: 2px dashed #4A90D9;
  outline-offset: -2px;
}

.page-label {
  position: absolute;
  top: 4px;
  right: 8px;
  font-size: 10px;
  color: #999;
  font-family: monospace;
  pointer-events: none;
  z-index: 200;
}

.guide-line {
  position: absolute;
  left: 0;
  height: 0;
  border-top: 2px dashed rgba(74, 144, 217, 0.8);
  pointer-events: none;
  z-index: 100;
}

.margin-guide {
  position: absolute;
  pointer-events: none;
  z-index: 90;
}

.margin-top, .margin-bottom {
  height: 0;
  border-top: 2px dashed rgba(74, 144, 217, 0.8);
}

.margin-left, .margin-right {
  width: 0;
  border-left: 2px dashed rgba(74, 144, 217, 0.8);
}
</style>
