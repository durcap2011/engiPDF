<script setup lang="ts">
import { computed, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useEditorStore } from '../../stores/editorStore'
import { BULLET_TYPES } from '../../utils/bulletTypes'
import { flattenSampleData } from '../../utils/fieldOptions'
import type { ListItem } from '../../types'

const store = useEditorStore()
const { t } = useI18n()

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
      <h3 class="section-title">{{ t('properties.page') }}</h3>
      <div class="field-row">
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
      <div class="dimensions-compact">
        <div class="dim-input-group" :title="t('properties.width')">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12H3m0 0l4-4m-4 4l4 4m14-8v8m0 0l-3-3m3 3l3-3"/></svg>
          <input
            type="number"
            :value="store.activePage.settings.width"
            @input="updatePage('width', +($event.target as HTMLInputElement).value)"
            min="50" max="1000" step="1"
          />
        </div>
        <div class="dim-input-group" :title="t('properties.height')">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v18m0 0l-4-4m4 4l4-4M3 12h18m0 0l-4-4m4 4l-4-4"/></svg>
          <input
            type="number"
            :value="store.activePage.settings.height"
            @input="updatePage('height', +($event.target as HTMLInputElement).value)"
            min="50" max="1000" step="1"
          />
        </div>
      </div>
      <div class="margins-compact">
        <div class="margin-input-wrap" :title="t('properties.marginTop')">
          <span class="margin-icon">Y↑</span>
          <input
            type="number"
            :value="store.activePage.settings.margins.top"
            @input="updateMargin('top', +($event.target as HTMLInputElement).value)"
            min="0" max="100" step="1"
          />
        </div>
        <div class="margin-input-wrap" :title="t('properties.marginRight')">
          <span class="margin-icon">X→</span>
          <input
            type="number"
            :value="store.activePage.settings.margins.right"
            @input="updateMargin('right', +($event.target as HTMLInputElement).value)"
            min="0" max="100" step="1"
          />
        </div>
        <div class="margin-input-wrap" :title="t('properties.marginBottom')">
          <span class="margin-icon">Y↓</span>
          <input
            type="number"
            :value="store.activePage.settings.margins.bottom"
            @input="updateMargin('bottom', +($event.target as HTMLInputElement).value)"
            min="0" max="100" step="1"
          />
        </div>
        <div class="margin-input-wrap" :title="t('properties.marginLeft')">
          <span class="margin-icon">←X</span>
          <input
            type="number"
            :value="store.activePage.settings.margins.left"
            @input="updateMargin('left', +($event.target as HTMLInputElement).value)"
            min="0" max="100" step="1"
          />
        </div>
      </div>
      <div class="header-footer-compact">
        <div class="hf-input-wrap" :title="t('properties.headerTooltip')">
          <span class="hf-icon">⊤</span>
          <input
            type="number"
            :value="store.activePage.settings.headerHeight"
            @input="updatePage('headerHeight', +($event.target as HTMLInputElement).value)"
            min="0" max="100" step="1"
          />
        </div>
        <div class="hf-input-wrap" :title="t('properties.footerTooltip')">
          <span class="hf-icon">⊥</span>
          <input
            type="number"
            :value="store.activePage.settings.footerHeight"
            @input="updatePage('footerHeight', +($event.target as HTMLInputElement).value)"
            min="0" max="100" step="1"
          />
        </div>
      </div>
      <div class="field-row">
        <label>{{ t('properties.copyHeaderFrom') }}</label>
        <select :value="store.activePage.settings.headerSourcePageId || ''" @change="copyHeaderFrom(($event.target as HTMLSelectElement).value)">
          <option value="">{{ t('properties.none') }}</option>
          <option v-for="page in availablePages" :key="page.id" :value="page.id">
            {{ t('properties.pageNName', { n: store.document.pages.indexOf(page) + 1, name: page.name || '' }) }}
          </option>
        </select>
      </div>
      <div class="field-row">
        <label>{{ t('properties.copyFooterFrom') }}</label>
        <select :value="store.activePage.settings.footerSourcePageId || ''" @change="copyFooterFrom(($event.target as HTMLSelectElement).value)">
          <option value="">{{ t('properties.none') }}</option>
          <option v-for="page in availablePages" :key="page.id" :value="page.id">
            {{ t('properties.pageNName', { n: store.document.pages.indexOf(page) + 1, name: page.name || '' }) }}
          </option>
        </select>
      </div>
    </div>

    <!-- Proprietà Elemento Selezionato -->
    <div v-if="multiSelected" class="panel-section">
      <h3 class="section-title">
        {{ t('properties.selectedElements', { count: store.selectedIds.length }) }}
      </h3>
    </div>

    <div v-else-if="selected" class="panel-section">
      <h3 class="section-title">
        {{ t('properties.propertiesOf', { type: selected.type }) }}
      </h3>

      <!-- Posizione -->
      <div class="field-row">
        <label>X</label>
        <input type="number" :value="selected.x" @input="update('x', +($event.target as HTMLInputElement).value)" step="1" />
        <span class="unit">{{ t('properties.mm') }}</span>
      </div>
      <div class="field-row">
        <label>Y</label>
        <input type="number" :value="selected.y" @input="update('y', +($event.target as HTMLInputElement).value)" step="1" />
        <span class="unit">{{ t('properties.mm') }}</span>
      </div>
      <div class="field-row">
        <label>{{ t('properties.width') }}</label>
        <input type="number" :value="selected.width" @input="update('width', +($event.target as HTMLInputElement).value)" step="1" />
        <span class="unit">{{ t('properties.mm') }}</span>
      </div>
      <div class="field-row">
        <label>{{ t('properties.height') }}</label>
        <input type="number" :value="selected.height" @input="update('height', +($event.target as HTMLInputElement).value)" step="1" />
        <span class="unit">{{ t('properties.mm') }}</span>
      </div>

      <!-- Testo -->
      <template v-if="selected.type === 'text'">
        <div class="field-group">
          <label>{{ t('properties.text') }}</label>
          <textarea
            :value="(selected as any).text"
            @input="update('text', ($event.target as HTMLTextAreaElement).value)"
            rows="3"
          ></textarea>
        </div>
        <div class="field-row">
          <label>{{ t('properties.font') }}</label>
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
          <label>{{ t('properties.size') }}</label>
          <input type="number" :value="(selected as any).style?.size" @input="updateStyle('size', +($event.target as HTMLInputElement).value)" min="4" max="72" step="1" />
          <span class="unit">pt</span>
        </div>
        <div class="field-row">
          <label>{{ t('properties.style') }}</label>
          <div class="style-toggles">
            <button
              class="style-toggle"
              :class="{ active: (selected as any).style?.weight === 'bold' }"
              @click="updateStyle('weight', (selected as any).style?.weight === 'bold' ? 'normal' : 'bold')"
              :title="t('properties.bold')"
            >B</button>
            <button
              class="style-toggle italic"
              :class="{ active: (selected as any).style?.style === 'italic' }"
              @click="updateStyle('style', (selected as any).style?.style === 'italic' ? 'normal' : 'italic')"
              :title="t('properties.italic')"
            >I</button>
            <button
              class="style-toggle underline"
              :class="{ active: (selected as any).style?.underline }"
              @click="updateStyle('underline', !(selected as any).style?.underline)"
              :title="t('properties.underline')"
            >U</button>
          </div>
        </div>
        <div class="field-row">
          <label>{{ t('properties.color') }}</label>
          <input
            type="color"
            :value="rgbToHex((selected as any).style?.color || [0,0,0])"
            @input="updateStyle('color', hexToRgb(($event.target as HTMLInputElement).value))"
          />
        </div>
        <div class="field-row">
          <label>{{ t('properties.alignment') }}</label>
          <div class="align-toggles">
            <button
              class="align-toggle"
              :class="{ active: (selected as any).style?.align === 'left' }"
              @click="updateStyle('align', 'left')"
              :title="t('properties.alignLeft')"
            >
              <svg width="16" height="16" viewBox="0 0 16 16"><path d="M1 2h14v2H1zm0 4h8v2H1zm0 4h10v2H1zm0 4h6v2H1z" fill="currentColor"/></svg>
            </button>
            <button
              class="align-toggle"
              :class="{ active: (selected as any).style?.align === 'center' }"
              @click="updateStyle('align', 'center')"
              :title="t('properties.alignCenter')"
            >
              <svg width="16" height="16" viewBox="0 0 16 16"><path d="M1 2h14v2H1zm2 4h10v2H3zm1 4h8v2H4zm1 4h6v2H5z" fill="currentColor"/></svg>
            </button>
            <button
              class="align-toggle"
              :class="{ active: (selected as any).style?.align === 'right' }"
              @click="updateStyle('align', 'right')"
              :title="t('properties.alignRight')"
            >
              <svg width="16" height="16" viewBox="0 0 16 16"><path d="M1 2h14v2H1zm6 4h8v2H7zm4 4h6v2h-6zm2 4h6v2H9z" fill="currentColor"/></svg>
            </button>
            <button
              class="align-toggle"
              :class="{ active: (selected as any).style?.align === 'justify' }"
              @click="updateStyle('align', 'justify')"
              :title="t('properties.alignJustify')"
            >
              <svg width="16" height="16" viewBox="0 0 16 16"><path d="M1 2h14v2H1zm0 4h14v2H1zm0 4h14v2H1zm0 4h14v2H1z" fill="currentColor"/></svg>
            </button>
          </div>
        </div>
      </template>

      <!-- Rettangolo -->
      <template v-if="selected.type === 'rectangle'">
        <div class="field-row">
          <label>{{ t('properties.fill') }}</label>
          <input
            type="color"
            :value="rgbToHex((selected as any).fill || [0.9,0.9,0.9])"
            @input="update('fill', hexToRgb(($event.target as HTMLInputElement).value))"
          />
        </div>
        <div class="field-row">
          <label>{{ t('properties.border') }}</label>
          <input
            type="color"
            :value="rgbToHex((selected as any).stroke || [0,0,0])"
            @input="update('stroke', hexToRgb(($event.target as HTMLInputElement).value))"
          />
        </div>
        <div class="field-row">
          <label>{{ t('properties.borderWidth') }}</label>
          <input type="number" :value="(selected as any).strokeWidth" @input="update('strokeWidth', +($event.target as HTMLInputElement).value)" min="0" max="5" step="0.1" />
          <span class="unit">{{ t('properties.mm') }}</span>
        </div>
      </template>

      <!-- Lista -->
      <template v-if="selected.type === 'list'">
        <div class="field-group">
          <label>{{ t('properties.items') }}</label>
          <div v-for="(item, idx) in (selected as any).items" :key="idx" class="list-item-edit">
            <input
              :value="item.text"
              @input="updateItems(idx, ($event.target as HTMLInputElement).value)"
              :placeholder="t('properties.itemTextPlaceholder')"
            />
          </div>
        </div>
        <div class="field-row">
          <label>{{ t('properties.font') }}</label>
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
          <label>{{ t('properties.size') }}</label>
          <input type="number" :value="(selected as any).style?.size" @input="updateStyle('size', +($event.target as HTMLInputElement).value)" min="4" max="72" step="1" />
          <span class="unit">pt</span>
        </div>
        <div class="field-row">
          <label>{{ t('properties.style') }}</label>
          <div class="style-toggles">
            <button
              class="style-toggle"
              :class="{ active: (selected as any).style?.weight === 'bold' }"
              @click="updateStyle('weight', (selected as any).style?.weight === 'bold' ? 'normal' : 'bold')"
              :title="t('properties.bold')"
            >B</button>
            <button
              class="style-toggle italic"
              :class="{ active: (selected as any).style?.style === 'italic' }"
              @click="updateStyle('style', (selected as any).style?.style === 'italic' ? 'normal' : 'italic')"
              :title="t('properties.italic')"
            >I</button>
            <button
              class="style-toggle underline"
              :class="{ active: (selected as any).style?.underline }"
              @click="updateStyle('underline', !(selected as any).style?.underline)"
              :title="t('properties.underline')"
            >U</button>
          </div>
        </div>
        <div class="field-row">
          <label>{{ t('properties.bullet') }}</label>
          <select :value="getBulletForLevel(0)" @change="updateBulletForLevel(0, ($event.target as HTMLSelectElement).value)">
            <option v-for="b in BULLET_TYPES" :key="b.id" :value="b.id">{{ b.label }}</option>
          </select>
        </div>
        <div v-for="lvl in maxListLevel" :key="lvl" class="field-row">
          <label>{{ t('properties.level', { n: lvl }) }}</label>
          <select :value="getBulletForLevel(lvl)" @change="updateBulletForLevel(lvl, ($event.target as HTMLSelectElement).value)">
            <option v-for="b in BULLET_TYPES" :key="b.id" :value="b.id">{{ b.label }}</option>
          </select>
        </div>
      </template>

      <!-- Immagine -->
      <template v-if="selected.type === 'image'">
        <div class="field-row">
          <label>{{ t('properties.image') }}</label>
          <button class="upload-btn" @click="triggerImageUpload">
            {{ (selected as any).src ? t('properties.change') : t('properties.upload') }}
          </button>
        </div>
        <div v-if="(selected as any).src" class="field-row">
          <label></label>
          <button class="upload-btn remove" @click="update('src', '')">{{ t('properties.removeImage') }}</button>
        </div>
        <div class="field-row">
          <label>{{ t('properties.fit') }}</label>
          <select :value="(selected as any).fit" @change="update('fit', ($event.target as HTMLSelectElement).value)">
            <option value="contain">{{ t('properties.fitContain') }}</option>
            <option value="cover">{{ t('properties.fitCover') }}</option>
            <option value="stretch">{{ t('properties.fitStretch') }}</option>
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
          <label>{{ t('properties.name') }}</label>
          <input type="text" :value="(selected as any).name" @input="update('name', ($event.target as HTMLInputElement).value)" placeholder="es. articoli" />
        </div>

        <!-- Colonne -->
        <div class="field-group">
          <div class="field-row">
            <label>{{ t('properties.columns') }}</label>
            <span class="col-count">{{ (selected as any).columns?.length }}</span>
            <button class="small-btn" @click="addTableColumn">+</button>
            <button class="small-btn" @click="removeTableColumn((selected as any).columns.length - 1)" :disabled="(selected as any).columns?.length <= 1">−</button>
          </div>
        </div>

        <!-- Intestazioni colonne -->
        <div class="field-group">
          <label class="group-label">{{ t('properties.headers') }}</label>
          <div v-for="(col, colIdx) in (selected as any).columns" :key="colIdx" class="header-editor">
            <div class="header-row" @click="expandedHeader = expandedHeader === colIdx ? null : colIdx">
              <span class="header-label">Col {{ colIdx + 1 }}: {{ col.header }}</span>
              <span class="expand-arrow">{{ expandedHeader === colIdx ? '▾' : '▸' }}</span>
            </div>
            <div v-if="expandedHeader === colIdx" class="header-detail">
              <div class="field-row">
                <label>{{ t('properties.text') }}</label>
                <input type="text" :value="col.header" @input="updateTableHeader(colIdx, ($event.target as HTMLInputElement).value)" />
              </div>
              <div class="field-row">
                <label>{{ t('properties.width') }}</label>
                <input type="number" :value="col.width" @input="updateTableHeaderStyle(colIdx, 'width', +($event.target as HTMLInputElement).value)" min="10" max="200" step="5" />
                <span class="unit">{{ t('properties.weight') }}</span>
              </div>
              <div class="field-row">
                <label>{{ t('properties.font') }}</label>
                <select :value="col.headerStyle?.font" @change="updateTableHeaderStyle(colIdx, 'font', ($event.target as HTMLSelectElement).value)">
                  <option value="helvetica">Helvetica</option>
                  <option value="arial">Arial</option>
                  <option value="times">Times</option>
                  <option value="courier">Courier</option>
                </select>
              </div>
              <div class="field-row">
                <label>{{ t('properties.sizeShort') }}</label>
                <input type="number" :value="col.headerStyle?.size" @input="updateTableHeaderStyle(colIdx, 'size', +($event.target as HTMLInputElement).value)" min="4" max="72" step="1" />
                <span class="unit">pt</span>
              </div>
              <div class="field-row">
                <label>{{ t('properties.style') }}</label>
                <div class="style-toggles">
                  <button class="style-toggle" :class="{ active: col.headerStyle?.weight === 'bold' }" @click="updateTableHeaderStyle(colIdx, 'weight', col.headerStyle?.weight === 'bold' ? 'normal' : 'bold')" :title="t('properties.bold')">B</button>
                  <button class="style-toggle italic" :class="{ active: col.headerStyle?.style === 'italic' }" @click="updateTableHeaderStyle(colIdx, 'style', col.headerStyle?.style === 'italic' ? 'normal' : 'italic')" :title="t('properties.italic')">I</button>
                </div>
              </div>
              <div class="field-row">
                <label>{{ t('properties.align') }}</label>
                <div class="style-toggles">
                  <button class="style-toggle" :class="{ active: col.headerStyle?.align === 'left' }" @click="updateTableHeaderStyle(colIdx, 'align', 'left')">L</button>
                  <button class="style-toggle" :class="{ active: col.headerStyle?.align === 'center' }" @click="updateTableHeaderStyle(colIdx, 'align', 'center')">C</button>
                  <button class="style-toggle" :class="{ active: col.headerStyle?.align === 'right' }" @click="updateTableHeaderStyle(colIdx, 'align', 'right')">R</button>
                </div>
              </div>
              <div class="field-row">
                <label>{{ t('properties.color') }}</label>
                <input type="color" :value="rgbToHex(col.headerStyle?.color || [0,0,0])" @input="updateTableHeaderStyle(colIdx, 'color', hexToRgb(($event.target as HTMLInputElement).value))" />
              </div>
              <div class="field-row">
                <label>{{ t('properties.background') }}</label>
                <input type="color" :value="rgbToHex((selected as any).rows?.[0]?.cells?.[colIdx]?.style?.background || [1,1,1])" @input="updateTableCellStyle(colIdx, 'background', hexToRgb(($event.target as HTMLInputElement).value))" />
              </div>
            </div>
          </div>
        </div>

        <!-- Stile celle dati per colonna -->
        <div class="field-group">
          <label class="group-label">{{ t('properties.dataCellStylePerColumn') }}</label>
          <div v-for="(col, colIdx) in (selected as any).columns" :key="'cell-' + colIdx" class="header-editor">
            <div class="header-row" @click="expandedCell = expandedCell === colIdx ? null : colIdx">
              <span class="header-label">Col {{ colIdx + 1 }}: {{ col.header }}</span>
              <span class="expand-arrow">{{ expandedCell === colIdx ? '▾' : '▸' }}</span>
            </div>
            <div v-if="expandedCell === colIdx" class="header-detail">
              <div class="field-row">
                <label>{{ t('properties.font') }}</label>
                <select :value="(selected as any).rows?.[0]?.cells?.[colIdx]?.style?.font || (selected as any).cellStyle?.font" @change="updateTableCellStyle(colIdx, 'font', ($event.target as HTMLSelectElement).value)">
                  <option value="helvetica">Helvetica</option>
                  <option value="arial">Arial</option>
                  <option value="times">Times</option>
                  <option value="courier">Courier</option>
                </select>
              </div>
              <div class="field-row">
                <label>{{ t('properties.sizeShort') }}</label>
                <input type="number" :value="(selected as any).rows?.[0]?.cells?.[colIdx]?.style?.size || (selected as any).cellStyle?.size" @input="updateTableCellStyle(colIdx, 'size', +($event.target as HTMLInputElement).value)" min="4" max="72" step="1" />
                <span class="unit">pt</span>
              </div>
              <div class="field-row">
                <label>{{ t('properties.style') }}</label>
                <div class="style-toggles">
                  <button class="style-toggle" :class="{ active: (selected as any).rows?.[0]?.cells?.[colIdx]?.style?.weight === 'bold' }" @click="updateTableCellStyle(colIdx, 'weight', (selected as any).rows?.[0]?.cells?.[colIdx]?.style?.weight === 'bold' ? 'normal' : 'bold')" :title="t('properties.bold')">B</button>
                  <button class="style-toggle italic" :class="{ active: (selected as any).rows?.[0]?.cells?.[colIdx]?.style?.style === 'italic' }" @click="updateTableCellStyle(colIdx, 'style', (selected as any).rows?.[0]?.cells?.[colIdx]?.style?.style === 'italic' ? 'normal' : 'italic')" :title="t('properties.italic')">I</button>
                </div>
              </div>
              <div class="field-row">
                <label>{{ t('properties.align') }}</label>
                <div class="style-toggles">
                  <button class="style-toggle" :class="{ active: ((selected as any).rows?.[0]?.cells?.[colIdx]?.style?.align || (selected as any).cellStyle?.align) === 'left' }" @click="updateTableCellStyle(colIdx, 'align', 'left')">L</button>
                  <button class="style-toggle" :class="{ active: ((selected as any).rows?.[0]?.cells?.[colIdx]?.style?.align || (selected as any).cellStyle?.align) === 'center' }" @click="updateTableCellStyle(colIdx, 'align', 'center')">C</button>
                  <button class="style-toggle" :class="{ active: ((selected as any).rows?.[0]?.cells?.[colIdx]?.style?.align || (selected as any).cellStyle?.align) === 'right' }" @click="updateTableCellStyle(colIdx, 'align', 'right')">R</button>
                </div>
              </div>
              <div class="field-row">
                <label>{{ t('properties.color') }}</label>
                <input type="color" :value="rgbToHex((selected as any).rows?.[0]?.cells?.[colIdx]?.style?.color || (selected as any).cellStyle?.color || [0,0,0])" @input="updateTableCellStyle(colIdx, 'color', hexToRgb(($event.target as HTMLInputElement).value))" />
              </div>
            </div>
          </div>
        </div>

        <!-- Stile celle dati (default) -->
        <div class="field-group">
          <label class="group-label">{{ t('properties.dataCellStyleDefault') }}</label>
          <div class="field-row">
            <label>{{ t('properties.font') }}</label>
            <select :value="(selected as any).cellStyle?.font" @change="updateTableDefaultStyle('font', ($event.target as HTMLSelectElement).value)">
              <option value="helvetica">Helvetica</option>
              <option value="arial">Arial</option>
              <option value="times">Times</option>
              <option value="courier">Courier</option>
            </select>
          </div>
          <div class="field-row">
            <label>{{ t('properties.sizeShort') }}</label>
            <input type="number" :value="(selected as any).cellStyle?.size" @input="updateTableDefaultStyle('size', +($event.target as HTMLInputElement).value)" min="4" max="72" step="1" />
            <span class="unit">pt</span>
          </div>
          <div class="field-row">
            <label>{{ t('properties.align') }}</label>
            <div class="style-toggles">
              <button class="style-toggle" :class="{ active: (selected as any).cellStyle?.align === 'left' }" @click="updateTableDefaultStyle('align', 'left')">L</button>
              <button class="style-toggle" :class="{ active: (selected as any).cellStyle?.align === 'center' }" @click="updateTableDefaultStyle('align', 'center')">C</button>
              <button class="style-toggle" :class="{ active: (selected as any).cellStyle?.align === 'right' }" @click="updateTableDefaultStyle('align', 'right')">R</button>
            </div>
          </div>
          <div class="field-row">
            <label>{{ t('properties.color') }}</label>
            <input type="color" :value="rgbToHex((selected as any).cellStyle?.color || [0,0,0])" @input="updateTableDefaultCellStyle('color', hexToRgb(($event.target as HTMLInputElement).value))" />
          </div>
          <div class="field-row">
            <label>{{ t('properties.background') }}</label>
            <input type="color" :value="rgbToHex((selected as any).cellStyle?.background || [1,1,1])" @input="updateTableDefaultCellStyle('background', hexToRgb(($event.target as HTMLInputElement).value))" />
          </div>
        </div>

        <!-- Bordi -->
        <div class="field-row">
          <label>{{ t('properties.borderColor') }}</label>
          <input type="color" :value="rgbToHex((selected as any).borderColor || [0,0,0])" @input="update('borderColor', hexToRgb(($event.target as HTMLInputElement).value))" />
        </div>
        <div class="field-row">
          <label>{{ t('properties.thickness') }}</label>
          <input type="number" :value="(selected as any).borderWidth" @input="update('borderWidth', +($event.target as HTMLInputElement).value)" min="0" max="5" step="0.1" />
          <span class="unit">{{ t('properties.mm') }}</span>
        </div>

        <!-- Ripeti intestazione -->
        <div class="field-row checkbox-row">
          <label>{{ t('properties.repeatHeader') }}</label>
          <input
            type="checkbox"
            :checked="(selected as any).repeatHeader"
            @change="update('repeatHeader', ($event.target as HTMLInputElement).checked)"
          />
        </div>
      </template>

      <template v-if="selected.type === 'ellipse'">
        <div class="field-row">
          <label>{{ t('properties.fill') }}</label>
          <input type="color" :value="toHex(selected.fill || [0.9,0.9,0.9])" @input="update('fill', fromHex(($event.target as HTMLInputElement).value))" />
        </div>
        <div class="field-row">
          <label>{{ t('properties.border') }}</label>
          <input type="color" :value="toHex(selected.stroke || [0,0,0])" @input="update('stroke', fromHex(($event.target as HTMLInputElement).value))" />
        </div>
        <div class="field-row">
          <label>{{ t('properties.borderWidth') }}</label>
          <input type="number" :value="selected.strokeWidth ?? 0.5" step="0.1" min="0" @input="update('strokeWidth', +($event.target as HTMLInputElement).value)" />
        </div>
      </template>

      <template v-if="selected.type === 'divider'">
        <div class="field-row">
          <label>{{ t('properties.color') }}</label>
          <input type="color" :value="toHex(selected.color)" @input="update('color', fromHex(($event.target as HTMLInputElement).value))" />
        </div>
        <div class="field-row">
          <label>{{ t('properties.thickness') }}</label>
          <input type="number" :value="selected.lineWidth" step="0.1" min="0.1" @input="update('lineWidth', +($event.target as HTMLInputElement).value)" />
        </div>
        <div class="field-row">
          <label>{{ t('properties.style') }}</label>
          <select :value="selected.lineStyle" @change="update('lineStyle', ($event.target as HTMLSelectElement).value)">
            <option value="solid">{{ t('properties.solid') }}</option>
            <option value="dashed">{{ t('properties.dashed') }}</option>
            <option value="dotted">{{ t('properties.dotted') }}</option>
          </select>
        </div>
      </template>

      <template v-if="selected.type === 'signature'">
        <div class="field-row">
          <label>{{ t('properties.label') }}</label>
          <input type="text" :value="selected.label" @input="update('label', ($event.target as HTMLInputElement).value)" />
        </div>
        <div class="field-row">
          <label>{{ t('properties.color') }}</label>
          <input type="color" :value="toHex(selected.color)" @input="update('color', fromHex(($event.target as HTMLInputElement).value))" />
        </div>
      </template>

      <template v-if="selected.type === 'container'">
        <div class="field-row">
          <label>{{ t('properties.fill') }}</label>
          <input type="color" :value="toHex(selected.fill || [1,1,1])" @input="update('fill', fromHex(($event.target as HTMLInputElement).value))" />
        </div>
        <div class="field-row">
          <label>{{ t('properties.borderColor') }}</label>
          <input type="color" :value="toHex(selected.borderColor)" @input="update('borderColor', fromHex(($event.target as HTMLInputElement).value))" />
        </div>
        <div class="field-row">
          <label>{{ t('properties.borderWidth') }}</label>
          <input type="number" :value="selected.borderWidth" step="0.1" min="0" @input="update('borderWidth', +($event.target as HTMLInputElement).value)" />
        </div>
      </template>

      <template v-if="selected.type === 'pageNumber'">
        <div class="section-title">{{ t('properties.textStyle') }}</div>
        <div class="field-row">
          <label>{{ t('properties.font') }}</label>
          <select :value="selected.style.font" @change="updateStyle('font', ($event.target as HTMLSelectElement).value)">
            <option value="helvetica">Helvetica</option>
            <option value="times">Times</option>
            <option value="courier">Courier</option>
          </select>
        </div>
        <div class="field-row">
          <label>{{ t('properties.size') }}</label>
          <input type="number" :value="selected.style.size" min="1" @input="updateStyle('size', +($event.target as HTMLInputElement).value)" />
        </div>
        <div class="field-row">
          <label>{{ t('properties.color') }}</label>
          <input type="color" :value="toHex(selected.style.color)" @input="updateStyle('color', fromHex(($event.target as HTMLInputElement).value))" />
        </div>
        <div class="field-row">
          <label>{{ t('properties.bold') }}</label>
          <input type="checkbox" :checked="selected.style.weight === 'bold'" @change="updateStyle('weight', ($event.target as HTMLInputElement).checked ? 'bold' : 'normal')" />
        </div>
        <div class="field-row">
          <label>{{ t('properties.alignment') }}</label>
          <select :value="selected.style.align" @change="updateStyle('align', ($event.target as HTMLSelectElement).value)">
            <option value="left">{{ t('properties.left') }}</option>
            <option value="center">{{ t('properties.center') }}</option>
            <option value="right">{{ t('properties.right') }}</option>
          </select>
        </div>
      </template>

      <template v-if="selected.type === 'date'">
        <div class="field-row">
          <label>{{ t('properties.format') }}</label>
          <input type="text" :value="selected.format" @input="update('format', ($event.target as HTMLInputElement).value)" />
        </div>
        <div class="section-title">{{ t('properties.textStyle') }}</div>
        <div class="field-row">
          <label>{{ t('properties.font') }}</label>
          <select :value="selected.style.font" @change="updateStyle('font', ($event.target as HTMLSelectElement).value)">
            <option value="helvetica">Helvetica</option>
            <option value="times">Times</option>
            <option value="courier">Courier</option>
          </select>
        </div>
        <div class="field-row">
          <label>{{ t('properties.size') }}</label>
          <input type="number" :value="selected.style.size" min="1" @input="updateStyle('size', +($event.target as HTMLInputElement).value)" />
        </div>
        <div class="field-row">
          <label>{{ t('properties.color') }}</label>
          <input type="color" :value="toHex(selected.style.color)" @input="updateStyle('color', fromHex(($event.target as HTMLInputElement).value))" />
        </div>
        <div class="field-row">
          <label>{{ t('properties.bold') }}</label>
          <input type="checkbox" :checked="selected.style.weight === 'bold'" @change="updateStyle('weight', ($event.target as HTMLInputElement).checked ? 'bold' : 'normal')" />
        </div>
        <div class="field-row">
          <label>{{ t('properties.alignment') }}</label>
          <select :value="selected.style.align" @change="updateStyle('align', ($event.target as HTMLSelectElement).value)">
            <option value="left">{{ t('properties.left') }}</option>
            <option value="center">{{ t('properties.center') }}</option>
            <option value="right">{{ t('properties.right') }}</option>
          </select>
        </div>
      </template>

      <template v-if="selected.type === 'watermark'">
        <div class="field-row">
          <label>{{ t('properties.text') }}</label>
          <input type="text" :value="selected.text" @input="update('text', ($event.target as HTMLInputElement).value)" />
        </div>
        <div class="field-row">
          <label>{{ t('properties.size') }}</label>
          <input type="number" :value="selected.fontSize" min="1" @input="update('fontSize', +($event.target as HTMLInputElement).value)" />
        </div>
        <div class="field-row">
          <label>{{ t('properties.color') }}</label>
          <input type="color" :value="toHex(selected.color)" @input="update('color', fromHex(($event.target as HTMLInputElement).value))" />
        </div>
        <div class="field-row">
          <label>{{ t('properties.rotation') }}</label>
          <input type="number" :value="selected.rotation" step="1" @input="update('rotation', +($event.target as HTMLInputElement).value)" />
        </div>
        <div class="field-row">
          <label>{{ t('properties.opacity') }}</label>
          <input type="range" min="0" max="1" step="0.05" :value="selected.opacity" @input="update('opacity', +($event.target as HTMLInputElement).value)" />
          <span class="value-label">{{ Math.round(selected.opacity * 100) }}%</span>
        </div>
      </template>

      <template v-if="selected.type === 'qrcode'">
        <div class="field-row">
          <label>{{ t('properties.content') }}</label>
          <input type="text" :value="selected.text" @input="update('text', ($event.target as HTMLInputElement).value)" />
        </div>
      </template>

      <template v-if="selected.type === 'stamp'">
        <div class="field-row">
          <label>{{ t('properties.text') }}</label>
          <input type="text" :value="selected.text" @input="update('text', ($event.target as HTMLInputElement).value)" />
        </div>
        <div class="field-row">
          <label>{{ t('properties.preset') }}</label>
          <select :value="selected.preset" @change="update('preset', ($event.target as HTMLSelectElement).value)">
            <option value="approved">APPROVATO</option>
            <option value="confidential">RISERVATO</option>
            <option value="draft">BOZZA</option>
            <option value="paid">PAGATO</option>
            <option value="urgent">URGENTE</option>
          </select>
        </div>
        <div class="field-row">
          <label>{{ t('properties.color') }}</label>
          <input type="color" :value="toHex(selected.color)" @input="update('color', fromHex(($event.target as HTMLInputElement).value))" />
        </div>
      </template>

      <template v-if="selected.type === 'quote'">
        <div class="field-row">
          <label>{{ t('properties.quoteText') }}</label>
          <input type="text" :value="selected.text" @input="update('text', ($event.target as HTMLInputElement).value)" />
        </div>
        <div class="field-row">
          <label>{{ t('properties.author') }}</label>
          <input type="text" :value="selected.author" @input="update('author', ($event.target as HTMLInputElement).value)" />
        </div>
        <div class="field-row">
          <label>{{ t('properties.barColor') }}</label>
          <input type="color" :value="toHex(selected.barColor)" @input="update('barColor', fromHex(($event.target as HTMLInputElement).value))" />
        </div>
        <div class="section-title">{{ t('properties.textStyle') }}</div>
        <div class="field-row">
          <label>{{ t('properties.font') }}</label>
          <select :value="selected.style.font" @change="updateStyle('font', ($event.target as HTMLSelectElement).value)">
            <option value="helvetica">Helvetica</option>
            <option value="times">Times</option>
            <option value="courier">Courier</option>
          </select>
        </div>
        <div class="field-row">
          <label>{{ t('properties.size') }}</label>
          <input type="number" :value="selected.style.size" min="1" @input="updateStyle('size', +($event.target as HTMLInputElement).value)" />
        </div>
        <div class="field-row">
          <label>{{ t('properties.color') }}</label>
          <input type="color" :value="toHex(selected.style.color)" @input="updateStyle('color', fromHex(($event.target as HTMLInputElement).value))" />
        </div>
      </template>

      <template v-if="selected.type === 'callout'">
        <div class="field-row">
          <label>{{ t('properties.text') }}</label>
          <input type="text" :value="selected.text" @input="update('text', ($event.target as HTMLInputElement).value)" />
        </div>
        <div class="field-row">
          <label>{{ t('properties.style') }}</label>
          <select :value="selected.style" @change="update('style', ($event.target as HTMLSelectElement).value)">
            <option value="info">Info</option>
            <option value="warning">{{ t('properties.warning') }}</option>
            <option value="error">{{ t('properties.error') }}</option>
            <option value="success">{{ t('properties.success') }}</option>
          </select>
        </div>
        <div class="field-row">
          <label>{{ t('properties.icon') }}</label>
          <input type="text" :value="selected.icon" @input="update('icon', ($event.target as HTMLInputElement).value)" />
        </div>
        <div class="field-row">
          <label>{{ t('properties.bgColor') }}</label>
          <input type="color" :value="toHex(selected.bgColor)" @input="update('bgColor', fromHex(($event.target as HTMLInputElement).value))" />
        </div>
        <div class="field-row">
          <label>{{ t('properties.borderColor') }}</label>
          <input type="color" :value="toHex(selected.borderColor)" @input="update('borderColor', fromHex(($event.target as HTMLInputElement).value))" />
        </div>
      </template>

      <template v-if="selected.type === 'codeBlock'">
        <div class="field-row">
          <label>{{ t('properties.code') }}</label>
          <textarea :value="selected.text" @input="update('text', ($event.target as HTMLTextAreaElement).value)" rows="4"></textarea>
        </div>
        <div class="field-row">
          <label>{{ t('properties.language') }}</label>
          <input type="text" :value="selected.language" @input="update('language', ($event.target as HTMLInputElement).value)" />
        </div>
      </template>

      <template v-if="selected.type === 'progressBar'">
        <div class="field-row">
          <label>{{ t('properties.value') }}</label>
          <input type="range" min="0" max="100" :value="selected.value" @input="update('value', +($event.target as HTMLInputElement).value)" />
          <span class="value-label">{{ selected.value }}%</span>
        </div>
        <div class="field-row">
          <label>{{ t('properties.label') }}</label>
          <input type="text" :value="selected.label" @input="update('label', ($event.target as HTMLInputElement).value)" />
        </div>
        <div class="field-row">
          <label>{{ t('properties.fillColor') }}</label>
          <input type="color" :value="toHex(selected.color)" @input="update('color', fromHex(($event.target as HTMLInputElement).value))" />
        </div>
        <div class="field-row">
          <label>{{ t('properties.bgColor') }}</label>
          <input type="color" :value="toHex(selected.bgColor)" @input="update('bgColor', fromHex(($event.target as HTMLInputElement).value))" />
        </div>
      </template>

      <template v-if="selected.type === 'icon'">
        <div class="field-row">
          <label>{{ t('properties.icon') }}</label>
          <select :value="selected.name" @change="update('name', ($event.target as HTMLSelectElement).value)">
            <option value="check">Check</option>
            <option value="warning">Warning</option>
            <option value="info">Info</option>
            <option value="error">Error</option>
            <option value="star">{{ t('properties.star') }}</option>
            <option value="heart">{{ t('properties.heart') }}</option>
            <option value="arrow">{{ t('properties.arrow') }}</option>
          </select>
        </div>
        <div class="field-row">
          <label>{{ t('properties.color') }}</label>
          <input type="color" :value="toHex(selected.color)" @input="update('color', fromHex(($event.target as HTMLInputElement).value))" />
        </div>
      </template>

      <template v-if="selected.type === 'barcode'">
        <div class="field-row">
          <label>{{ t('properties.content') }}</label>
          <input type="text" :value="selected.text" @input="update('text', ($event.target as HTMLInputElement).value)" />
        </div>
        <div class="field-row">
          <label>{{ t('properties.format') }}</label>
          <select :value="selected.format" @change="update('format', ($event.target as HTMLSelectElement).value)">
            <option value="code128">Code 128</option>
            <option value="code39">Code 39</option>
            <option value="ean13">EAN-13</option>
          </select>
        </div>
        <div class="field-row">
          <label>{{ t('properties.showText') }}</label>
          <input type="checkbox" :checked="selected.showText" @change="update('showText', ($event.target as HTMLInputElement).checked)" />
        </div>
      </template>

      <template v-if="selected.type === 'chart'">
        <div class="field-row">
          <label>{{ t('properties.type') }}</label>
          <select :value="selected.chartType" @change="update('chartType', ($event.target as HTMLSelectElement).value)">
            <option value="bar">{{ t('properties.bar') }}</option>
            <option value="pie">{{ t('properties.pie') }}</option>
            <option value="line">{{ t('properties.line') }}</option>
          </select>
        </div>
        <div class="field-row">
          <label>{{ t('properties.dataJson') }}</label>
          <textarea :value="JSON.stringify(selected.data, null, 2)" @input="tryUpdateData(($event.target as HTMLTextAreaElement).value)" rows="4"></textarea>
        </div>
      </template>

      <template v-if="selected.type === 'pageBreak'">
        <div class="field-row">
          <label>{{ t('properties.pageBreak') }}</label>
          <span class="value-label">{{ t('properties.pageBreakDesc') }}</span>
        </div>
      </template>

      <template v-if="selected.type === 'dataRepeat'">
        <div class="field-row">
          <label>{{ t('properties.dataField') }}</label>
          <input type="text" :value="selected.repeatField" @input="update('repeatField', ($event.target as HTMLInputElement).value)" />
        </div>
        <div class="field-row">
          <label>{{ t('properties.direction') }}</label>
          <select :value="selected.direction" @change="update('direction', ($event.target as HTMLSelectElement).value)">
            <option value="vertical">{{ t('properties.vertical') }}</option>
            <option value="horizontal">{{ t('properties.horizontal') }}</option>
          </select>
        </div>
        <div class="field-row">
          <label>{{ t('properties.spacing') }}</label>
          <input type="number" :value="selected.spacing" min="0" @input="update('spacing', +($event.target as HTMLInputElement).value)" />
        </div>
      </template>

      <template v-if="selected.type === 'checklist'">
        <div class="section-title">{{ t('properties.checklistItems') }}</div>
        <div v-for="(item, idx) in selected.items" :key="idx" class="checklist-item-row">
          <input type="checkbox" :checked="item.checked" @change="toggleChecklistItem(idx)" />
          <input type="text" :value="item.text" @input="updateChecklistItemText(idx, ($event.target as HTMLInputElement).value)" />
          <button class="remove-rule-btn" @click="removeChecklistItem(idx)">✕</button>
        </div>
        <button class="add-rule-btn" @click="addChecklistItem()">+ {{ t('properties.addItem') }}</button>
        <div class="field-row">
          <label>{{ t('properties.size') }}</label>
          <input type="number" :value="selected.size" min="6" max="72" @input="update('size', +($event.target as HTMLInputElement).value)" />
          <span class="unit">pt</span>
        </div>
        <div class="field-row">
          <label>{{ t('properties.textColor') }}</label>
          <input type="color" :value="toHex(selected.color)" @input="update('color', fromHex(($event.target as HTMLInputElement).value))" />
        </div>
        <div class="field-row">
          <label>{{ t('properties.checkedColor') }}</label>
          <input type="color" :value="toHex(selected.checkedColor)" @input="update('checkedColor', fromHex(($event.target as HTMLInputElement).value))" />
        </div>
        <div class="field-row">
          <label>{{ t('properties.spacing') }}</label>
          <input type="number" :value="selected.gap" min="0" max="20" step="0.5" @input="update('gap', +($event.target as HTMLInputElement).value)" />
          <span class="unit">{{ t('properties.mm') }}</span>
        </div>
      </template>

      <template v-if="selected.type === 'radio'">
        <div class="section-title">{{ t('properties.radioItems') }}</div>
        <div v-for="(item, idx) in selected.items" :key="idx" class="checklist-item-row">
          <input type="radio" :name="'radio-' + selected.id" :checked="item.selected" @change="selectRadioItem(idx)" />
          <input type="text" :value="item.text" @input="updateRadioItemText(idx, ($event.target as HTMLInputElement).value)" />
          <button class="remove-rule-btn" @click="removeRadioItem(idx)">✕</button>
        </div>
        <button class="add-rule-btn" @click="addRadioItem()">+ {{ t('properties.addOption') }}</button>
        <div class="field-row">
          <label>{{ t('properties.size') }}</label>
          <input type="number" :value="selected.size" min="6" max="72" @input="update('size', +($event.target as HTMLInputElement).value)" />
          <span class="unit">pt</span>
        </div>
        <div class="field-row">
          <label>{{ t('properties.textColor') }}</label>
          <input type="color" :value="toHex(selected.color)" @input="update('color', fromHex(($event.target as HTMLInputElement).value))" />
        </div>
        <div class="field-row">
          <label>{{ t('properties.selectedColor') }}</label>
          <input type="color" :value="toHex(selected.selectedColor)" @input="update('selectedColor', fromHex(($event.target as HTMLInputElement).value))" />
        </div>
        <div class="field-row">
          <label>{{ t('properties.spacing') }}</label>
          <input type="number" :value="selected.gap" min="0" max="20" step="0.5" @input="update('gap', +($event.target as HTMLInputElement).value)" />
          <span class="unit">{{ t('properties.mm') }}</span>
        </div>
      </template>

      <div class="section-title programmabilita-title">{{ t('properties.programmability') }}</div>
        <div class="field-row">
          <label>{{ t('properties.showIf') }}</label>
          <input type="checkbox" :checked="!!selected.showIf" @change="toggleShowIf(($event.target as HTMLInputElement).checked)" />
        </div>
        <template v-if="selected.showIf">
          <div class="field-row">
            <label>{{ t('properties.field') }}</label>
            <select v-if="!customField['showIf']" :value="selected.showIf.field" @change="onShowIfFieldChange($event)">
              <option value="">{{ t('properties.selectField') }}</option>
              <option v-for="opt in fieldOptions" :key="opt.path" :value="opt.path">
                {{ opt.label }} → {{ formatPreview(opt.value) }}
              </option>
              <option value="__custom__">✏️ {{ t('properties.customize') }}</option>
            </select>
            <input v-else type="text" :value="selected.showIf.field === '__custom__' ? '' : selected.showIf.field" placeholder="es. ordine.stato" @input="updateShowIf('field', ($event.target as HTMLInputElement).value)" />
          </div>
          <div class="field-row">
            <label>{{ t('properties.operator') }}</label>
            <select :value="selected.showIf.op" @change="updateShowIf('op', ($event.target as HTMLSelectElement).value)">
              <option value="eq">{{ t('properties.eq') }}</option>
              <option value="neq">{{ t('properties.neq') }}</option>
              <option value="gt">{{ t('properties.gt') }}</option>
              <option value="lt">{{ t('properties.lt') }}</option>
              <option value="gte">{{ t('properties.gte') }}</option>
              <option value="lte">{{ t('properties.lte') }}</option>
              <option value="empty">{{ t('properties.emptyValue') }}</option>
              <option value="notempty">{{ t('properties.notEmpty') }}</option>
              <option value="contains">{{ t('properties.contains') }}</option>
            </select>
          </div>
          <div class="field-row" v-if="!['empty', 'notempty'].includes(selected.showIf.op)">
            <label>{{ t('properties.value') }}</label>
            <input type="text" :value="selected.showIf.value" @input="updateShowIf('value', ($event.target as HTMLInputElement).value)" />
          </div>
        </template>
        <div class="field-row">
          <label>{{ t('properties.styleIf') }}</label>
          <input type="checkbox" :checked="!!selected.styleIf?.length" @change="toggleStyleIf(($event.target as HTMLInputElement).checked)" />
        </div>
        <template v-if="selected.styleIf?.length">
          <div v-for="(rule, idx) in selected.styleIf" :key="idx" class="style-if-rule">
            <div class="field-row">
              <label>{{ t('properties.field') }}</label>
              <select v-if="!customField['styleIf_' + idx]" :value="rule.field" @change="onStyleIfFieldChange(idx, $event)">
                <option value="">{{ t('properties.selectField') }}</option>
                <option v-for="opt in fieldOptions" :key="opt.path" :value="opt.path">
                  {{ opt.label }} → {{ formatPreview(opt.value) }}
                </option>
                <option value="__custom__">✏️ {{ t('properties.customize') }}</option>
              </select>
              <input v-else type="text" :value="rule.field === '__custom__' ? '' : rule.field" placeholder="es. ordine.stato" @input="updateStyleIfRule(idx, 'field', ($event.target as HTMLInputElement).value)" />
            </div>
            <div class="field-row">
              <label>{{ t('properties.value') }}</label>
              <input type="text" :value="rule.value" @input="updateStyleIfRule(idx, 'value', ($event.target as HTMLInputElement).value)" />
            </div>
            <div class="field-row">
              <label>{{ t('properties.colorIfTrue') }}</label>
              <input type="color" :value="toHex((rule.then?.fill as any) || [0,0,0])" @input="updateStyleIfThen(idx, 'fill', fromHex(($event.target as HTMLInputElement).value) as any)" />
            </div>
            <button class="remove-rule-btn" @click="removeStyleIfRule(idx)">✕</button>
          </div>
          <button class="add-rule-btn" @click="addStyleIfRule()">+ {{ t('properties.addRule') }}</button>
        </template>
        <div class="field-row">
          <label>{{ t('properties.repeatOnAllPages') }}</label>
          <input type="checkbox" :checked="!!selected.repeatOnAllPages" @change="update('repeatOnAllPages', ($event.target as HTMLInputElement).checked || undefined)" />
        </div>
    </div>

    <div v-else class="panel-section empty">
      <p>{{ t('properties.empty') }}</p>
    </div>
    </div>
  </div>
</template>

<style scoped>
.property-panel {
  width: 260px;
  min-width: 260px;
  max-width: 260px;
  background: var(--bg-elevated);
  border-left: 1px solid var(--border-default);
  overflow: hidden;
}

.panel-scroll {
  height: 100%;
  overflow-y: auto;
  overflow-x: clip;
  padding: 12px;
}

.panel-section {
  margin-bottom: 16px;
}

.section-title {
  font-size: 10px;
  text-transform: uppercase;
  letter-spacing: 1.2px;
  color: var(--text-tertiary);
  margin-bottom: 10px;
  padding-bottom: 6px;
  border-bottom: 1px solid var(--border-subtle);
  font-weight: 600;
}

.programmabilita-title {
  color: var(--text-danger);
  border-bottom-color: var(--text-danger);
  margin-top: 8px;
}

.field-row {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 10px;
  min-width: 0;
}

.field-row label {
  font-size: 12px;
  color: var(--text-secondary);
  min-width: 60px;
  flex-shrink: 0;
  font-weight: 400;
}

.field-row input[type="number"],
.field-row select {
  flex: 1;
  background: var(--bg-inset);
  border: 1px solid var(--border-subtle);
  color: var(--text-primary);
  padding: 5px 8px;
  border-radius: var(--radius-sm);
  font-size: 12px;
  font-family: inherit;
  transition: border-color 0.2s, box-shadow 0.2s;
}

.field-row input[type="number"]:focus,
.field-row select:focus {
  outline: none;
  border-color: var(--border-accent);
  box-shadow: 0 0 0 3px var(--bg-accent-subtle);
}

.field-row input[type="color"] {
  width: 32px;
  height: 28px;
  padding: 2px;
  border: 1px solid var(--border-subtle);
  border-radius: var(--radius-sm);
  cursor: pointer;
  background: var(--bg-inset);
}

.unit {
  font-size: 11px;
  color: var(--text-tertiary);
  min-width: 20px;
}

.margins-compact {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 4px;
  margin-bottom: 10px;
}

.margin-input-wrap {
  display: flex;
  align-items: center;
  gap: 2px;
  background: var(--bg-inset);
  border: 1px solid var(--border-subtle);
  border-radius: var(--radius-sm);
  padding: 0 4px;
  transition: border-color 0.2s, box-shadow 0.2s;
}

.margin-input-wrap:focus-within {
  border-color: var(--border-accent);
  box-shadow: 0 0 0 3px var(--bg-accent-subtle);
}

.margin-icon {
  font-size: 9px;
  font-weight: 700;
  color: var(--text-tertiary);
  flex-shrink: 0;
  font-family: 'SF Mono', 'Cascadia Code', 'Consolas', monospace;
  user-select: none;
}

.margin-input-wrap input {
  width: 100%;
  background: transparent;
  border: none;
  color: var(--text-primary);
  padding: 5px 0;
  border-radius: 0;
  font-size: 11px;
  text-align: center;
  font-family: inherit;
  min-width: 0;
}

.margin-input-wrap input:focus {
  outline: none;
  box-shadow: none;
}

.header-footer-compact {
  display: flex;
  gap: 6px;
  margin-bottom: 10px;
}

.hf-input-wrap {
  flex: 1;
  display: flex;
  align-items: center;
  gap: 4px;
  background: var(--bg-inset);
  border: 1px solid var(--border-subtle);
  border-radius: var(--radius-sm);
  padding: 0 6px;
  transition: border-color 0.2s, box-shadow 0.2s;
}

.hf-input-wrap:focus-within {
  border-color: var(--border-accent);
  box-shadow: 0 0 0 3px var(--bg-accent-subtle);
}

.hf-icon {
  font-size: 12px;
  font-weight: 700;
  color: var(--text-tertiary);
  flex-shrink: 0;
  user-select: none;
}

.hf-input-wrap input {
  flex: 1;
  background: transparent;
  border: none;
  color: var(--text-primary);
  padding: 5px 0;
  border-radius: 0;
  font-size: 11px;
  text-align: center;
  font-family: inherit;
  min-width: 0;
}

.hf-input-wrap input:focus {
  outline: none;
  box-shadow: none;
}

.dimensions-compact {
  flex: 1;
  display: flex;
  gap: 6px;
  min-width: 0;
  margin-bottom: 10px;
}

.dim-input-group {
  flex: 1;
  display: flex;
  align-items: center;
  gap: 4px;
  background: var(--bg-inset);
  border: 1px solid var(--border-subtle);
  border-radius: var(--radius-sm);
  padding: 0 6px;
  transition: border-color 0.2s, box-shadow 0.2s;
}

.dim-input-group:focus-within {
  border-color: var(--border-accent);
  box-shadow: 0 0 0 3px var(--bg-accent-subtle);
}

.dim-input-group svg {
  color: var(--text-tertiary);
  flex-shrink: 0;
}

.dim-input-group input {
  flex: 1;
  background: transparent;
  border: none;
  color: var(--text-primary);
  padding: 5px 0;
  font-size: 12px;
  font-family: inherit;
  min-width: 0;
}

.dim-input-group input:focus {
  outline: none;
  box-shadow: none;
}

.field-group {
  margin-bottom: 8px;
}

.field-group label {
  display: block;
  font-size: 12px;
  color: var(--text-secondary);
  margin-bottom: 4px;
  font-weight: 400;
}

textarea {
  width: 100%;
  background: var(--bg-inset);
  border: 1px solid var(--border-subtle);
  color: var(--text-primary);
  padding: 6px 8px;
  border-radius: var(--radius-sm);
  font-size: 12px;
  resize: vertical;
  font-family: inherit;
  transition: border-color 0.2s, box-shadow 0.2s;
}

textarea:focus {
  outline: none;
  border-color: var(--border-accent);
  box-shadow: 0 0 0 3px var(--bg-accent-subtle);
}

.list-item-edit {
  margin-bottom: 4px;
}

.list-item-edit input {
  width: 100%;
  background: var(--bg-inset);
  border: 1px solid var(--border-subtle);
  color: var(--text-primary);
  padding: 5px 8px;
  border-radius: var(--radius-sm);
  font-size: 12px;
  font-family: inherit;
}

.empty {
  text-align: center;
  padding: 40px 20px;
  color: var(--text-tertiary);
  font-size: 13px;
}

.style-toggles {
  display: flex;
  gap: 3px;
}

.style-toggle {
  width: 32px;
  height: 28px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--bg-button);
  border: 1px solid var(--border-subtle);
  color: var(--text-tertiary);
  border-radius: var(--radius-sm);
  cursor: pointer;
  font-size: 14px;
  font-weight: 400;
  transition: all 0.15s ease;
  font-family: inherit;
}

.style-toggle.italic {
  font-style: italic;
}

.style-toggle.underline {
  text-decoration: underline;
}

.style-toggle:hover {
  background: var(--bg-button-hover);
  color: var(--text-primary);
}

.style-toggle.active {
  background: var(--bg-accent);
  border-color: var(--bg-accent);
  color: var(--text-inverse);
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
  background: var(--bg-button);
  border: 1px solid var(--border-subtle);
  color: var(--text-tertiary);
  border-radius: var(--radius-sm);
  cursor: pointer;
  padding: 4px;
  transition: all 0.15s ease;
}

.align-toggle:hover {
  background: var(--bg-button-hover);
  color: var(--text-primary);
}

.align-toggle.active {
  background: var(--bg-accent);
  border-color: var(--bg-accent);
  color: var(--text-inverse);
}

.upload-btn {
  flex: 1;
  background: var(--bg-button);
  border: 1px solid var(--border-subtle);
  color: var(--text-primary);
  padding: 6px 12px;
  border-radius: var(--radius-sm);
  cursor: pointer;
  font-size: 12px;
  transition: all 0.15s ease;
  font-family: inherit;
  font-weight: 500;
}

.upload-btn:hover {
  background: var(--bg-button-hover);
  border-color: var(--border-strong);
}

.upload-btn.remove {
  background: var(--bg-danger-subtle);
  border-color: var(--bg-danger);
  color: var(--bg-danger);
}

.upload-btn.remove:hover {
  background: var(--bg-danger);
  color: var(--text-inverse);
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
  background: var(--bg-button);
  border: 1px solid var(--border-subtle);
  color: var(--text-tertiary);
  border-radius: var(--radius-sm);
  cursor: pointer;
  font-size: 11px;
  transition: all 0.15s ease;
  font-family: inherit;
  font-weight: 500;
}

.format-toggle:hover {
  background: var(--bg-button-hover);
  color: var(--text-primary);
}

.format-toggle.active {
  background: var(--bg-accent);
  border-color: var(--bg-accent);
  color: var(--text-inverse);
}

.checkbox-row {
  justify-content: flex-start;
}

.checkbox-row input[type="checkbox"] {
  width: 16px;
  height: 16px;
  accent-color: var(--bg-accent);
  cursor: pointer;
}

.small-btn {
  width: 24px;
  height: 24px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--bg-button);
  border: 1px solid var(--border-subtle);
  color: var(--text-secondary);
  border-radius: var(--radius-sm);
  cursor: pointer;
  font-size: 14px;
  padding: 0;
  transition: all 0.15s ease;
}

.small-btn:hover {
  background: var(--bg-button-hover);
  color: var(--text-primary);
}

.small-btn:disabled {
  opacity: 0.35;
  cursor: default;
}

.col-count {
  font-size: 13px;
  color: var(--text-primary);
  min-width: 16px;
  text-align: center;
  font-weight: 500;
}

.group-label {
  display: block;
  font-size: 10px;
  color: var(--text-tertiary);
  text-transform: uppercase;
  letter-spacing: 0.8px;
  margin: 8px 0 4px 0;
  padding-bottom: 3px;
  border-bottom: 1px solid var(--border-subtle);
  font-weight: 600;
}

.header-editor {
  margin-bottom: 4px;
}

.header-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 5px 8px;
  background: var(--bg-inset);
  border-radius: var(--radius-sm);
  cursor: pointer;
  transition: all 0.15s ease;
}

.header-row:hover {
  background: var(--bg-hover);
}

.header-label {
  font-size: 11px;
  color: var(--text-secondary);
  font-weight: 500;
}

.expand-arrow {
  font-size: 10px;
  color: var(--text-tertiary);
}

.header-detail {
  padding: 6px 4px;
  background: var(--bg-app);
  border-radius: 0 0 var(--radius-sm) var(--radius-sm);
  margin-top: 2px;
}

.header-detail .field-row {
  margin-bottom: 4px;
}

input[type="text"] {
  flex: 1;
  background: var(--bg-inset);
  border: 1px solid var(--border-subtle);
  color: var(--text-primary);
  padding: 5px 8px;
  border-radius: var(--radius-sm);
  font-size: 12px;
  min-width: 0;
  font-family: inherit;
  transition: border-color 0.2s, box-shadow 0.2s;
}

input[type="text"]:focus {
  outline: none;
  border-color: var(--border-accent);
  box-shadow: 0 0 0 3px var(--bg-accent-subtle);
}

.style-if-rule {
  background: var(--bg-inset);
  border-radius: var(--radius-sm);
  padding: 6px;
  margin-bottom: 4px;
  position: relative;
}

.remove-rule-btn {
  position: absolute;
  top: 4px;
  right: 4px;
  background: var(--bg-danger);
  border: none;
  color: var(--text-inverse);
  width: 16px;
  height: 16px;
  border-radius: 50%;
  font-size: 10px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  line-height: 1;
  transition: opacity 0.15s;
}

.remove-rule-btn:hover {
  opacity: 0.8;
}

.add-rule-btn {
  background: var(--bg-accent-subtle);
  border: 1px solid var(--border-accent);
  color: var(--text-accent);
  padding: 5px 10px;
  border-radius: var(--radius-sm);
  font-size: 11px;
  cursor: pointer;
  width: 100%;
  font-family: inherit;
  font-weight: 500;
  transition: all 0.15s ease;
}

.add-rule-btn:hover {
  background: var(--bg-accent);
  color: var(--text-inverse);
}
</style>
