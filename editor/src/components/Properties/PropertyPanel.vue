<script setup lang="ts">
import { computed, ref } from 'vue'
import { useEditorStore } from '../../stores/editorStore'
import { BULLET_TYPES } from '../../utils/bulletTypes'
import { flattenSampleData } from '../../utils/fieldOptions'
import type { ListItem } from '../../types'

const store = useEditorStore()

const selected = computed(() => store.getSelectedElement())
const multiSelected = computed(() => store.selectedIds.length > 1)
const imageFileInput = ref<HTMLInputElement | null>(null)
const expandedHeader = ref<number | null>(null)
const expandedCell = ref<number | null>(null)
const customField = ref<Record<string, boolean>>({})

const fieldOptions = computed(() => {
  return flattenSampleData(store.document.sampleData || {})
})

function update(key: string, value: unknown) {
  if (!selected.value) return
  store.updateElement(selected.value.id, { [key]: value })
}

function updateStyle(key: string, value: unknown) {
  if (!selected.value) return
  const currentStyle = (selected.value as any).style || {}
  update('style', { ...currentStyle, [key]: value })
}

function updatePage(key: string, value: unknown) {
  store.updatePageSettings({ [key]: value })
}

function updateMargin(side: string, value: number) {
  const current = store.activePage.settings.margins
  store.updatePageSettings({ margins: { ...current, [side]: value } })
}

function updateItems(idx: number, text: string) {
  if (!selected.value || selected.value.type !== 'list') return
  const items = [...(selected.value as any).items]
  items[idx] = { ...items[idx], text }
  update('items', items)
}

function triggerImageUpload() {
  imageFileInput.value?.click()
}

function onImageFileChange(e: Event) {
  const input = e.target as HTMLInputElement
  const file = input.files?.[0]
  if (!file) return

  const reader = new FileReader()
  reader.onload = () => {
    update('src', reader.result as string)
  }
  reader.readAsDataURL(file)

  input.value = ''
}

const maxListLevel = computed(() => {
  if (!selected.value || selected.value.type !== 'list') return 0
  const items = (selected.value as any).items as ListItem[]

  function getDepth(items: ListItem[], depth: number): number {
    let max = depth
    for (const item of items) {
      if (item.items && item.items.length > 0) {
        const d = getDepth(item.items, depth + 1)
        if (d > max) max = d
      }
    }
    return max
  }

  return getDepth(items, 0)
})

function updateBulletForLevel(level: number, bulletId: string) {
  if (!selected.value || selected.value.type !== 'list') return
  const currentStyle = (selected.value as any).style || {}
  const bullets = [...(currentStyle.bullets || [])]
  while (bullets.length <= level) {
    bullets.push('circle')
  }
  bullets[level] = bulletId
  updateStyle('bullets', bullets)
}

function getBulletForLevel(level: number): string {
  if (!selected.value || selected.value.type !== 'list') return 'circle'
  const currentStyle = (selected.value as any).style || {}
  const bullets = currentStyle.bullets || []
  return bullets[level] || (level === 0 ? currentStyle.bullet : 'circle')
}

const pageFormats = [
  { label: 'A3',  width: 297, height: 420 },
  { label: 'A4',  width: 210, height: 297 },
  { label: 'A5',  width: 148, height: 210 },
  { label: 'A6',  width: 105, height: 148 },
  { label: 'B5',  width: 176, height: 250 },
  { label: 'Letter', width: 216, height: 279 },
  { label: 'Legal',  width: 216, height: 356 },
]

function setPageSize(width: number, height: number) {
  store.updatePageSettings({ width, height })
}

function copyHeaderFrom(sourcePageId: string) {
  if (!sourcePageId) {
    store.updatePageSettings({ headerSourcePageId: undefined })
    return
  }
  store.copyHeaderFooterFromPage(sourcePageId, store.activePage.id, 'header')
}

function copyFooterFrom(sourcePageId: string) {
  if (!sourcePageId) {
    store.updatePageSettings({ footerSourcePageId: undefined })
    return
  }
  store.copyHeaderFooterFromPage(sourcePageId, store.activePage.id, 'footer')
}

const availablePages = computed(() => {
  return store.document.pages.filter(p => p.id !== store.activePage.id)
})

function addTableColumn() {
  if (!selected.value || selected.value.type !== 'table') return
  const cols = [...(selected.value as any).columns]
  cols.push({ width: 50, header: `Colonna ${cols.length + 1}`, headerStyle: { font: 'helvetica', weight: 'bold', style: 'normal', size: 10, color: [0, 0, 0], align: 'center' } })
  const rows = (selected.value as any).rows.map((r: any) => ({
    ...r,
    cells: [...r.cells, { text: '', style: {} }]
  }))
  update('columns', cols)
  update('rows', rows)
}

function removeTableColumn(idx: number) {
  if (!selected.value || selected.value.type !== 'table') return
  const cols = (selected.value as any).columns.filter((_: any, i: number) => i !== idx)
  if (cols.length < 1) return
  const rows = (selected.value as any).rows.map((r: any) => ({
    ...r,
    cells: r.cells.filter((_: any, i: number) => i !== idx)
  }))
  update('columns', cols)
  update('rows', rows)
}

function updateTableHeader(colIdx: number, text: string) {
  if (!selected.value || selected.value.type !== 'table') return
  const cols = (selected.value as any).columns.map((c: any, i: number) => {
    if (i !== colIdx) return c
    return { ...c, header: text }
  })
  update('columns', cols)
}

function updateTableHeaderStyle(colIdx: number, key: string, value: unknown) {
  if (!selected.value || selected.value.type !== 'table') return
  const cols = (selected.value as any).columns.map((c: any, i: number) => {
    if (i !== colIdx) return c
    return { ...c, headerStyle: { ...c.headerStyle, [key]: value } }
  })
  update('columns', cols)
}

function updateTableDefaultStyle(key: string, value: unknown) {
  if (!selected.value || selected.value.type !== 'table') return
  update('cellStyle', { ...(selected.value as any).cellStyle, [key]: value })
}

function updateTableCellStyle(colIdx: number, key: string, value: unknown) {
  if (!selected.value || selected.value.type !== 'table') return
  const rows = (selected.value as any).rows.map((r: any, ri: number) => {
    if (ri !== 0) return r
    const newCells = r.cells.map((c: any, ci: number) => {
      if (ci !== colIdx) return c
      return { ...c, style: { ...c.style, [key]: value } }
    })
    return { ...r, cells: newCells }
  })
  update('rows', rows)
}

function updateTableDefaultCellStyle(key: string, value: unknown) {
  if (!selected.value || selected.value.type !== 'table') return
  update('cellStyle', { ...(selected.value as any).cellStyle, [key]: value })
}

function rgbToHex(color: number[]): string {
  const r = Math.round(color[0] * 255).toString(16).padStart(2, '0')
  const g = Math.round(color[1] * 255).toString(16).padStart(2, '0')
  const b = Math.round(color[2] * 255).toString(16).padStart(2, '0')
  return `#${r}${g}${b}`
}

function hexToRgb(hex: string): [number, number, number] {
  const r = parseInt(hex.slice(1, 3), 16) / 255
  const g = parseInt(hex.slice(3, 5), 16) / 255
  const b = parseInt(hex.slice(5, 7), 16) / 255
  return [
    Math.round(r * 100) / 100,
    Math.round(g * 100) / 100,
    Math.round(b * 100) / 100
  ]
}

function toHex(color: number[]): string {
  return rgbToHex(color)
}

function fromHex(hex: string): [number, number, number] {
  return hexToRgb(hex)
}

function tryUpdateData(value: string) {
  try {
    const data = JSON.parse(value)
    if (Array.isArray(data)) {
      update('data', data)
    }
  } catch {}
}

function toggleShowIf(enabled: boolean) {
  if (!selected.value) return
  if (enabled) {
    update('showIf', { field: '', op: 'eq', value: '' })
  } else {
    update('showIf', undefined)
  }
}

function updateShowIf(key: string, value: unknown) {
  if (!selected.value || !(selected.value as any).showIf) return
  const current = { ...(selected.value as any).showIf }
  current[key] = value
  update('showIf', current)
}

function toggleStyleIf(enabled: boolean) {
  if (!selected.value) return
  if (enabled) {
    update('styleIf', [{ field: '', op: 'eq', value: '', then: { fill: [0, 0.6, 0] } }])
  } else {
    update('styleIf', undefined)
  }
}

function updateStyleIfRule(idx: number, key: string, value: unknown) {
  if (!selected.value || !(selected.value as any).styleIf) return
  const rules = [...(selected.value as any).styleIf]
  rules[idx] = { ...rules[idx], [key]: value }
  update('styleIf', rules)
}

function updateStyleIfThen(idx: number, key: string, value: unknown) {
  if (!selected.value || !(selected.value as any).styleIf) return
  const rules = [...(selected.value as any).styleIf]
  rules[idx] = { ...rules[idx], then: { ...rules[idx].then, [key]: value } }
  update('styleIf', rules)
}

function addStyleIfRule() {
  if (!selected.value) return
  const current = (selected.value as any).styleIf || []
  update('styleIf', [...current, { field: '', op: 'eq', value: '', then: { fill: [0, 0.6, 0] } }])
}

function removeStyleIfRule(idx: number) {
  if (!selected.value || !(selected.value as any).styleIf) return
  const rules = [...(selected.value as any).styleIf]
  rules.splice(idx, 1)
  update('styleIf', rules.length > 0 ? rules : undefined)
}

function formatPreview(value: unknown): string {
  if (value === null || value === undefined) return 'null'
  if (typeof value === 'string') return `"${value}"`
  if (Array.isArray(value)) return `Array[${value.length}]`
  return String(value)
}

function onShowIfFieldChange(e: Event) {
  const val = (e.target as HTMLSelectElement).value
  if (val === '__custom__') {
    customField.value = { ...customField.value, showIf: true }
    updateShowIf('field', '')
  } else {
    customField.value = { ...customField.value, showIf: false }
    updateShowIf('field', val)
  }
}

function onStyleIfFieldChange(idx: number, e: Event) {
  const val = (e.target as HTMLSelectElement).value
  const key = `styleIf_${idx}`
  if (val === '__custom__') {
    customField.value = { ...customField.value, [key]: true }
    updateStyleIfRule(idx, 'field', '')
  } else {
    customField.value = { ...customField.value, [key]: false }
    updateStyleIfRule(idx, 'field', val)
  }
}

function toggleChecklistItem(idx: number) {
  if (!selected.value || selected.value.type !== 'checklist') return
  const items = [...(selected.value as any).items]
  items[idx] = { ...items[idx], checked: !items[idx].checked }
  update('items', items)
}

function updateChecklistItemText(idx: number, text: string) {
  if (!selected.value || selected.value.type !== 'checklist') return
  const items = [...(selected.value as any).items]
  items[idx] = { ...items[idx], text }
  update('items', items)
}

function addChecklistItem() {
  if (!selected.value || selected.value.type !== 'checklist') return
  const items = [...(selected.value as any).items, { text: 'Nuova voce', checked: false }]
  update('items', items)
}

function removeChecklistItem(idx: number) {
  if (!selected.value || selected.value.type !== 'checklist') return
  const items = [...(selected.value as any).items]
  items.splice(idx, 1)
  update('items', items)
}

function selectRadioItem(idx: number) {
  if (!selected.value || selected.value.type !== 'radio') return
  const items = (selected.value as any).items.map((item: any, i: number) => ({
    ...item,
    selected: i === idx
  }))
  update('items', items)
}

function updateRadioItemText(idx: number, text: string) {
  if (!selected.value || selected.value.type !== 'radio') return
  const items = [...(selected.value as any).items]
  items[idx] = { ...items[idx], text }
  update('items', items)
}

function addRadioItem() {
  if (!selected.value || selected.value.type !== 'radio') return
  const items = [...(selected.value as any).items, { text: 'Nuova opzione', selected: false }]
  update('items', items)
}

function removeRadioItem(idx: number) {
  if (!selected.value || selected.value.type !== 'radio') return
  const items = [...(selected.value as any).items]
  items.splice(idx, 1)
  update('items', items)
}
</script>

<template>
  <div class="property-panel">
    <div class="panel-scroll">
    <!-- Impostazioni Pagina -->
    <div class="panel-section">
      <h3 class="section-title">Pagina</h3>
      <div class="field-row">
        <label>Formato</label>
        <div class="format-toggles">
          <button
            v-for="fmt in pageFormats"
            :key="fmt.label"
            class="format-toggle"
            :class="{ active: store.activePage.settings.width === fmt.width && store.activePage.settings.height === fmt.height }"
            @click="setPageSize(fmt.width, fmt.height)"
          >{{ fmt.label }}</button>
        </div>
      </div>
      <div class="field-row">
        <label>Larghezza</label>
        <input
          type="number"
          :value="store.activePage.settings.width"
          @input="updatePage('width', +($event.target as HTMLInputElement).value)"
          min="50" max="1000" step="1"
        />
        <span class="unit">mm</span>
      </div>
      <div class="field-row">
        <label>Altezza</label>
        <input
          type="number"
          :value="store.activePage.settings.height"
          @input="updatePage('height', +($event.target as HTMLInputElement).value)"
          min="50" max="1000" step="1"
        />
        <span class="unit">mm</span>
      </div>
      <div class="field-row">
        <label>Margine Su</label>
        <input
          type="number"
          :value="store.activePage.settings.margins.top"
          @input="updateMargin('top', +($event.target as HTMLInputElement).value)"
          min="0" max="100" step="1"
        />
        <span class="unit">mm</span>
      </div>
      <div class="field-row">
        <label>Margine Destra</label>
        <input
          type="number"
          :value="store.activePage.settings.margins.right"
          @input="updateMargin('right', +($event.target as HTMLInputElement).value)"
          min="0" max="100" step="1"
        />
        <span class="unit">mm</span>
      </div>
      <div class="field-row">
        <label>Margine Giù</label>
        <input
          type="number"
          :value="store.activePage.settings.margins.bottom"
          @input="updateMargin('bottom', +($event.target as HTMLInputElement).value)"
          min="0" max="100" step="1"
        />
        <span class="unit">mm</span>
      </div>
      <div class="field-row">
        <label>Margine Sinistra</label>
        <input
          type="number"
          :value="store.activePage.settings.margins.left"
          @input="updateMargin('left', +($event.target as HTMLInputElement).value)"
          min="0" max="100" step="1"
        />
        <span class="unit">mm</span>
      </div>
      <div class="field-row">
        <label>Header</label>
        <input
          type="number"
          :value="store.activePage.settings.headerHeight"
          @input="updatePage('headerHeight', +($event.target as HTMLInputElement).value)"
          min="0" max="100" step="1"
        />
        <span class="unit">mm</span>
      </div>
      <div class="field-row">
        <label>Footer</label>
        <input
          type="number"
          :value="store.activePage.settings.footerHeight"
          @input="updatePage('footerHeight', +($event.target as HTMLInputElement).value)"
          min="0" max="100" step="1"
        />
        <span class="unit">mm</span>
      </div>
      <div class="field-row">
        <label>Copia Header da</label>
        <select :value="store.activePage.settings.headerSourcePageId || ''" @change="copyHeaderFrom(($event.target as HTMLSelectElement).value)">
          <option value="">Nessuna</option>
          <option v-for="page in availablePages" :key="page.id" :value="page.id">
            Pagina {{ store.document.pages.indexOf(page) + 1 }}{{ page.name ? ' — ' + page.name : '' }}
          </option>
        </select>
      </div>
      <div class="field-row">
        <label>Copia Footer da</label>
        <select :value="store.activePage.settings.footerSourcePageId || ''" @change="copyFooterFrom(($event.target as HTMLSelectElement).value)">
          <option value="">Nessuna</option>
          <option v-for="page in availablePages" :key="page.id" :value="page.id">
            Pagina {{ store.document.pages.indexOf(page) + 1 }}{{ page.name ? ' — ' + page.name : '' }}
          </option>
        </select>
      </div>
    </div>

    <!-- Proprietà Elemento Selezionato -->
    <div v-if="multiSelected" class="panel-section">
      <h3 class="section-title">
        {{ store.selectedIds.length }} elementi selezionati
      </h3>
    </div>

    <div v-else-if="selected" class="panel-section">
      <h3 class="section-title">
        Proprietà — {{ selected.type }}
      </h3>

      <!-- Posizione -->
      <div class="field-row">
        <label>X</label>
        <input type="number" :value="selected.x" @input="update('x', +($event.target as HTMLInputElement).value)" step="1" />
        <span class="unit">mm</span>
      </div>
      <div class="field-row">
        <label>Y</label>
        <input type="number" :value="selected.y" @input="update('y', +($event.target as HTMLInputElement).value)" step="1" />
        <span class="unit">mm</span>
      </div>
      <div class="field-row">
        <label>Larghezza</label>
        <input type="number" :value="selected.width" @input="update('width', +($event.target as HTMLInputElement).value)" step="1" />
        <span class="unit">mm</span>
      </div>
      <div class="field-row">
        <label>Altezza</label>
        <input type="number" :value="selected.height" @input="update('height', +($event.target as HTMLInputElement).value)" step="1" />
        <span class="unit">mm</span>
      </div>

      <!-- Testo -->
      <template v-if="selected.type === 'text'">
        <div class="field-group">
          <label>Testo</label>
          <textarea
            :value="(selected as any).text"
            @input="update('text', ($event.target as HTMLTextAreaElement).value)"
            rows="3"
          ></textarea>
        </div>
        <div class="field-row">
          <label>Font</label>
          <select :value="(selected as any).style?.font" @change="updateStyle('font', ($event.target as HTMLSelectElement).value)">
            <optgroup label="Sans-Serif">
              <option value="helvetica">Helvetica</option>
              <option value="arial">Arial</option>
              <option value="sans-serif">Sans-Serif</option>
            </optgroup>
            <optgroup label="Serif">
              <option value="times">Times New Roman</option>
              <option value="serif">Serif</option>
            </optgroup>
            <optgroup label="Monospace">
              <option value="courier">Courier New</option>
              <option value="monospace">Monospace</option>
            </optgroup>
          </select>
        </div>
        <div class="field-row">
          <label>Dimensione</label>
          <input type="number" :value="(selected as any).style?.size" @input="updateStyle('size', +($event.target as HTMLInputElement).value)" min="4" max="72" step="1" />
          <span class="unit">pt</span>
        </div>
        <div class="field-row">
          <label>Stile</label>
          <div class="style-toggles">
            <button
              class="style-toggle"
              :class="{ active: (selected as any).style?.weight === 'bold' }"
              @click="updateStyle('weight', (selected as any).style?.weight === 'bold' ? 'normal' : 'bold')"
              title="Grassetto"
            >B</button>
            <button
              class="style-toggle italic"
              :class="{ active: (selected as any).style?.style === 'italic' }"
              @click="updateStyle('style', (selected as any).style?.style === 'italic' ? 'normal' : 'italic')"
              title="Corsivo"
            >I</button>
            <button
              class="style-toggle underline"
              :class="{ active: (selected as any).style?.underline }"
              @click="updateStyle('underline', !(selected as any).style?.underline)"
              title="Sottolineato"
            >U</button>
          </div>
        </div>
        <div class="field-row">
          <label>Colore</label>
          <input
            type="color"
            :value="rgbToHex((selected as any).style?.color || [0,0,0])"
            @input="updateStyle('color', hexToRgb(($event.target as HTMLInputElement).value))"
          />
        </div>
        <div class="field-row">
          <label>Allineamento</label>
          <div class="align-toggles">
            <button
              class="align-toggle"
              :class="{ active: (selected as any).style?.align === 'left' }"
              @click="updateStyle('align', 'left')"
              title="Sinistra"
            >
              <svg width="16" height="16" viewBox="0 0 16 16"><path d="M1 2h14v2H1zm0 4h8v2H1zm0 4h10v2H1zm0 4h6v2H1z" fill="currentColor"/></svg>
            </button>
            <button
              class="align-toggle"
              :class="{ active: (selected as any).style?.align === 'center' }"
              @click="updateStyle('align', 'center')"
              title="Centro"
            >
              <svg width="16" height="16" viewBox="0 0 16 16"><path d="M1 2h14v2H1zm2 4h10v2H3zm1 4h8v2H4zm1 4h6v2H5z" fill="currentColor"/></svg>
            </button>
            <button
              class="align-toggle"
              :class="{ active: (selected as any).style?.align === 'right' }"
              @click="updateStyle('align', 'right')"
              title="Destra"
            >
              <svg width="16" height="16" viewBox="0 0 16 16"><path d="M1 2h14v2H1zm6 4h8v2H7zm4 4h6v2h-6zm2 4h6v2H9z" fill="currentColor"/></svg>
            </button>
            <button
              class="align-toggle"
              :class="{ active: (selected as any).style?.align === 'justify' }"
              @click="updateStyle('align', 'justify')"
              title="Giustificato"
            >
              <svg width="16" height="16" viewBox="0 0 16 16"><path d="M1 2h14v2H1zm0 4h14v2H1zm0 4h14v2H1zm0 4h14v2H1z" fill="currentColor"/></svg>
            </button>
          </div>
        </div>
      </template>

      <!-- Rettangolo -->
      <template v-if="selected.type === 'rectangle'">
        <div class="field-row">
          <label>Riempimento</label>
          <input
            type="color"
            :value="rgbToHex((selected as any).fill || [0.9,0.9,0.9])"
            @input="update('fill', hexToRgb(($event.target as HTMLInputElement).value))"
          />
        </div>
        <div class="field-row">
          <label>Bordo</label>
          <input
            type="color"
            :value="rgbToHex((selected as any).stroke || [0,0,0])"
            @input="update('stroke', hexToRgb(($event.target as HTMLInputElement).value))"
          />
        </div>
        <div class="field-row">
          <label>Spessore bordo</label>
          <input type="number" :value="(selected as any).strokeWidth" @input="update('strokeWidth', +($event.target as HTMLInputElement).value)" min="0" max="5" step="0.1" />
          <span class="unit">mm</span>
        </div>
      </template>

      <!-- Lista -->
      <template v-if="selected.type === 'list'">
        <div class="field-group">
          <label>Elementi</label>
          <div v-for="(item, idx) in (selected as any).items" :key="idx" class="list-item-edit">
            <input
              :value="item.text"
              @input="updateItems(idx, ($event.target as HTMLInputElement).value)"
              placeholder="Testo elemento..."
            />
          </div>
        </div>
        <div class="field-row">
          <label>Font</label>
          <select :value="(selected as any).style?.font" @change="updateStyle('font', ($event.target as HTMLSelectElement).value)">
            <optgroup label="Sans-Serif">
              <option value="helvetica">Helvetica</option>
              <option value="arial">Arial</option>
              <option value="sans-serif">Sans-Serif</option>
            </optgroup>
            <optgroup label="Serif">
              <option value="times">Times New Roman</option>
              <option value="serif">Serif</option>
            </optgroup>
            <optgroup label="Monospace">
              <option value="courier">Courier New</option>
              <option value="monospace">Monospace</option>
            </optgroup>
          </select>
        </div>
        <div class="field-row">
          <label>Dimensione</label>
          <input type="number" :value="(selected as any).style?.size" @input="updateStyle('size', +($event.target as HTMLInputElement).value)" min="4" max="72" step="1" />
          <span class="unit">pt</span>
        </div>
        <div class="field-row">
          <label>Stile</label>
          <div class="style-toggles">
            <button
              class="style-toggle"
              :class="{ active: (selected as any).style?.weight === 'bold' }"
              @click="updateStyle('weight', (selected as any).style?.weight === 'bold' ? 'normal' : 'bold')"
              title="Grassetto"
            >B</button>
            <button
              class="style-toggle italic"
              :class="{ active: (selected as any).style?.style === 'italic' }"
              @click="updateStyle('style', (selected as any).style?.style === 'italic' ? 'normal' : 'italic')"
              title="Corsivo"
            >I</button>
            <button
              class="style-toggle underline"
              :class="{ active: (selected as any).style?.underline }"
              @click="updateStyle('underline', !(selected as any).style?.underline)"
              title="Sottolineato"
            >U</button>
          </div>
        </div>
        <div class="field-row">
          <label>Bullet</label>
          <select :value="getBulletForLevel(0)" @change="updateBulletForLevel(0, ($event.target as HTMLSelectElement).value)">
            <option v-for="b in BULLET_TYPES" :key="b.id" :value="b.id">{{ b.label }}</option>
          </select>
        </div>
        <div v-for="lvl in maxListLevel" :key="lvl" class="field-row">
          <label>Livello {{ lvl }}</label>
          <select :value="getBulletForLevel(lvl)" @change="updateBulletForLevel(lvl, ($event.target as HTMLSelectElement).value)">
            <option v-for="b in BULLET_TYPES" :key="b.id" :value="b.id">{{ b.label }}</option>
          </select>
        </div>
      </template>

      <!-- Immagine -->
      <template v-if="selected.type === 'image'">
        <div class="field-row">
          <label>Immagine</label>
          <button class="upload-btn" @click="triggerImageUpload">
            {{ (selected as any).src ? 'Cambia' : 'Carica' }}
          </button>
        </div>
        <div v-if="(selected as any).src" class="field-row">
          <label></label>
          <button class="upload-btn remove" @click="update('src', '')">Rimuovi</button>
        </div>
        <div class="field-row">
          <label>Adatta</label>
          <select :value="(selected as any).fit" @change="update('fit', ($event.target as HTMLSelectElement).value)">
            <option value="contain">Contenuto</option>
            <option value="cover">Copri</option>
            <option value="stretch">Stira</option>
          </select>
        </div>
        <input
          ref="imageFileInput"
          type="file"
          accept="image/*"
          style="display: none"
          @change="onImageFileChange"
        />
      </template>

      <!-- Tabella -->
      <template v-if="selected.type === 'table'">
        <!-- Nome tabella -->
        <div class="field-row">
          <label>Nome</label>
          <input type="text" :value="(selected as any).name" @input="update('name', ($event.target as HTMLInputElement).value)" placeholder="es. articoli" />
        </div>

        <!-- Colonne -->
        <div class="field-group">
          <div class="field-row">
            <label>Colonne</label>
            <span class="col-count">{{ (selected as any).columns?.length }}</span>
            <button class="small-btn" @click="addTableColumn">+</button>
            <button class="small-btn" @click="removeTableColumn((selected as any).columns.length - 1)" :disabled="(selected as any).columns?.length <= 1">−</button>
          </div>
        </div>

        <!-- Intestazioni colonne -->
        <div class="field-group">
          <label class="group-label">Intestazioni</label>
          <div v-for="(col, colIdx) in (selected as any).columns" :key="colIdx" class="header-editor">
            <div class="header-row" @click="expandedHeader = expandedHeader === colIdx ? null : colIdx">
              <span class="header-label">Col {{ colIdx + 1 }}: {{ col.header }}</span>
              <span class="expand-arrow">{{ expandedHeader === colIdx ? '▾' : '▸' }}</span>
            </div>
            <div v-if="expandedHeader === colIdx" class="header-detail">
              <div class="field-row">
                <label>Testo</label>
                <input type="text" :value="col.header" @input="updateTableHeader(colIdx, ($event.target as HTMLInputElement).value)" />
              </div>
              <div class="field-row">
                <label>Larghezza</label>
                <input type="number" :value="col.width" @input="updateTableHeaderStyle(colIdx, 'width', +($event.target as HTMLInputElement).value)" min="10" max="200" step="5" />
                <span class="unit">peso</span>
              </div>
              <div class="field-row">
                <label>Font</label>
                <select :value="col.headerStyle?.font" @change="updateTableHeaderStyle(colIdx, 'font', ($event.target as HTMLSelectElement).value)">
                  <option value="helvetica">Helvetica</option>
                  <option value="arial">Arial</option>
                  <option value="times">Times</option>
                  <option value="courier">Courier</option>
                </select>
              </div>
              <div class="field-row">
                <label>Dim.</label>
                <input type="number" :value="col.headerStyle?.size" @input="updateTableHeaderStyle(colIdx, 'size', +($event.target as HTMLInputElement).value)" min="4" max="72" step="1" />
                <span class="unit">pt</span>
              </div>
              <div class="field-row">
                <label>Stile</label>
                <div class="style-toggles">
                  <button class="style-toggle" :class="{ active: col.headerStyle?.weight === 'bold' }" @click="updateTableHeaderStyle(colIdx, 'weight', col.headerStyle?.weight === 'bold' ? 'normal' : 'bold')" title="Grassetto">B</button>
                  <button class="style-toggle italic" :class="{ active: col.headerStyle?.style === 'italic' }" @click="updateTableHeaderStyle(colIdx, 'style', col.headerStyle?.style === 'italic' ? 'normal' : 'italic')" title="Corsivo">I</button>
                </div>
              </div>
              <div class="field-row">
                <label>Allinea</label>
                <div class="style-toggles">
                  <button class="style-toggle" :class="{ active: col.headerStyle?.align === 'left' }" @click="updateTableHeaderStyle(colIdx, 'align', 'left')">L</button>
                  <button class="style-toggle" :class="{ active: col.headerStyle?.align === 'center' }" @click="updateTableHeaderStyle(colIdx, 'align', 'center')">C</button>
                  <button class="style-toggle" :class="{ active: col.headerStyle?.align === 'right' }" @click="updateTableHeaderStyle(colIdx, 'align', 'right')">R</button>
                </div>
              </div>
              <div class="field-row">
                <label>Colore</label>
                <input type="color" :value="rgbToHex(col.headerStyle?.color || [0,0,0])" @input="updateTableHeaderStyle(colIdx, 'color', hexToRgb(($event.target as HTMLInputElement).value))" />
              </div>
              <div class="field-row">
                <label>Sfondo</label>
                <input type="color" :value="rgbToHex((selected as any).rows?.[0]?.cells?.[colIdx]?.style?.background || [1,1,1])" @input="updateTableCellStyle(colIdx, 'background', hexToRgb(($event.target as HTMLInputElement).value))" />
              </div>
            </div>
          </div>
        </div>

        <!-- Stile celle dati per colonna -->
        <div class="field-group">
          <label class="group-label">Stile celle dati (per colonna)</label>
          <div v-for="(col, colIdx) in (selected as any).columns" :key="'cell-' + colIdx" class="header-editor">
            <div class="header-row" @click="expandedCell = expandedCell === colIdx ? null : colIdx">
              <span class="header-label">Col {{ colIdx + 1 }}: {{ col.header }}</span>
              <span class="expand-arrow">{{ expandedCell === colIdx ? '▾' : '▸' }}</span>
            </div>
            <div v-if="expandedCell === colIdx" class="header-detail">
              <div class="field-row">
                <label>Font</label>
                <select :value="(selected as any).rows?.[0]?.cells?.[colIdx]?.style?.font || (selected as any).cellStyle?.font" @change="updateTableCellStyle(colIdx, 'font', ($event.target as HTMLSelectElement).value)">
                  <option value="helvetica">Helvetica</option>
                  <option value="arial">Arial</option>
                  <option value="times">Times</option>
                  <option value="courier">Courier</option>
                </select>
              </div>
              <div class="field-row">
                <label>Dim.</label>
                <input type="number" :value="(selected as any).rows?.[0]?.cells?.[colIdx]?.style?.size || (selected as any).cellStyle?.size" @input="updateTableCellStyle(colIdx, 'size', +($event.target as HTMLInputElement).value)" min="4" max="72" step="1" />
                <span class="unit">pt</span>
              </div>
              <div class="field-row">
                <label>Stile</label>
                <div class="style-toggles">
                  <button class="style-toggle" :class="{ active: (selected as any).rows?.[0]?.cells?.[colIdx]?.style?.weight === 'bold' }" @click="updateTableCellStyle(colIdx, 'weight', (selected as any).rows?.[0]?.cells?.[colIdx]?.style?.weight === 'bold' ? 'normal' : 'bold')" title="Grassetto">B</button>
                  <button class="style-toggle italic" :class="{ active: (selected as any).rows?.[0]?.cells?.[colIdx]?.style?.style === 'italic' }" @click="updateTableCellStyle(colIdx, 'style', (selected as any).rows?.[0]?.cells?.[colIdx]?.style?.style === 'italic' ? 'normal' : 'italic')" title="Corsivo">I</button>
                </div>
              </div>
              <div class="field-row">
                <label>Allinea</label>
                <div class="style-toggles">
                  <button class="style-toggle" :class="{ active: ((selected as any).rows?.[0]?.cells?.[colIdx]?.style?.align || (selected as any).cellStyle?.align) === 'left' }" @click="updateTableCellStyle(colIdx, 'align', 'left')">L</button>
                  <button class="style-toggle" :class="{ active: ((selected as any).rows?.[0]?.cells?.[colIdx]?.style?.align || (selected as any).cellStyle?.align) === 'center' }" @click="updateTableCellStyle(colIdx, 'align', 'center')">C</button>
                  <button class="style-toggle" :class="{ active: ((selected as any).rows?.[0]?.cells?.[colIdx]?.style?.align || (selected as any).cellStyle?.align) === 'right' }" @click="updateTableCellStyle(colIdx, 'align', 'right')">R</button>
                </div>
              </div>
              <div class="field-row">
                <label>Colore</label>
                <input type="color" :value="rgbToHex((selected as any).rows?.[0]?.cells?.[colIdx]?.style?.color || (selected as any).cellStyle?.color || [0,0,0])" @input="updateTableCellStyle(colIdx, 'color', hexToRgb(($event.target as HTMLInputElement).value))" />
              </div>
            </div>
          </div>
        </div>

        <!-- Stile celle dati (default) -->
        <div class="field-group">
          <label class="group-label">Stile celle dati (default)</label>
          <div class="field-row">
            <label>Font</label>
            <select :value="(selected as any).cellStyle?.font" @change="updateTableDefaultStyle('font', ($event.target as HTMLSelectElement).value)">
              <option value="helvetica">Helvetica</option>
              <option value="arial">Arial</option>
              <option value="times">Times</option>
              <option value="courier">Courier</option>
            </select>
          </div>
          <div class="field-row">
            <label>Dim.</label>
            <input type="number" :value="(selected as any).cellStyle?.size" @input="updateTableDefaultStyle('size', +($event.target as HTMLInputElement).value)" min="4" max="72" step="1" />
            <span class="unit">pt</span>
          </div>
          <div class="field-row">
            <label>Allinea</label>
            <div class="style-toggles">
              <button class="style-toggle" :class="{ active: (selected as any).cellStyle?.align === 'left' }" @click="updateTableDefaultStyle('align', 'left')">L</button>
              <button class="style-toggle" :class="{ active: (selected as any).cellStyle?.align === 'center' }" @click="updateTableDefaultStyle('align', 'center')">C</button>
              <button class="style-toggle" :class="{ active: (selected as any).cellStyle?.align === 'right' }" @click="updateTableDefaultStyle('align', 'right')">R</button>
            </div>
          </div>
          <div class="field-row">
            <label>Colore</label>
            <input type="color" :value="rgbToHex((selected as any).cellStyle?.color || [0,0,0])" @input="updateTableDefaultCellStyle('color', hexToRgb(($event.target as HTMLInputElement).value))" />
          </div>
          <div class="field-row">
            <label>Sfondo</label>
            <input type="color" :value="rgbToHex((selected as any).cellStyle?.background || [1,1,1])" @input="updateTableDefaultCellStyle('background', hexToRgb(($event.target as HTMLInputElement).value))" />
          </div>
        </div>

        <!-- Bordi -->
        <div class="field-row">
          <label>Colore bordi</label>
          <input type="color" :value="rgbToHex((selected as any).borderColor || [0,0,0])" @input="update('borderColor', hexToRgb(($event.target as HTMLInputElement).value))" />
        </div>
        <div class="field-row">
          <label>Spessore</label>
          <input type="number" :value="(selected as any).borderWidth" @input="update('borderWidth', +($event.target as HTMLInputElement).value)" min="0" max="5" step="0.1" />
          <span class="unit">mm</span>
        </div>

        <!-- Ripeti intestazione -->
        <div class="field-row checkbox-row">
          <label>Ripeti intestazione</label>
          <input
            type="checkbox"
            :checked="(selected as any).repeatHeader"
            @change="update('repeatHeader', ($event.target as HTMLInputElement).checked)"
          />
        </div>
      </template>

      <template v-if="selected.type === 'ellipse'">
        <div class="field-row">
          <label>Riempimento</label>
          <input type="color" :value="toHex(selected.fill || [0.9,0.9,0.9])" @input="update('fill', fromHex(($event.target as HTMLInputElement).value))" />
        </div>
        <div class="field-row">
          <label>Bordo</label>
          <input type="color" :value="toHex(selected.stroke || [0,0,0])" @input="update('stroke', fromHex(($event.target as HTMLInputElement).value))" />
        </div>
        <div class="field-row">
          <label>Spessore bordo</label>
          <input type="number" :value="selected.strokeWidth ?? 0.5" step="0.1" min="0" @input="update('strokeWidth', +($event.target as HTMLInputElement).value)" />
        </div>
      </template>

      <template v-if="selected.type === 'divider'">
        <div class="field-row">
          <label>Colore</label>
          <input type="color" :value="toHex(selected.color)" @input="update('color', fromHex(($event.target as HTMLInputElement).value))" />
        </div>
        <div class="field-row">
          <label>Spessore</label>
          <input type="number" :value="selected.lineWidth" step="0.1" min="0.1" @input="update('lineWidth', +($event.target as HTMLInputElement).value)" />
        </div>
        <div class="field-row">
          <label>Stile</label>
          <select :value="selected.lineStyle" @change="update('lineStyle', ($event.target as HTMLSelectElement).value)">
            <option value="solid">Continuo</option>
            <option value="dashed">Tratteggiato</option>
            <option value="dotted">Punteggiato</option>
          </select>
        </div>
      </template>

      <template v-if="selected.type === 'signature'">
        <div class="field-row">
          <label>Etichetta</label>
          <input type="text" :value="selected.label" @input="update('label', ($event.target as HTMLInputElement).value)" />
        </div>
        <div class="field-row">
          <label>Colore</label>
          <input type="color" :value="toHex(selected.color)" @input="update('color', fromHex(($event.target as HTMLInputElement).value))" />
        </div>
      </template>

      <template v-if="selected.type === 'container'">
        <div class="field-row">
          <label>Riempimento</label>
          <input type="color" :value="toHex(selected.fill || [1,1,1])" @input="update('fill', fromHex(($event.target as HTMLInputElement).value))" />
        </div>
        <div class="field-row">
          <label>Colore bordo</label>
          <input type="color" :value="toHex(selected.borderColor)" @input="update('borderColor', fromHex(($event.target as HTMLInputElement).value))" />
        </div>
        <div class="field-row">
          <label>Spessore bordo</label>
          <input type="number" :value="selected.borderWidth" step="0.1" min="0" @input="update('borderWidth', +($event.target as HTMLInputElement).value)" />
        </div>
      </template>

      <template v-if="selected.type === 'pageNumber'">
        <div class="section-title">Stile testo</div>
        <div class="field-row">
          <label>Font</label>
          <select :value="selected.style.font" @change="updateStyle('font', ($event.target as HTMLSelectElement).value)">
            <option value="helvetica">Helvetica</option>
            <option value="times">Times</option>
            <option value="courier">Courier</option>
          </select>
        </div>
        <div class="field-row">
          <label>Dimensione</label>
          <input type="number" :value="selected.style.size" min="1" @input="updateStyle('size', +($event.target as HTMLInputElement).value)" />
        </div>
        <div class="field-row">
          <label>Colore</label>
          <input type="color" :value="toHex(selected.style.color)" @input="updateStyle('color', fromHex(($event.target as HTMLInputElement).value))" />
        </div>
        <div class="field-row">
          <label>Grassetto</label>
          <input type="checkbox" :checked="selected.style.weight === 'bold'" @change="updateStyle('weight', ($event.target as HTMLInputElement).checked ? 'bold' : 'normal')" />
        </div>
        <div class="field-row">
          <label>Allineamento</label>
          <select :value="selected.style.align" @change="updateStyle('align', ($event.target as HTMLSelectElement).value)">
            <option value="left">Sinistra</option>
            <option value="center">Centro</option>
            <option value="right">Destra</option>
          </select>
        </div>
      </template>

      <template v-if="selected.type === 'date'">
        <div class="field-row">
          <label>Formato</label>
          <input type="text" :value="selected.format" @input="update('format', ($event.target as HTMLInputElement).value)" />
        </div>
        <div class="section-title">Stile testo</div>
        <div class="field-row">
          <label>Font</label>
          <select :value="selected.style.font" @change="updateStyle('font', ($event.target as HTMLSelectElement).value)">
            <option value="helvetica">Helvetica</option>
            <option value="times">Times</option>
            <option value="courier">Courier</option>
          </select>
        </div>
        <div class="field-row">
          <label>Dimensione</label>
          <input type="number" :value="selected.style.size" min="1" @input="updateStyle('size', +($event.target as HTMLInputElement).value)" />
        </div>
        <div class="field-row">
          <label>Colore</label>
          <input type="color" :value="toHex(selected.style.color)" @input="updateStyle('color', fromHex(($event.target as HTMLInputElement).value))" />
        </div>
        <div class="field-row">
          <label>Grassetto</label>
          <input type="checkbox" :checked="selected.style.weight === 'bold'" @change="updateStyle('weight', ($event.target as HTMLInputElement).checked ? 'bold' : 'normal')" />
        </div>
        <div class="field-row">
          <label>Allineamento</label>
          <select :value="selected.style.align" @change="updateStyle('align', ($event.target as HTMLSelectElement).value)">
            <option value="left">Sinistra</option>
            <option value="center">Centro</option>
            <option value="right">Destra</option>
          </select>
        </div>
      </template>

      <template v-if="selected.type === 'watermark'">
        <div class="field-row">
          <label>Testo</label>
          <input type="text" :value="selected.text" @input="update('text', ($event.target as HTMLInputElement).value)" />
        </div>
        <div class="field-row">
          <label>Dimensione</label>
          <input type="number" :value="selected.fontSize" min="1" @input="update('fontSize', +($event.target as HTMLInputElement).value)" />
        </div>
        <div class="field-row">
          <label>Colore</label>
          <input type="color" :value="toHex(selected.color)" @input="update('color', fromHex(($event.target as HTMLInputElement).value))" />
        </div>
        <div class="field-row">
          <label>Rotazione</label>
          <input type="number" :value="selected.rotation" step="1" @input="update('rotation', +($event.target as HTMLInputElement).value)" />
        </div>
        <div class="field-row">
          <label>Opacità</label>
          <input type="range" min="0" max="1" step="0.05" :value="selected.opacity" @input="update('opacity', +($event.target as HTMLInputElement).value)" />
          <span class="value-label">{{ Math.round(selected.opacity * 100) }}%</span>
        </div>
      </template>

      <template v-if="selected.type === 'qrcode'">
        <div class="field-row">
          <label>Contenuto</label>
          <input type="text" :value="selected.text" @input="update('text', ($event.target as HTMLInputElement).value)" />
        </div>
      </template>

      <template v-if="selected.type === 'stamp'">
        <div class="field-row">
          <label>Testo</label>
          <input type="text" :value="selected.text" @input="update('text', ($event.target as HTMLInputElement).value)" />
        </div>
        <div class="field-row">
          <label>Preset</label>
          <select :value="selected.preset" @change="update('preset', ($event.target as HTMLSelectElement).value)">
            <option value="approved">APPROVATO</option>
            <option value="confidential">RISERVATO</option>
            <option value="draft">BOZZA</option>
            <option value="paid">PAGATO</option>
            <option value="urgent">URGENTE</option>
          </select>
        </div>
        <div class="field-row">
          <label>Colore</label>
          <input type="color" :value="toHex(selected.color)" @input="update('color', fromHex(($event.target as HTMLInputElement).value))" />
        </div>
      </template>

      <template v-if="selected.type === 'quote'">
        <div class="field-row">
          <label>Citazione</label>
          <input type="text" :value="selected.text" @input="update('text', ($event.target as HTMLInputElement).value)" />
        </div>
        <div class="field-row">
          <label>Autore</label>
          <input type="text" :value="selected.author" @input="update('author', ($event.target as HTMLInputElement).value)" />
        </div>
        <div class="field-row">
          <label>Colore barra</label>
          <input type="color" :value="toHex(selected.barColor)" @input="update('barColor', fromHex(($event.target as HTMLInputElement).value))" />
        </div>
        <div class="section-title">Stile testo</div>
        <div class="field-row">
          <label>Font</label>
          <select :value="selected.style.font" @change="updateStyle('font', ($event.target as HTMLSelectElement).value)">
            <option value="helvetica">Helvetica</option>
            <option value="times">Times</option>
            <option value="courier">Courier</option>
          </select>
        </div>
        <div class="field-row">
          <label>Dimensione</label>
          <input type="number" :value="selected.style.size" min="1" @input="updateStyle('size', +($event.target as HTMLInputElement).value)" />
        </div>
        <div class="field-row">
          <label>Colore</label>
          <input type="color" :value="toHex(selected.style.color)" @input="updateStyle('color', fromHex(($event.target as HTMLInputElement).value))" />
        </div>
      </template>

      <template v-if="selected.type === 'callout'">
        <div class="field-row">
          <label>Testo</label>
          <input type="text" :value="selected.text" @input="update('text', ($event.target as HTMLInputElement).value)" />
        </div>
        <div class="field-row">
          <label>Stile</label>
          <select :value="selected.style" @change="update('style', ($event.target as HTMLSelectElement).value)">
            <option value="info">Info</option>
            <option value="warning">Avviso</option>
            <option value="error">Errore</option>
            <option value="success">Successo</option>
          </select>
        </div>
        <div class="field-row">
          <label>Icona</label>
          <input type="text" :value="selected.icon" @input="update('icon', ($event.target as HTMLInputElement).value)" />
        </div>
        <div class="field-row">
          <label>Colore sfondo</label>
          <input type="color" :value="toHex(selected.bgColor)" @input="update('bgColor', fromHex(($event.target as HTMLInputElement).value))" />
        </div>
        <div class="field-row">
          <label>Colore bordo</label>
          <input type="color" :value="toHex(selected.borderColor)" @input="update('borderColor', fromHex(($event.target as HTMLInputElement).value))" />
        </div>
      </template>

      <template v-if="selected.type === 'codeBlock'">
        <div class="field-row">
          <label>Codice</label>
          <textarea :value="selected.text" @input="update('text', ($event.target as HTMLTextAreaElement).value)" rows="4"></textarea>
        </div>
        <div class="field-row">
          <label>Linguaggio</label>
          <input type="text" :value="selected.language" @input="update('language', ($event.target as HTMLInputElement).value)" />
        </div>
      </template>

      <template v-if="selected.type === 'progressBar'">
        <div class="field-row">
          <label>Valore</label>
          <input type="range" min="0" max="100" :value="selected.value" @input="update('value', +($event.target as HTMLInputElement).value)" />
          <span class="value-label">{{ selected.value }}%</span>
        </div>
        <div class="field-row">
          <label>Etichetta</label>
          <input type="text" :value="selected.label" @input="update('label', ($event.target as HTMLInputElement).value)" />
        </div>
        <div class="field-row">
          <label>Colore riempimento</label>
          <input type="color" :value="toHex(selected.color)" @input="update('color', fromHex(($event.target as HTMLInputElement).value))" />
        </div>
        <div class="field-row">
          <label>Colore sfondo</label>
          <input type="color" :value="toHex(selected.bgColor)" @input="update('bgColor', fromHex(($event.target as HTMLInputElement).value))" />
        </div>
      </template>

      <template v-if="selected.type === 'icon'">
        <div class="field-row">
          <label>Icona</label>
          <select :value="selected.name" @change="update('name', ($event.target as HTMLSelectElement).value)">
            <option value="check">Check</option>
            <option value="warning">Warning</option>
            <option value="info">Info</option>
            <option value="error">Error</option>
            <option value="star">Stella</option>
            <option value="heart">Cuore</option>
            <option value="arrow">Freccia</option>
          </select>
        </div>
        <div class="field-row">
          <label>Colore</label>
          <input type="color" :value="toHex(selected.color)" @input="update('color', fromHex(($event.target as HTMLInputElement).value))" />
        </div>
      </template>

      <template v-if="selected.type === 'barcode'">
        <div class="field-row">
          <label>Contenuto</label>
          <input type="text" :value="selected.text" @input="update('text', ($event.target as HTMLInputElement).value)" />
        </div>
        <div class="field-row">
          <label>Formato</label>
          <select :value="selected.format" @change="update('format', ($event.target as HTMLSelectElement).value)">
            <option value="code128">Code 128</option>
            <option value="code39">Code 39</option>
            <option value="ean13">EAN-13</option>
          </select>
        </div>
        <div class="field-row">
          <label>Mostra testo</label>
          <input type="checkbox" :checked="selected.showText" @change="update('showText', ($event.target as HTMLInputElement).checked)" />
        </div>
      </template>

      <template v-if="selected.type === 'chart'">
        <div class="field-row">
          <label>Tipo</label>
          <select :value="selected.chartType" @change="update('chartType', ($event.target as HTMLSelectElement).value)">
            <option value="bar">Barre</option>
            <option value="pie">Torta</option>
            <option value="line">Linea</option>
          </select>
        </div>
        <div class="field-row">
          <label>Dati (JSON)</label>
          <textarea :value="JSON.stringify(selected.data, null, 2)" @input="tryUpdateData(($event.target as HTMLTextAreaElement).value)" rows="4"></textarea>
        </div>
      </template>

      <template v-if="selected.type === 'pageBreak'">
        <div class="field-row">
          <label>Interruzione pagina</label>
          <span class="value-label">Forza il taglio pagina</span>
        </div>
      </template>

      <template v-if="selected.type === 'dataRepeat'">
        <div class="field-row">
          <label>Campo dati</label>
          <input type="text" :value="selected.repeatField" @input="update('repeatField', ($event.target as HTMLInputElement).value)" />
        </div>
        <div class="field-row">
          <label>Direzione</label>
          <select :value="selected.direction" @change="update('direction', ($event.target as HTMLSelectElement).value)">
            <option value="vertical">Verticale</option>
            <option value="horizontal">Orizzontale</option>
          </select>
        </div>
        <div class="field-row">
          <label>Spaziatura</label>
          <input type="number" :value="selected.spacing" min="0" @input="update('spacing', +($event.target as HTMLInputElement).value)" />
        </div>
      </template>

      <template v-if="selected.type === 'checklist'">
        <div class="section-title">Voci Checklist</div>
        <div v-for="(item, idx) in selected.items" :key="idx" class="checklist-item-row">
          <input type="checkbox" :checked="item.checked" @change="toggleChecklistItem(idx)" />
          <input type="text" :value="item.text" @input="updateChecklistItemText(idx, ($event.target as HTMLInputElement).value)" />
          <button class="remove-rule-btn" @click="removeChecklistItem(idx)">✕</button>
        </div>
        <button class="add-rule-btn" @click="addChecklistItem()">+ Aggiungi voce</button>
        <div class="field-row">
          <label>Dimensione</label>
          <input type="number" :value="selected.size" min="6" max="72" @input="update('size', +($event.target as HTMLInputElement).value)" />
          <span class="unit">pt</span>
        </div>
        <div class="field-row">
          <label>Colore testo</label>
          <input type="color" :value="toHex(selected.color)" @input="update('color', fromHex(($event.target as HTMLInputElement).value))" />
        </div>
        <div class="field-row">
          <label>Colore spuntata</label>
          <input type="color" :value="toHex(selected.checkedColor)" @input="update('checkedColor', fromHex(($event.target as HTMLInputElement).value))" />
        </div>
        <div class="field-row">
          <label>Spaziatura</label>
          <input type="number" :value="selected.gap" min="0" max="20" step="0.5" @input="update('gap', +($event.target as HTMLInputElement).value)" />
          <span class="unit">mm</span>
        </div>
      </template>

      <template v-if="selected.type === 'radio'">
        <div class="section-title">Voci Radio</div>
        <div v-for="(item, idx) in selected.items" :key="idx" class="checklist-item-row">
          <input type="radio" :name="'radio-' + selected.id" :checked="item.selected" @change="selectRadioItem(idx)" />
          <input type="text" :value="item.text" @input="updateRadioItemText(idx, ($event.target as HTMLInputElement).value)" />
          <button class="remove-rule-btn" @click="removeRadioItem(idx)">✕</button>
        </div>
        <button class="add-rule-btn" @click="addRadioItem()">+ Aggiungi opzione</button>
        <div class="field-row">
          <label>Dimensione</label>
          <input type="number" :value="selected.size" min="6" max="72" @input="update('size', +($event.target as HTMLInputElement).value)" />
          <span class="unit">pt</span>
        </div>
        <div class="field-row">
          <label>Colore testo</label>
          <input type="color" :value="toHex(selected.color)" @input="update('color', fromHex(($event.target as HTMLInputElement).value))" />
        </div>
        <div class="field-row">
          <label>Colore selezione</label>
          <input type="color" :value="toHex(selected.selectedColor)" @input="update('selectedColor', fromHex(($event.target as HTMLInputElement).value))" />
        </div>
        <div class="field-row">
          <label>Spaziatura</label>
          <input type="number" :value="selected.gap" min="0" max="20" step="0.5" @input="update('gap', +($event.target as HTMLInputElement).value)" />
          <span class="unit">mm</span>
        </div>
      </template>

      <div class="section-title programmabilita-title">Programmabilità</div>
        <div class="field-row">
          <label>Condizione visibilità</label>
          <input type="checkbox" :checked="!!selected.showIf" @change="toggleShowIf(($event.target as HTMLInputElement).checked)" />
        </div>
        <template v-if="selected.showIf">
          <div class="field-row">
            <label>Campo</label>
            <select v-if="!customField['showIf']" :value="selected.showIf.field" @change="onShowIfFieldChange($event)">
              <option value="">-- Seleziona campo --</option>
              <option v-for="opt in fieldOptions" :key="opt.path" :value="opt.path">
                {{ opt.label }} → {{ formatPreview(opt.value) }}
              </option>
              <option value="__custom__">✏️ Personalizza...</option>
            </select>
            <input v-else type="text" :value="selected.showIf.field === '__custom__' ? '' : selected.showIf.field" placeholder="es. ordine.stato" @input="updateShowIf('field', ($event.target as HTMLInputElement).value)" />
          </div>
          <div class="field-row">
            <label>Operatore</label>
            <select :value="selected.showIf.op" @change="updateShowIf('op', ($event.target as HTMLSelectElement).value)">
              <option value="eq">Uguale a</option>
              <option value="neq">Diverso da</option>
              <option value="gt">Maggiore di</option>
              <option value="lt">Minore di</option>
              <option value="gte">Maggiore o uguale</option>
              <option value="lte">Minore o uguale</option>
              <option value="empty">Vuoto</option>
              <option value="notempty">Non vuoto</option>
              <option value="contains">Contiene</option>
            </select>
          </div>
          <div class="field-row" v-if="!['empty', 'notempty'].includes(selected.showIf.op)">
            <label>Valore</label>
            <input type="text" :value="selected.showIf.value" @input="updateShowIf('value', ($event.target as HTMLInputElement).value)" />
          </div>
        </template>
        <div class="field-row">
          <label>Stile condizionale</label>
          <input type="checkbox" :checked="!!selected.styleIf?.length" @change="toggleStyleIf(($event.target as HTMLInputElement).checked)" />
        </div>
        <template v-if="selected.styleIf?.length">
          <div v-for="(rule, idx) in selected.styleIf" :key="idx" class="style-if-rule">
            <div class="field-row">
              <label>Campo</label>
              <select v-if="!customField['styleIf_' + idx]" :value="rule.field" @change="onStyleIfFieldChange(idx, $event)">
                <option value="">-- Seleziona campo --</option>
                <option v-for="opt in fieldOptions" :key="opt.path" :value="opt.path">
                  {{ opt.label }} → {{ formatPreview(opt.value) }}
                </option>
                <option value="__custom__">✏️ Personalizza...</option>
              </select>
              <input v-else type="text" :value="rule.field === '__custom__' ? '' : rule.field" placeholder="es. ordine.stato" @input="updateStyleIfRule(idx, 'field', ($event.target as HTMLInputElement).value)" />
            </div>
            <div class="field-row">
              <label>Valore</label>
              <input type="text" :value="rule.value" @input="updateStyleIfRule(idx, 'value', ($event.target as HTMLInputElement).value)" />
            </div>
            <div class="field-row">
              <label>Colore se vero</label>
              <input type="color" :value="toHex((rule.then?.fill as any) || [0,0,0])" @input="updateStyleIfThen(idx, 'fill', fromHex(($event.target as HTMLInputElement).value) as any)" />
            </div>
            <button class="remove-rule-btn" @click="removeStyleIfRule(idx)">✕</button>
          </div>
          <button class="add-rule-btn" @click="addStyleIfRule()">+ Aggiungi regola</button>
        </template>
        <div class="field-row">
          <label>Ripeti su tutte le pagine</label>
          <input type="checkbox" :checked="!!selected.repeatOnAllPages" @change="update('repeatOnAllPages', ($event.target as HTMLInputElement).checked || undefined)" />
        </div>
    </div>

    <div v-else class="panel-section empty">
      <p>Seleziona un elemento per modificarne le proprietà</p>
    </div>
    </div>
  </div>
</template>

<style scoped>
.property-panel {
  width: 260px;
  min-width: 260px;
  max-width: 260px;
  background: #16213e;
  border-left: 1px solid #0f3460;
  overflow: hidden;
}

.panel-scroll {
  height: 100%;
  overflow-y: auto;
  overflow-x: hidden;
  padding: 12px;
}

.panel-section {
  margin-bottom: 16px;
}

.section-title {
  font-size: 12px;
  text-transform: uppercase;
  letter-spacing: 1px;
  color: #888;
  margin-bottom: 10px;
  padding-bottom: 6px;
  border-bottom: 1px solid #0f3460;
}

.programmabilita-title {
  color: #e94560;
  border-bottom-color: #e94560;
  margin-top: 8px;
}

.field-row {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 6px;
  min-width: 0;
}

.field-row label {
  font-size: 12px;
  color: #aaa;
  min-width: 60px;
  flex-shrink: 0;
}

.field-row input[type="number"],
.field-row select {
  flex: 1;
  background: #0f3460;
  border: 1px solid #1a1a4e;
  color: #eee;
  padding: 4px 6px;
  border-radius: 3px;
  font-size: 12px;
}

.field-row input[type="color"] {
  width: 32px;
  height: 24px;
  padding: 0;
  border: 1px solid #1a1a4e;
  border-radius: 3px;
  cursor: pointer;
  background: transparent;
}

.unit {
  font-size: 11px;
  color: #666;
  min-width: 20px;
}

.field-group {
  margin-bottom: 8px;
}

.field-group label {
  display: block;
  font-size: 12px;
  color: #aaa;
  margin-bottom: 4px;
}

textarea {
  width: 100%;
  background: #0f3460;
  border: 1px solid #1a1a4e;
  color: #eee;
  padding: 6px;
  border-radius: 3px;
  font-size: 12px;
  resize: vertical;
  font-family: inherit;
}

.list-item-edit {
  margin-bottom: 4px;
}

.list-item-edit input {
  width: 100%;
  background: #0f3460;
  border: 1px solid #1a1a4e;
  color: #eee;
  padding: 4px 6px;
  border-radius: 3px;
  font-size: 12px;
}

.empty {
  text-align: center;
  padding: 40px 20px;
  color: #666;
  font-size: 13px;
}

.style-toggles {
  display: flex;
  gap: 4px;
}

.style-toggle {
  width: 32px;
  height: 28px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #0f3460;
  border: 1px solid #1a1a4e;
  color: #aaa;
  border-radius: 3px;
  cursor: pointer;
  font-size: 14px;
  font-weight: 400;
  transition: all 0.15s;
}

.style-toggle.italic {
  font-style: italic;
}

.style-toggle.underline {
  text-decoration: underline;
}

.style-toggle:hover {
  background: #1a4a7a;
  color: #fff;
}

.style-toggle.active {
  background: #4A90D9;
  border-color: #4A90D9;
  color: #fff;
}

.align-toggles {
  display: flex;
  gap: 2px;
}

.align-toggle {
  width: 28px;
  height: 28px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #0f3460;
  border: 1px solid #1a1a4e;
  color: #aaa;
  border-radius: 3px;
  cursor: pointer;
  padding: 4px;
  transition: all 0.15s;
}

.align-toggle:hover {
  background: #1a4a7a;
  color: #fff;
}

.align-toggle.active {
  background: #4A90D9;
  border-color: #4A90D9;
  color: #fff;
}

.upload-btn {
  flex: 1;
  background: #0f3460;
  border: 1px solid #1a1a4e;
  color: #eee;
  padding: 6px 12px;
  border-radius: 3px;
  cursor: pointer;
  font-size: 12px;
  transition: background 0.15s;
}

.upload-btn:hover {
  background: #1a4a7a;
}

.upload-btn.remove {
  background: #5a2020;
  border-color: #7a3030;
}

.upload-btn.remove:hover {
  background: #7a3030;
}

.format-toggles {
  display: flex;
  flex-wrap: wrap;
  gap: 3px;
  flex: 1;
  min-width: 0;
}

.format-toggle {
  padding: 4px 8px;
  background: #0f3460;
  border: 1px solid #1a1a4e;
  color: #aaa;
  border-radius: 3px;
  cursor: pointer;
  font-size: 11px;
  transition: all 0.15s;
}

.format-toggle:hover {
  background: #1a4a7a;
  color: #fff;
}

.format-toggle.active {
  background: #4A90D9;
  border-color: #4A90D9;
  color: #fff;
}

.checkbox-row {
  justify-content: flex-start;
}

.checkbox-row input[type="checkbox"] {
  width: 16px;
  height: 16px;
  accent-color: #4A90D9;
  cursor: pointer;
}

.small-btn {
  width: 24px;
  height: 24px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #0f3460;
  border: 1px solid #1a1a4e;
  color: #eee;
  border-radius: 3px;
  cursor: pointer;
  font-size: 14px;
  padding: 0;
  transition: background 0.15s;
}

.small-btn:hover {
  background: #1a4a7a;
}

.small-btn:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}

.col-count {
  font-size: 13px;
  color: #eee;
  min-width: 16px;
  text-align: center;
}

.group-label {
  display: block;
  font-size: 11px;
  color: #888;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin: 8px 0 4px 0;
  padding-bottom: 3px;
  border-bottom: 1px solid #0f3460;
}

.header-editor {
  margin-bottom: 4px;
}

.header-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 4px 6px;
  background: #0a1a3a;
  border-radius: 3px;
  cursor: pointer;
  transition: background 0.15s;
}

.header-row:hover {
  background: #0f2a4a;
}

.header-label {
  font-size: 11px;
  color: #ccc;
}

.expand-arrow {
  font-size: 10px;
  color: #888;
}

.header-detail {
  padding: 6px 4px;
  background: #0a1530;
  border-radius: 0 0 3px 3px;
  margin-top: 2px;
}

.header-detail .field-row {
  margin-bottom: 4px;
}

input[type="text"] {
  flex: 1;
  background: #0f3460;
  border: 1px solid #1a1a4e;
  color: #eee;
  padding: 4px 6px;
  border-radius: 3px;
  font-size: 12px;
  min-width: 0;
}

.style-if-rule {
  background: #0a1530;
  border-radius: 3px;
  padding: 6px;
  margin-bottom: 4px;
  position: relative;
}

.remove-rule-btn {
  position: absolute;
  top: 4px;
  right: 4px;
  background: #e74c3c;
  border: none;
  color: white;
  width: 16px;
  height: 16px;
  border-radius: 50%;
  font-size: 10px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  line-height: 1;
}

.add-rule-btn {
  background: #1a4a8a;
  border: 1px solid #2a6ac9;
  color: #a0c4f0;
  padding: 4px 8px;
  border-radius: 3px;
  font-size: 11px;
  cursor: pointer;
  width: 100%;
}

.add-rule-btn:hover {
  background: #2a6ac9;
  color: white;
}
</style>
