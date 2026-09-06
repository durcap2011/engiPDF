<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { useEditorStore } from '../stores/editorStore'

const { t } = useI18n()
const store = useEditorStore()

const pageElements = computed(() => {
  return [...store.document.elements]
    .filter(el => el.pageId === store.selectedPageId)
    .reverse()
})

function getElementLabel(el: any): string {
  switch (el.type) {
    case 'text': return el.text?.substring(0, 30) || t('element.text')
    case 'rectangle': return t('element.rectangle')
    case 'line': return t('element.line')
    case 'list': return `${t('element.list')} (${el.items?.length || 0} ${t('element.items')})`
    case 'image': return t('element.image')
    case 'table': return el.name || t('element.table')
    case 'group': return `${t('element.group')} (${el.children?.length || 0} ${t('element.children')})`
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
        <h3 class="section-title">{{ t('layers.title') }}</h3>
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
          {{ t('layers.noElements') }}
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
  background: var(--bg-surface);
  border-right: 1px solid var(--border-default);
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
  font-size: 10px;
  text-transform: uppercase;
  letter-spacing: 1.2px;
  color: var(--text-tertiary);
  margin-bottom: 10px;
  padding-bottom: 6px;
  border-bottom: 1px solid var(--border-subtle);
  font-weight: 600;
}

.layer-item {
  display: flex;
  align-items: center;
  gap: 4px;
  padding: 5px 6px;
  border-radius: var(--radius-sm);
  cursor: pointer;
  transition: all 0.15s ease;
  margin-bottom: 1px;
}

.layer-item:hover {
  background: var(--bg-hover);
}

.layer-item.selected {
  background: var(--bg-active);
  outline: 1px solid var(--border-accent);
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
  font-size: 11px;
  color: var(--text-tertiary);
  flex-shrink: 0;
}

.layer-label {
  flex: 1;
  font-size: 12px;
  color: var(--text-secondary);
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  font-weight: 400;
}

.layer-item.selected .layer-label {
  color: var(--text-primary);
  font-weight: 500;
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
  color: var(--text-tertiary);
  cursor: pointer;
  border-radius: var(--radius-sm);
  padding: 0;
  transition: all 0.15s ease;
}

.layer-btn:hover {
  background: var(--bg-hover);
  color: var(--text-primary);
}

.layer-btn.active {
  color: var(--text-accent);
}

.empty-layers {
  text-align: center;
  padding: 24px 16px;
  color: var(--text-tertiary);
  font-size: 12px;
}
</style>
