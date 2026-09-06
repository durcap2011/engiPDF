<script setup lang="ts">
import { computed } from 'vue'
import { useEditorStore } from '../stores/editorStore'

const store = useEditorStore()

const pageElements = computed(() => {
  return [...store.document.elements]
    .filter(el => el.pageId === store.selectedPageId)
    .reverse()
})

function getElementLabel(el: any): string {
  switch (el.type) {
    case 'text': return el.text?.substring(0, 30) || 'Testo'
    case 'rectangle': return 'Rettangolo'
    case 'line': return 'Linea'
    case 'list': return `Lista (${el.items?.length || 0} item)`
    case 'image': return 'Immagine'
    case 'table': return el.name || 'Tabella'
    case 'group': return `Gruppo (${el.children?.length || 0})`
    default: return el.type
  }
}

function getElementIcon(el: any): string {
  switch (el.type) {
    case 'text': return 'T'
    case 'rectangle': return '▭'
    case 'line': return '—'
    case 'list': return '☰'
    case 'image': return '🖼'
    case 'table': return '⊞'
    case 'group': return '▤'
    default: return '?'
  }
}
</script>

<template>
  <div class="layers-panel">
    <div class="panel-scroll">
      <div class="panel-section">
        <h3 class="section-title">Livelli</h3>
        <div
          v-for="el in pageElements"
          :key="el.id"
          class="layer-item"
          :class="{
            selected: store.selectedIds.includes(el.id),
            hidden: store.isHidden(el.id),
            locked: store.isLocked(el.id)
          }"
          @click="store.selectElement(el.id)"
        >
          <span class="layer-icon">{{ getElementIcon(el) }}</span>
          <span class="layer-label">{{ getElementLabel(el) }}</span>
          <div class="layer-actions">
            <button
              class="layer-btn"
              :class="{ active: !store.isHidden(el.id) }"
              @click.stop="store.toggleVisibility(el.id)"
              :title="store.isHidden(el.id) ? 'Mostra' : 'Nascondi'"
            >
              <svg v-if="!store.isHidden(el.id)" width="14" height="14" viewBox="0 0 16 16"><path d="M8 3C4 3 .7 6.1.1 8c.6 1.9 3.9 5 7.9 5s7.3-3.1 7.9-5c-.6-1.9-3.9-5-7.9-5zm0 8a3 3 0 110-6 3 3 0 010 6z" fill="currentColor"/></svg>
              <svg v-else width="14" height="14" viewBox="0 0 16 16"><path d="M1.3 1.3l13.4 13.4M6.5 6.5a3 3 0 004 4M1.1 8c.6-1.9 3.9-5 7.9-5 1.6 0 3.1.6 4.3 1.3M14.9 8c-.6 1.9-3.9 5-7.9 5-1.6 0-3.1-.6-4.3-1.3" stroke="currentColor" stroke-width="1.5" fill="none"/></svg>
            </button>
            <button
              class="layer-btn"
              :class="{ active: !store.isLocked(el.id) }"
              @click.stop="store.toggleLock(el.id)"
              :title="store.isLocked(el.id) ? 'Sblocca' : 'Blocca'"
            >
              <svg v-if="store.isLocked(el.id)" width="14" height="14" viewBox="0 0 16 16"><rect x="3" y="7" width="10" height="7" rx="1" stroke="currentColor" stroke-width="1.5" fill="none"/><path d="M5 7V5a3 3 0 016 0v2" stroke="currentColor" stroke-width="1.5" fill="none"/></svg>
              <svg v-else width="14" height="14" viewBox="0 0 16 16"><rect x="3" y="7" width="10" height="7" rx="1" stroke="currentColor" stroke-width="1.5" fill="none"/><path d="M5 7V5a3 3 0 016 0" stroke="currentColor" stroke-width="1.5" fill="none"/></svg>
            </button>
          </div>
        </div>
        <div v-if="pageElements.length === 0" class="empty-layers">
          Nessun elemento
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.layers-panel {
  width: 160px;
  min-width: 160px;
  max-width: 160px;
  background: #16213e;
  border-right: 1px solid #0f3460;
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

.layer-item {
  display: flex;
  align-items: center;
  gap: 4px;
  padding: 5px 6px;
  border-radius: 4px;
  cursor: pointer;
  transition: background 0.15s;
  margin-bottom: 1px;
}

.layer-item:hover {
  background: #1a2a4a;
}

.layer-item.selected {
  background: #0f3460;
  outline: 1px solid #4A90D9;
}

.layer-item.hidden {
  opacity: 0.4;
}

.layer-item.locked {
  opacity: 0.6;
}

.layer-icon {
  width: 18px;
  text-align: center;
  font-size: 12px;
  color: #888;
  flex-shrink: 0;
}

.layer-label {
  flex: 1;
  font-size: 12px;
  color: #ccc;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.layer-actions {
  display: flex;
  gap: 2px;
  opacity: 0;
  transition: opacity 0.15s;
}

.layer-item:hover .layer-actions,
.layer-item.selected .layer-actions {
  opacity: 1;
}

.layer-btn {
  width: 20px;
  height: 20px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: transparent;
  border: none;
  color: #888;
  cursor: pointer;
  border-radius: 3px;
  padding: 0;
}

.layer-btn:hover {
  background: #0f3460;
  color: #eee;
}

.layer-btn.active {
  color: #4A90D9;
}

.empty-layers {
  text-align: center;
  padding: 20px;
  color: #666;
  font-size: 12px;
}
</style>
