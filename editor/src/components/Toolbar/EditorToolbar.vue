<script setup lang="ts">
import { computed } from 'vue'
import { useEditorStore } from '../../stores/editorStore'

const store = useEditorStore()

const currentPageIndex = computed(() => {
  return store.document.pages.findIndex(p => p.id === store.selectedPageId) + 1
})

const totalPages = computed(() => store.document.pages.length)
const hasMultiSelection = computed(() => store.selectedIds.length >= 2)
const hasEnoughForDistribute = computed(() => store.selectedIds.length >= 3)
const hasGroupSelected = computed(() => {
  if (store.selectedIds.length !== 1) return false
  const el = store.document.elements.find(e => e.id === store.selectedIds[0])
  return el?.type === 'group'
})

function goToPreviousPage() {
  const idx = store.document.pages.findIndex(p => p.id === store.selectedPageId)
  if (idx > 0) {
    store.selectPage(store.document.pages[idx - 1].id)
  }
}

function goToNextPage() {
  const idx = store.document.pages.findIndex(p => p.id === store.selectedPageId)
  if (idx < store.document.pages.length - 1) {
    store.selectPage(store.document.pages[idx + 1].id)
  }
}

function exportJson() {
  const json = store.exportJson()
  const blob = new Blob([json], { type: 'application/json' })
  const url = URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = `${store.document.name || 'template'}.json`
  a.click()
  URL.revokeObjectURL(url)
}

function importJson() {
  const input = document.createElement('input')
  input.type = 'file'
  input.accept = '.json'
  input.onchange = async (e: Event) => {
    const file = (e.target as HTMLInputElement).files?.[0]
    if (!file) return
    const text = await file.text()
    store.importJson(text)
  }
  input.click()
}
</script>

<template>
  <div class="toolbar">
    <div class="toolbar-left">
      <span class="logo">engiPDF</span>
      <input
        v-model="store.document.name"
        class="doc-name"
        placeholder="Nome documento..."
      />
    </div>

    <div class="toolbar-center">
      <button @click="store.undo" :disabled="!store.canUndo" title="Undo (Ctrl+Z)">↶</button>
      <button @click="store.redo" :disabled="!store.canRedo" title="Redo (Ctrl+Y)">↷</button>
      <span class="separator">|</span>
      <button @click="store.moveElementUp(store.selectedId!)" :disabled="!store.selectedId" title="Sopra">▲</button>
      <button @click="store.moveElementDown(store.selectedId!)" :disabled="!store.selectedId" title="Sotto">▼</button>
      <button @click="store.duplicateElement(store.selectedId!)" :disabled="!store.selectedId" title="Duplica (Ctrl+D)">⧉</button>
      <button @click="store.removeElement(store.selectedId!)" :disabled="!store.selectedId" title="Elimina (Del)">🗑</button>
      <span class="separator" v-if="hasMultiSelection">|</span>
      <template v-if="hasMultiSelection">
        <button @click="store.alignElements('left')" title="Allinea a sinistra">
          <svg width="16" height="16" viewBox="0 0 16 16"><path d="M1 2h2v12H1zm3 2h10v2H4zm2 4h8v2H6zm-1 4h9v2H5z" fill="currentColor"/></svg>
        </button>
        <button @click="store.alignElements('center')" title="Allinea al centro">
          <svg width="16" height="16" viewBox="0 0 16 16"><path d="M1 2h14v2H1zm2 4h10v2H3zm1 4h8v2H4zm1 4h6v2H5z" fill="currentColor"/></svg>
        </button>
        <button @click="store.alignElements('right')" title="Allinea a destra">
          <svg width="16" height="16" viewBox="0 0 16 16"><path d="M13 2h2v12h-2zm-3 2h10v2H10zm-2 4h8v2H8zm1 4h9v2H9z" fill="currentColor"/></svg>
        </button>
        <button @click="store.alignElements('top')" title="Allinea in alto">
          <svg width="16" height="16" viewBox="0 0 16 16"><path d="M2 1v2h12V1zm2 3v10h2V4zm4 2v8h2V6zm3 3v5h2V9z" fill="currentColor"/></svg>
        </button>
        <button @click="store.alignElements('middle')" title="Allinea al centro verticale">
          <svg width="16" height="16" viewBox="0 0 16 16"><path d="M2 1v2h12V1zm1 3h10v2H3zm0 4h10v2H3zm1 4h8v2H4z" fill="currentColor"/></svg>
        </button>
        <button @click="store.alignElements('bottom')" title="Allinea in basso">
          <svg width="16" height="16" viewBox="0 0 16 16"><path d="M2 13v2h12v-2zm2-3v10h2v-10zm4-2v8h2V8zm3-3v5h2V5z" fill="currentColor"/></svg>
        </button>
      </template>
      <template v-if="hasEnoughForDistribute">
        <button @click="store.distributeElements('horizontal')" title="Distribuisci orizzontalmente">
          <svg width="16" height="16" viewBox="0 0 16 16"><path d="M1 2h2v12H1zm12 0h2v12h-2zM5 6h6v4H5z" fill="currentColor"/></svg>
        </button>
        <button @click="store.distributeElements('vertical')" title="Distribuisci verticalmente">
          <svg width="16" height="16" viewBox="0 0 16 16"><path d="M2 1v2h12V1zm0 12v2h12v-2zM6 5h4v6H6z" fill="currentColor"/></svg>
        </button>
      </template>
      <span class="separator" v-if="hasMultiSelection || hasGroupSelected">|</span>
      <button v-if="hasMultiSelection" @click="store.groupElements(store.selectedIds)" title="Raggruppa (Ctrl+G)">
        <svg width="16" height="16" viewBox="0 0 16 16"><rect x="1" y="1" width="14" height="14" rx="2" stroke="currentColor" stroke-width="1.5" fill="none" stroke-dasharray="3 2"/></svg>
      </button>
      <button v-if="hasGroupSelected" @click="store.ungroupElement(store.selectedIds[0])" title="Separa (Ctrl+Shift+G)">
        <svg width="16" height="16" viewBox="0 0 16 16"><rect x="2" y="2" width="5" height="5" stroke="currentColor" stroke-width="1.5" fill="none"/><rect x="9" y="9" width="5" height="5" stroke="currentColor" stroke-width="1.5" fill="none"/></svg>
      </button>
      <span class="separator">|</span>
      <div class="page-controls">
        <button @click="goToPreviousPage" :disabled="currentPageIndex <= 1" title="Pagina precedente">◀</button>
        <span class="page-counter">{{ currentPageIndex }} / {{ totalPages }}</span>
        <button @click="goToNextPage" :disabled="currentPageIndex >= totalPages" title="Pagina successiva">▶</button>
        <span class="separator">|</span>
        <button @click="store.addPage(store.selectedPageId)" title="Aggiungi pagina">+ Pagina</button>
        <button @click="store.duplicatePage(store.selectedPageId)" title="Duplica pagina">⧉ Pagina</button>
        <button @click="store.removePage(store.selectedPageId)" :disabled="totalPages <= 1" title="Elimina pagina">🗑 Pagina</button>
      </div>
    </div>

    <div class="toolbar-right">
      <button @click="importJson">📂 Importa</button>
      <button @click="exportJson">💾 Salva JSON</button>
    </div>
  </div>
</template>

<style scoped>
.toolbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 8px 16px;
  background: #16213e;
  border-bottom: 1px solid #0f3460;
  min-height: 48px;
}

.toolbar-left, .toolbar-center, .toolbar-right {
  display: flex;
  align-items: center;
  gap: 8px;
}

.logo {
  font-weight: 700;
  font-size: 16px;
  color: #e94560;
}

.doc-name {
  background: transparent;
  border: 1px solid #0f3460;
  color: #eee;
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 13px;
  width: 180px;
}

button {
  background: #0f3460;
  color: #eee;
  border: none;
  padding: 6px 10px;
  border-radius: 4px;
  cursor: pointer;
  font-size: 13px;
  white-space: nowrap;
}

button:hover:not(:disabled) {
  background: #e94560;
}

button:disabled {
  opacity: 0.3;
  cursor: default;
}

.separator {
  color: #0f3460;
}

.page-controls {
  display: flex;
  align-items: center;
  gap: 6px;
}

.page-counter {
  font-size: 12px;
  color: #aaa;
  min-width: 40px;
  text-align: center;
  font-family: monospace;
}


</style>
