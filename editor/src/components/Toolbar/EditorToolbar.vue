<script setup lang="ts">
import { useEditorStore } from '../../stores/editorStore'

const store = useEditorStore()

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
    </div>

    <div class="toolbar-right">
      <label class="grid-label">
        Grid:
        <input v-model.number="store.gridSize" type="number" min="1" max="20" step="1" class="grid-input" />
        mm
      </label>
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

.grid-label {
  font-size: 12px;
  color: #aaa;
}

.grid-input {
  width: 40px;
  background: transparent;
  border: 1px solid #0f3460;
  color: #eee;
  padding: 2px 4px;
  border-radius: 3px;
  text-align: center;
}
</style>
