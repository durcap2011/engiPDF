<script setup lang="ts">
import { computed, ref, onMounted, onUnmounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useEditorStore } from '../../stores/editorStore'
import { snapToGrid } from '../../utils/snapToGrid'
import type { Element } from '../../types'
import TextElement from '../../elements/TextElement.vue'
import RectangleElement from '../../elements/RectangleElement.vue'
import LineElement from '../../elements/LineElement.vue'
import ListElement from '../../elements/ListElement.vue'
import ImageElement from '../../elements/ImageElement.vue'
import TableElement from '../../elements/TableElement.vue'
import GroupElement from '../../elements/GroupElement.vue'
import EllipseElement from '../../elements/EllipseElement.vue'
import DividerElement from '../../elements/DividerElement.vue'
import SignatureElement from '../../elements/SignatureElement.vue'
import ContainerElement from '../../elements/ContainerElement.vue'
import PageNumberElement from '../../elements/PageNumberElement.vue'
import DateElement from '../../elements/DateElement.vue'
import WatermarkElement from '../../elements/WatermarkElement.vue'
import QrCodeElement from '../../elements/QrCodeElement.vue'
import SpacerElement from '../../elements/SpacerElement.vue'
import StampElement from '../../elements/StampElement.vue'
import QuoteElement from '../../elements/QuoteElement.vue'
import CalloutElement from '../../elements/CalloutElement.vue'
import CodeBlockElement from '../../elements/CodeBlockElement.vue'
import ProgressBarElement from '../../elements/ProgressBarElement.vue'
import IconElement from '../../elements/IconElement.vue'
import BarcodeElement from '../../elements/BarcodeElement.vue'
import ChartElement from '../../elements/ChartElement.vue'
import PageBreakElement from '../../elements/PageBreakElement.vue'
import DataRepeatElement from '../../elements/DataRepeatElement.vue'
import ChecklistElement from '../../elements/ChecklistElement.vue'
import RadioElement from '../../elements/RadioElement.vue'
import { evaluateShowIf, evaluateStyleIf } from '../../utils/conditionHelpers'

const props = defineProps<{
  element: Element
  selected: boolean
}>()

const emit = defineEmits<{ select: [event: MouseEvent] }>()

const { t } = useI18n()
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

// --- Conditional visibility & style ---
const sampleData = computed(() => store.document.sampleData || {})

const isVisible = computed(() => {
  return evaluateShowIf(props.element.showIf, sampleData.value as Record<string, unknown>)
})

const conditionalStyle = computed(() => {
  return evaluateStyleIf(props.element.styleIf, sampleData.value as Record<string, unknown>)
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
  const isHidden = store.isHidden(props.element.id)
  const isLocked = store.isLocked(props.element.id)

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
    cursor: isLocked ? 'not-allowed' : isDragging.value ? 'grabbing' : 'move',
    userSelect: 'none' as const,
    opacity: isHidden ? 0.3 : 1,
    pointerEvents: isLocked ? 'none' as const : 'auto' as const,
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
      const dxMm = newX - props.element.x
      const dyMm = newY - props.element.y

      if (store.selectedIds.length > 1 && store.selectedIds.includes(props.element.id)) {
        store.moveSelectedElements(dxMm, dyMm)
      } else {
        store.updateElement(props.element.id, { x: newX, y: newY })
      }
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

const isOverflowing = computed(() => store.isElementOverflowing(props.element))

const resizeCursors: Record<ResizeDir, string> = {
  n: 'ns-resize', s: 'ns-resize',
  e: 'ew-resize', w: 'ew-resize',
  ne: 'nesw-resize', sw: 'nesw-resize',
  nw: 'nwse-resize', se: 'nwse-resize',
}
</script>

<template>
  <div
    v-if="isVisible"
    ref="wrapperRef"
    :style="{ ...wrapperStyle, ...conditionalStyle }"
    @pointerdown.stop="onPointerDown"
    @click.stop="emit('select', $event)"
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
    <TableElement
      v-else-if="element.type === 'table'"
      :element="element"
      @update="(data) => store.updateElement(element.id, data)"
    />
    <GroupElement v-else-if="element.type === 'group'" :element="element" />
    <EllipseElement v-else-if="element.type === 'ellipse'" :element="element" />
    <DividerElement v-else-if="element.type === 'divider'" :element="element" />
    <SignatureElement v-else-if="element.type === 'signature'" :element="element" />
    <ContainerElement v-else-if="element.type === 'container'" :element="element" />
    <PageNumberElement v-else-if="element.type === 'pageNumber'" :element="element" />
    <DateElement v-else-if="element.type === 'date'" :element="element" />
    <WatermarkElement v-else-if="element.type === 'watermark'" :element="element" />
    <QrCodeElement v-else-if="element.type === 'qrcode'" :element="element" />
    <SpacerElement v-else-if="element.type === 'spacer'" :element="element" />
    <StampElement v-else-if="element.type === 'stamp'" :element="element" />
    <QuoteElement v-else-if="element.type === 'quote'" :element="element" />
    <CalloutElement v-else-if="element.type === 'callout'" :element="element" />
    <CodeBlockElement v-else-if="element.type === 'codeBlock'" :element="element" />
    <ProgressBarElement v-else-if="element.type === 'progressBar'" :element="element" />
    <IconElement v-else-if="element.type === 'icon'" :element="element" />
    <BarcodeElement v-else-if="element.type === 'barcode'" :element="element" />
    <ChartElement v-else-if="element.type === 'chart'" :element="element" />
    <PageBreakElement v-else-if="element.type === 'pageBreak'" :element="element" />
    <DataRepeatElement v-else-if="element.type === 'dataRepeat'" :element="element" />
    <ChecklistElement v-else-if="element.type === 'checklist'" :element="element" />
    <RadioElement v-else-if="element.type === 'radio'" :element="element" />

    <!-- Indicatore overflow -->
    <div v-if="isOverflowing" class="overflow-indicator">
      <span class="overflow-badge">{{ t('element.outOfPage') }}</span>
      <button class="overflow-move-btn" @click.stop="store.moveElementToNextPage(element.id)" :title="t('element.moveToNext')">→ {{ t('element.pagePlus') }}</button>
    </div>

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
  background: var(--selection-color);
  border: 1.5px solid var(--bg-elevated);
  border-radius: 2px;
  z-index: 10;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.15);
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

.overflow-indicator {
  position: absolute;
  top: -28px;
  left: 0;
  display: flex;
  align-items: center;
  gap: 4px;
  z-index: 20;
}

.overflow-badge {
  background: var(--bg-danger);
  color: var(--text-inverse);
  font-size: 10px;
  padding: 2px 8px;
  border-radius: var(--radius-sm);
  white-space: nowrap;
  font-weight: 500;
}

.overflow-move-btn {
  background: var(--bg-accent);
  color: var(--text-inverse);
  border: none;
  font-size: 10px;
  padding: 2px 8px;
  border-radius: var(--radius-sm);
  cursor: pointer;
  white-space: nowrap;
  font-weight: 500;
  font-family: inherit;
  transition: background 0.15s ease;
}

.overflow-move-btn:hover {
  background: var(--bg-accent-hover);
}
</style>
