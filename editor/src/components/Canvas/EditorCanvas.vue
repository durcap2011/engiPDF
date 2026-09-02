<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useEditorStore } from '../../stores/editorStore'
import { MM_TO_PX } from '../../types'
import PageArtboard from './PageArtboard.vue'

const store = useEditorStore()
const canvasRef = ref<HTMLDivElement | null>(null)

const panX = ref(0)
const panY = ref(0)
const zoom = ref(1)
const isPanning = ref(false)
const lastMouse = ref({ x: 0, y: 0 })

const pageWidthPx = computed(() => store.document.page.width * MM_TO_PX)
const pageHeightPx = computed(() => store.document.page.height * MM_TO_PX)

const canvasStyle = computed(() => ({
  transform: `translate(${panX.value}px, ${panY.value}px) scale(${zoom.value})`,
  transformOrigin: '0 0'
}))

function handleWheel(e: WheelEvent) {
  e.preventDefault()
  if (e.ctrlKey || e.metaKey) {
    // Zoom
    const delta = e.deltaY > 0 ? 0.9 : 1.1
    zoom.value = Math.min(Math.max(zoom.value * delta, 0.2), 5)
  } else {
    // Pan
    panX.value -= e.deltaX
    panY.value -= e.deltaY
  }
}

function handleMouseDown(e: MouseEvent) {
  if (e.target === canvasRef.value || (e.target as HTMLElement).classList.contains('canvas-bg')) {
    isPanning.value = true
    lastMouse.value = { x: e.clientX, y: e.clientY }
    store.selectElement(null)
  }
}

function handleMouseMove(e: MouseEvent) {
  if (!isPanning.value) return
  panX.value += e.clientX - lastMouse.value.x
  panY.value += e.clientY - lastMouse.value.y
  lastMouse.value = { x: e.clientX, y: e.clientY }
}

function handleMouseUp() {
  isPanning.value = false
}

function centerPage() {
  if (!canvasRef.value) return
  const rect = canvasRef.value.getBoundingClientRect()
  panX.value = (rect.width - pageWidthPx.value * zoom.value) / 2
  panY.value = (rect.height - pageHeightPx.value * zoom.value) / 2
}

onMounted(() => {
  centerPage()
  window.addEventListener('mouseup', handleMouseUp)
})

onUnmounted(() => {
  window.removeEventListener('mouseup', handleMouseUp)
})
</script>

<template>
  <div
    ref="canvasRef"
    class="editor-canvas"
    :class="{ panning: isPanning }"
    @wheel.passive="handleWheel"
    @mousedown="handleMouseDown"
    @mousemove="handleMouseMove"
  >
    <div class="canvas-bg"></div>
    <div :style="canvasStyle" class="canvas-content">
      <PageArtboard :width="pageWidthPx" :height="pageHeightPx" />
    </div>
  </div>
</template>

<style scoped>
.editor-canvas {
  flex: 1;
  overflow: hidden;
  position: relative;
  background: #1a1a2e;
  background-image:
    linear-gradient(rgba(255,255,255,0.03) 1px, transparent 1px),
    linear-gradient(90deg, rgba(255,255,255,0.03) 1px, transparent 1px);
  background-size: 20px 20px;
  cursor: grab;
}

.editor-canvas.panning {
  cursor: grabbing;
}

.canvas-bg {
  position: absolute;
  inset: 0;
}

.canvas-content {
  position: absolute;
  top: 0;
  left: 0;
}
</style>
