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

function mmToPx(mm: number): number {
  return mm * MM_TO_PX
}

const horizontalMarks = computed(() => {
  const result: { px: number; mm: number; major: boolean }[] = []
  for (let mm = 0; mm <= store.document.page.width; mm += 1) {
    if (mm % 5 !== 0) continue
    const major = mm % 10 === 0
    result.push({ px: mmToPx(mm), mm, major })
  }
  return result
})

const verticalMarks = computed(() => {
  const result: { px: number; mm: number; major: boolean }[] = []
  for (let mm = 0; mm <= store.document.page.height; mm += 1) {
    if (mm % 5 !== 0) continue
    const major = mm % 10 === 0
    result.push({ px: mmToPx(mm), mm, major })
  }
  return result
})

function handleWheel(e: WheelEvent) {
  e.preventDefault()
  if (e.ctrlKey || e.metaKey) {
    const delta = e.deltaY > 0 ? 0.9 : 1.1
    zoom.value = Math.min(Math.max(zoom.value * delta, 0.2), 5)
  } else {
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
      <div class="ruler-and-page">
        <div class="page-row">
          <div class="ruler-vertical" :style="{ height: pageHeightPx + 'px' }">
            <svg :width="20" :height="pageHeightPx">
              <line x1="19" y1="0" x2="19" :y2="pageHeightPx" stroke="#888" stroke-width="1" />
              <template v-for="mark in verticalMarks" :key="'v' + mark.mm">
                <line
                  :x1="mark.major ? 0 : 12"
                  :y1="mark.px"
                  x2="19"
                  :y2="mark.px"
                  stroke="#888"
                  stroke-width="1"
                />
                <text
                  v-if="mark.major"
                  x="10"
                  :y="mark.px - 3"
                  fill="#999"
                  font-size="9"
                  font-family="monospace"
                  writing-mode="vertical-rl"
                  transform="rotate(180deg)"
                  transform-origin="center"
                >{{ mark.mm / 10 }}</text>
              </template>
            </svg>
          </div>

          <div class="page-column">
            <div class="ruler-horizontal" :style="{ width: pageWidthPx + 'px' }">
              <svg :width="pageWidthPx" :height="20">
                <line x1="0" y1="19" :x2="pageWidthPx" y2="19" stroke="#888" stroke-width="1" />
                <template v-for="mark in horizontalMarks" :key="'h' + mark.mm">
                  <line
                    :x1="mark.px"
                    :y1="mark.major ? 0 : 12"
                    :x2="mark.px"
                    y2="19"
                    stroke="#888"
                    stroke-width="1"
                  />
                  <text
                    v-if="mark.major"
                    :x="mark.px + 3"
                    y="11"
                    fill="#999"
                    font-size="10"
                    font-family="monospace"
                  >{{ mark.mm / 10 }}</text>
                </template>
              </svg>
            </div>

            <PageArtboard :width="pageWidthPx" :height="pageHeightPx" />
          </div>
        </div>
      </div>
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

.ruler-and-page {
  display: inline-block;
}

.page-row {
  display: flex;
}

.ruler-vertical {
  width: 20px;
  margin-top: 20px;
  background: #2a2a3e;
  border-right: 1px solid #444;
  overflow: hidden;
}

.ruler-vertical svg {
  display: block;
}

.page-column {
  display: inline-flex;
  flex-direction: column;
}

.ruler-horizontal {
  height: 20px;
  background: #2a2a3e;
  border-bottom: 1px solid #444;
  overflow: hidden;
}

.ruler-horizontal svg {
  display: block;
}
</style>
