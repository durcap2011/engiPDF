<script setup lang="ts">
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

interface ComponentItem {
  type: string
  label: string
  icon: string
}

interface Category {
  id: string
  label: string
  icon: string
  items: ComponentItem[]
}

const categories: Category[] = [
  {
    id: 'shapes',
    label: 'Forme base',
    icon: '◇',
    items: [
      { type: 'rectangle', label: 'Rettangolo', icon: '▭' },
      { type: 'ellipse', label: 'Cerchio', icon: '◯' },
      { type: 'line', label: 'Linea', icon: '—' },
      { type: 'divider', label: 'Divisore', icon: '─' },
    ]
  },
  {
    id: 'text',
    label: 'Testo',
    icon: 'T',
    items: [
      { type: 'text', label: 'Testo', icon: 'T' },
      { type: 'list', label: 'Lista', icon: '≡' },
      { type: 'checklist', label: 'Checklist', icon: '☑' },
      { type: 'radio', label: 'Radio', icon: '◉' },
      { type: 'quote', label: 'Citazione', icon: '❝' },
      { type: 'callout', label: 'Callout', icon: '💬' },
      { type: 'codeBlock', label: 'Blocco codice', icon: '⟨⟩' },
    ]
  },
  {
    id: 'data',
    label: 'Dati',
    icon: '⊞',
    items: [
      { type: 'table', label: 'Tabella', icon: '⊞' },
      { type: 'barcode', label: 'Barcode', icon: '║' },
      { type: 'qrcode', label: 'QR Code', icon: '◫' },
      { type: 'chart', label: 'Grafico', icon: '📊' },
    ]
  },
  {
    id: 'media',
    label: 'Media',
    icon: '🖼',
    items: [
      { type: 'image', label: 'Immagine', icon: '🖼' },
      { type: 'icon', label: 'Icona', icon: '★' },
    ]
  },
  {
    id: 'layout',
    label: 'Layout',
    icon: '□',
    items: [
      { type: 'container', label: 'Contenitore', icon: '□' },
      { type: 'group', label: 'Gruppo', icon: '▦' },
      { type: 'spacer', label: 'Spaziatore', icon: '↕' },
      { type: 'pageBreak', label: 'Taglio pagina', icon: '⚓' },
      { type: 'dataRepeat', label: 'Ripeti dati', icon: '↻' },
    ]
  },
  {
    id: 'dynamic',
    label: 'Dinamici',
    icon: '#',
    items: [
      { type: 'pageNumber', label: 'N. Pagina', icon: '#' },
      { type: 'date', label: 'Data', icon: '📅' },
      { type: 'progressBar', label: 'Avanzamento', icon: '▰' },
    ]
  },
  {
    id: 'style',
    label: 'Stile',
    icon: '✦',
    items: [
      { type: 'watermark', label: 'Watermark', icon: '💧' },
      { type: 'stamp', label: 'Timbro', icon: '✓' },
      { type: 'callout', label: 'Callout', icon: 'ℹ' },
      { type: 'signature', label: 'Firma', icon: '✍' },
    ]
  },
]

const openCategories = ref<Set<string>>(new Set(['shapes']))

function toggleCategory(id: string) {
  if (openCategories.value.has(id)) {
    openCategories.value.delete(id)
  } else {
    openCategories.value.add(id)
  }
}

function onDragStart(e: DragEvent, type: string) {
  e.dataTransfer?.setData('component-type', type)
  e.dataTransfer!.effectAllowed = 'copy'
}

const categoryLabelMap: Record<string, string> = {
  shapes: 'palette.shapes',
  text: 'palette.text',
  data: 'palette.data',
  media: 'palette.media',
  layout: 'palette.layout',
  dynamic: 'palette.dynamic',
  style: 'palette.style',
}

const componentLabelMap: Record<string, string> = {
  rectangle: 'palette.rectangle',
  ellipse: 'palette.ellipse',
  line: 'palette.line',
  divider: 'palette.divider',
  text: 'palette.textEl',
  list: 'palette.list',
  checklist: 'palette.checklist',
  radio: 'palette.radio',
  quote: 'palette.quote',
  callout: 'palette.callout',
  codeBlock: 'palette.codeBlock',
  table: 'palette.table',
  barcode: 'palette.barcode',
  qrcode: 'palette.qrcode',
  chart: 'palette.chart',
  image: 'palette.image',
  icon: 'palette.icon',
  container: 'palette.container',
  group: 'palette.group',
  spacer: 'palette.spacer',
  pageBreak: 'palette.pageBreak',
  dataRepeat: 'palette.dataRepeat',
  pageNumber: 'palette.pageNumber',
  date: 'palette.date',
  progressBar: 'palette.progressBar',
  watermark: 'palette.watermark',
  stamp: 'palette.stamp',
  signature: 'palette.signature',
}

function getCategoryLabel(id: string): string {
  const key = categoryLabelMap[id]
  return key ? t(key) : id
}

function getComponentLabel(type: string): string {
  const key = componentLabelMap[type]
  return key ? t(key) : type
}
</script>

<template>
  <div class="palette">
    <h3 class="palette-title">{{ t('palette.title') }}</h3>
    <div
      v-for="cat in categories"
      :key="cat.id"
      class="category"
    >
      <button
        class="category-header"
        :class="{ open: openCategories.has(cat.id) }"
        @click="toggleCategory(cat.id)"
      >
        <span class="category-icon">{{ cat.icon }}</span>
        <span class="category-label">{{ getCategoryLabel(cat.id) }}</span>
        <svg class="category-arrow" :class="{ rotated: openCategories.has(cat.id) }" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9l6 6 6-6"/></svg>
      </button>
      <Transition name="accordion">
        <div v-show="openCategories.has(cat.id)" class="category-items">
          <div
            v-for="comp in cat.items"
            :key="comp.type"
            class="palette-item"
            draggable="true"
            @dragstart="onDragStart($event, comp.type)"
          >
            <span class="item-icon">{{ comp.icon }}</span>
            <span class="item-label">{{ getComponentLabel(comp.type) }}</span>
          </div>
        </div>
      </Transition>
    </div>
  </div>
</template>

<style scoped>
.palette {
  width: 160px;
  background: var(--bg-surface);
  border-right: 1px solid var(--border-default);
  padding: 12px 8px;
  overflow-y: auto;
}

.palette-title {
  font-size: 10px;
  text-transform: uppercase;
  letter-spacing: 1.2px;
  color: var(--text-tertiary);
  margin-bottom: 10px;
  padding-left: 6px;
  font-weight: 600;
}

.category {
  margin-bottom: 2px;
}

.category-header {
  display: flex;
  align-items: center;
  gap: 6px;
  width: 100%;
  padding: 6px 6px;
  border: none;
  background: transparent;
  border-radius: var(--radius-sm);
  cursor: pointer;
  transition: all 0.15s ease;
  color: var(--text-secondary);
  font-size: 11px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.category-header:hover {
  background: var(--bg-hover);
  color: var(--text-primary);
}

.category-header.open {
  color: var(--text-accent);
}

.category-icon {
  font-size: 11px;
  width: 16px;
  text-align: center;
  flex-shrink: 0;
  opacity: 0.7;
}

.category-label {
  flex: 1;
  text-align: left;
}

.category-arrow {
  color: var(--text-tertiary);
  transition: transform 0.2s ease;
  flex-shrink: 0;
}

.category-arrow.rotated {
  transform: rotate(180deg);
}

.category-items {
  padding-left: 2px;
}

.palette-item {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 6px 8px 6px 22px;
  border-radius: var(--radius-sm);
  cursor: grab;
  transition: all 0.15s ease;
  margin-bottom: 1px;
}

.palette-item:hover {
  background: var(--bg-hover);
}

.palette-item:active {
  cursor: grabbing;
  background: var(--bg-active);
}

.item-icon {
  font-size: 13px;
  width: 18px;
  text-align: center;
  flex-shrink: 0;
  color: var(--text-tertiary);
}

.item-label {
  font-size: 12px;
  font-weight: 400;
  color: var(--text-primary);
}

.accordion-enter-active,
.accordion-leave-active {
  transition: all 0.2s ease;
  overflow: hidden;
}

.accordion-enter-from,
.accordion-leave-to {
  opacity: 0;
  max-height: 0;
}

.accordion-enter-to,
.accordion-leave-from {
  opacity: 1;
  max-height: 500px;
}
</style>
