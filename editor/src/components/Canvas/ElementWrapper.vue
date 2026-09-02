<script setup lang="ts">
import { computed, ref, onMounted, onUnmounted } from 'vue'
import { useEditorStore } from '../../stores/editorStore'
import { snapToGrid } from '../../utils/snapToGrid'
import type { Element } from '../../types'
import TextElement from '../../elements/TextElement.vue'
import RectangleElement from '../../elements/RectangleElement.vue'
import LineElement from '../../elements/LineElement.vue'
import ListElement from '../../elements/ListElement.vue'
import ImageElement from '../../elements/ImageElement.vue'

const props = defineProps<{
  element: Element
  selected: boolean
}>()

const emit = defineEmits<{ select: [] }>()

const store = useEditorStore()
const MM_TO_PX = 96 / 25.4

const wrapperRef = ref<HTMLDivElement | null>(null)
let resizeObserver: ResizeObserver | null = null

onMounted(() => {
  if (props.element.type === 'list' && wrapperRef.value) {
    resizeObserver = new ResizeObserver((entries) => {
      for (const entry of entries) {
        const newHeightPx = entry.contentRect.height
        const newHeightMm = Math.ceil(newHeightPx / MM_TO_PX)
        if (Math.abs(newHeightMm - props.element.height) > 0.5) {
          store.updateElement(props.element.id, { height: newHeightMm })
        }
      }
    })
    resizeObserver.observe(wrapperRef.value)
  }
})

onUnmounted(() => {
  resizeObserver?.disconnect()
})

// --- Drag ---
const isDragging = ref(false)
const dragStart = ref({ x: 0, y: 0 })
const dragOffset = ref({ x: 0, y: 0 })

// --- Resize ---
type ResizeDir = 'n' | 's' | 'e' | 'w' | 'ne' | 'nw' | 'se' | 'sw'
const isResizing = ref(false)
const resizeDir = ref<ResizeDir>('e')
const resizeStart = ref({ x: 0, y: 0 })
const resizeOriginal = ref({ x: 0, y: 0, width: 0, height: 0 })

const wrapperStyle = computed(() => {
  const dragX = isDragging.value ? dragOffset.value.x / MM_TO_PX : 0
  const dragY = isDragging.value ? dragOffset.value.y / MM_TO_PX : 0
  const isList = props.element.type === 'list'

  return {
    position: 'absolute' as const,
    left: `${(props.element.x + dragX) * MM_TO_PX}px`,
    top: `${(props.element.y + dragY) * MM_TO_PX}px`,
    width: `${props.element.width * MM_TO_PX}px`,
    height: isList ? 'auto' : `${props.element.height * MM_TO_PX}px`,
    minHeight: isList ? undefined : undefined,
    outline: isDragging.value
      ? '2px dashed #4A90D9'
      : props.selected
        ? '2px solid #4A90D9'
        : 'none',
    outlineOffset: '1px',
    cursor: isDragging.value ? 'grabbing' : 'move',
    userSelect: 'none' as const,
  }
})

function onPointerDown(e: PointerEvent) {
  isDragging.value = true
  dragStart.value = { x: e.clientX, y: e.clientY }
  dragOffset.value = { x: 0, y: 0 }

  const onMove = (ev: PointerEvent) => {
    if (!isDragging.value) return
    dragOffset.value = {
      x: ev.clientX - dragStart.value.x,
      y: ev.clientY - dragStart.value.y
    }
  }

  const onUp = () => {
    if (isDragging.value && (dragOffset.value.x !== 0 || dragOffset.value.y !== 0)) {
      const newX = snapToGrid(
        props.element.x + dragOffset.value.x / MM_TO_PX,
        store.gridSize
      )
      const newY = snapToGrid(
        props.element.y + dragOffset.value.y / MM_TO_PX,
        store.gridSize
      )
      store.updateElement(props.element.id, { x: newX, y: newY })
    }
    isDragging.value = false
    window.removeEventListener('pointermove', onMove)
    window.removeEventListener('pointerup', onUp)
  }

  window.addEventListener('pointermove', onMove)
  window.addEventListener('pointerup', onUp)
}

function onResizeStart(dir: ResizeDir, e: PointerEvent) {
  e.stopPropagation()
  e.preventDefault()
  isResizing.value = true
  resizeDir.value = dir
  resizeStart.value = { x: e.clientX, y: e.clientY }
  resizeOriginal.value = {
    x: props.element.x,
    y: props.element.y,
    width: props.element.width,
    height: props.element.height
  }

  const onMove = (ev: PointerEvent) => {
    if (!isResizing.value) return

    const dx = (ev.clientX - resizeStart.value.x) / MM_TO_PX
    const dy = (ev.clientY - resizeStart.value.y) / MM_TO_PX
    const orig = resizeOriginal.value
    const minSize = 5

    let newX = orig.x
    let newY = orig.y
    let newW = orig.width
    let newH = orig.height

    const dir = resizeDir.value

    // Calcola nuova dimensione in base alla direzione
    if (dir.includes('e')) {
      newW = Math.max(minSize, orig.width + dx)
    }
    if (dir.includes('w')) {
      newW = Math.max(minSize, orig.width - dx)
      newX = orig.x + (orig.width - newW)
    }
    if (dir.includes('s')) {
      newH = Math.max(minSize, orig.height + dy)
    }
    if (dir.includes('n')) {
      newH = Math.max(minSize, orig.height - dy)
      newY = orig.y + (orig.height - newH)
    }

    // Snap to grid
    newX = snapToGrid(newX, store.gridSize)
    newY = snapToGrid(newY, store.gridSize)
    newW = snapToGrid(newW, store.gridSize)
    newH = snapToGrid(newH, store.gridSize)

    store.updateElement(props.element.id, {
      x: newX, y: newY, width: newW, height: newH
    })
  }

  const onUp = () => {
    isResizing.value = false
    window.removeEventListener('pointermove', onMove)
    window.removeEventListener('pointerup', onUp)
  }

  window.addEventListener('pointermove', onMove)
  window.addEventListener('pointerup', onUp)
}

function onHandleDoubleClick() {
  store.autoFitElement(props.element.id)
}

const resizeCursors: Record<ResizeDir, string> = {
  n: 'ns-resize', s: 'ns-resize',
  e: 'ew-resize', w: 'ew-resize',
  ne: 'nesw-resize', sw: 'nesw-resize',
  nw: 'nwse-resize', se: 'nwse-resize',
}
</script>

<template>
  <div
    ref="wrapperRef"
    :style="wrapperStyle"
    @pointerdown.stop="onPointerDown"
    @click.stop="emit('select')"
  >
    <TextElement
      v-if="element.type === 'text'"
      :element="element"
      @update="(t: string) => store.updateElement(element.id, { text: t })"
    />
    <RectangleElement v-else-if="element.type === 'rectangle'" :element="element" />
    <LineElement v-else-if="element.type === 'line'" :element="element" />
    <ListElement
      v-else-if="element.type === 'list'"
      :element="element"
      @update="(items) => store.updateElement(element.id, { items })"
    />
    <ImageElement v-else-if="element.type === 'image'" :element="element" />

    <!-- Maniglie di resize -->
    <template v-if="selected && !isDragging">
      <!-- Angoli -->
      <div
        v-for="dir in (['nw','ne','sw','se'] as ResizeDir[])"
        :key="dir"
        class="resize-handle corner"
        :class="dir"
        :style="{ cursor: resizeCursors[dir] }"
        @pointerdown.stop="onResizeStart(dir, $event)"
        @dblclick.stop="onHandleDoubleClick"
      />
      <!-- Lati -->
      <div
        v-for="dir in (['n','s','e','w'] as ResizeDir[])"
        :key="dir"
        class="resize-handle edge"
        :class="dir"
        :style="{ cursor: resizeCursors[dir] }"
        @pointerdown.stop="onResizeStart(dir, $event)"
        @dblclick.stop="onHandleDoubleClick"
      />
    </template>
  </div>
</template>

<style scoped>
.resize-handle {
  position: absolute;
  background: #4A90D9;
  border: 1px solid #fff;
  border-radius: 2px;
  z-index: 10;
}

/* Angoli */
.resize-handle.corner {
  width: 10px;
  height: 10px;
}
.resize-handle.corner.nw { top: -5px; left: -5px; }
.resize-handle.corner.ne { top: -5px; right: -5px; }
.resize-handle.corner.sw { bottom: -5px; left: -5px; }
.resize-handle.corner.se { bottom: -5px; right: -5px; }

/* Lati */
.resize-handle.edge {
  z-index: 11;
}
.resize-handle.edge.n,
.resize-handle.edge.s {
  left: 50%;
  transform: translateX(-50%);
  width: 24px;
  height: 6px;
}
.resize-handle.edge.e,
.resize-handle.edge.w {
  top: 50%;
  transform: translateY(-50%);
  width: 6px;
  height: 24px;
}

.resize-handle.edge.n { top: -3px; }
.resize-handle.edge.s { bottom: -3px; }
.resize-handle.edge.e { right: -3px; }
.resize-handle.edge.w { left: -3px; }
</style>
