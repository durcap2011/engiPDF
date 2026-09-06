<script setup lang="ts">
import { ref } from 'vue'

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
</script>

<template>
  <div class="palette">
    <h3 class="palette-title">Componenti</h3>
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
        <span class="category-label">{{ cat.label }}</span>
        <span class="category-arrow">{{ openCategories.has(cat.id) ? '▾' : '▸' }}</span>
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
            <span class="item-label">{{ comp.label }}</span>
          </div>
        </div>
      </Transition>
    </div>
  </div>
</template>

<style scoped>
.palette {
  width: 160px;
  background: #16213e;
  border-right: 1px solid #0f3460;
  padding: 12px 8px;
  overflow-y: auto;
}

.palette-title {
  font-size: 11px;
  text-transform: uppercase;
  letter-spacing: 1px;
  color: #666;
  margin-bottom: 10px;
  padding-left: 4px;
}

.category {
  margin-bottom: 4px;
}

.category-header {
  display: flex;
  align-items: center;
  gap: 6px;
  width: 100%;
  padding: 6px 6px;
  border: none;
  background: transparent;
  border-radius: 4px;
  cursor: pointer;
  transition: background 0.15s;
  color: #aaa;
  font-size: 11px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.category-header:hover {
  background: rgba(255, 255, 255, 0.05);
  color: #ddd;
}

.category-header.open {
  color: #4fc3f7;
}

.category-icon {
  font-size: 12px;
  width: 16px;
  text-align: center;
  flex-shrink: 0;
}

.category-label {
  flex: 1;
  text-align: left;
}

.category-arrow {
  font-size: 10px;
  color: #666;
  transition: transform 0.2s;
}

.category-items {
  padding-left: 4px;
}

.palette-item {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 6px 6px 6px 22px;
  border-radius: 5px;
  cursor: grab;
  transition: background 0.15s;
  margin-bottom: 1px;
}

.palette-item:hover {
  background: #0f3460;
}

.palette-item:active {
  cursor: grabbing;
}

.item-icon {
  font-size: 14px;
  width: 20px;
  text-align: center;
  flex-shrink: 0;
  opacity: 0.7;
}

.item-label {
  font-size: 12px;
  font-weight: 400;
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
