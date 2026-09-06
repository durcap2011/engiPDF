<script setup lang="ts">
import { computed, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useEditorStore } from '../../stores/editorStore'
import { useThemeStore } from '../../stores/themeStore'
import { setLocale } from '../../i18n'

const { t, locale } = useI18n()
const store = useEditorStore()
const themeStore = useThemeStore()

const showPasteModal = ref(false)
const pasteJsonText = ref('')
const pasteError = ref('')

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

const languages = [
  { code: 'en', label: 'EN' },
  { code: 'it', label: 'IT' },
  { code: 'es', label: 'ES' },
  { code: 'de', label: 'DE' },
  { code: 'fr', label: 'FR' },
]

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

function openPasteModal() {
  pasteJsonText.value = ''
  pasteError.value = ''
  showPasteModal.value = true
}

function confirmPasteJson() {
  try {
    const trimmed = pasteJsonText.value.trim()
    if (!trimmed) {
      pasteError.value = t('toolbar.pasteEmpty')
      return
    }
    JSON.parse(trimmed)
    store.importJson(trimmed)
    showPasteModal.value = false
  } catch (e: any) {
    pasteError.value = t('toolbar.pasteInvalid', { error: e.message || '' })
  }
}

function switchLang(code: string) {
  setLocale(code)
}
</script>

<template>
  <div class="toolbar">
    <div class="toolbar-left">
      <span class="logo">engiPDF</span>
      <input
        v-model="store.document.name"
        class="doc-name"
        :placeholder="t('app.docNamePlaceholder')"
      />
    </div>

    <div class="toolbar-center">
      <div class="tool-group">
        <button @click="store.undo" :disabled="!store.canUndo" :title="t('toolbar.undo')">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 7v6h6"/><path d="M21 17a9 9 0 0 0-9-9 9 9 0 0 0-6 2.3L3 13"/></svg>
        </button>
        <button @click="store.redo" :disabled="!store.canRedo" :title="t('toolbar.redo')">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 7v6h-6"/><path d="M3 17a9 9 0 0 1 9-9 9 9 0 0 1 6 2.3L21 13"/></svg>
        </button>
      </div>

      <span class="separator"></span>

      <div class="tool-group">
        <button @click="store.moveElementUp(store.selectedId!)" :disabled="!store.selectedId" :title="t('toolbar.moveUp')">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 15l-6-6-6 6"/></svg>
        </button>
        <button @click="store.moveElementDown(store.selectedId!)" :disabled="!store.selectedId" :title="t('toolbar.moveDown')">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9l6 6 6-6"/></svg>
        </button>
        <button @click="store.duplicateElement(store.selectedId!)" :disabled="!store.selectedId" :title="t('toolbar.duplicate')">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
        </button>
        <button class="btn-danger" @click="store.removeElement(store.selectedId!)" :disabled="!store.selectedId" :title="t('toolbar.delete')">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>
        </button>
      </div>

      <template v-if="hasMultiSelection">
        <span class="separator"></span>
        <div class="tool-group">
          <button @click="store.alignElements('left')" :title="t('toolbar.alignLeft')">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M17 10H3"/><path d="M21 6H3"/><path d="M21 14H3"/><path d="M17 18H3"/></svg>
          </button>
          <button @click="store.alignElements('center')" :title="t('toolbar.alignCenter')">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M18 10H6"/><path d="M21 6H3"/><path d="M21 14H3"/><path d="M18 18H6"/></svg>
          </button>
          <button @click="store.alignElements('right')" :title="t('toolbar.alignRight')">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M21 10H7"/><path d="M21 6H3"/><path d="M21 14H3"/><path d="M21 18H7"/></svg>
          </button>
          <button @click="store.alignElements('top')" :title="t('toolbar.alignTop')">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 3v18"/><path d="M6 7V3"/><path d="M18 7V3"/></svg>
          </button>
          <button @click="store.alignElements('middle')" :title="t('toolbar.alignMiddle')">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 3v18"/><path d="M6 9H3"/><path d="M21 9h-3"/><path d="M6 15H3"/><path d="M21 15h-3"/></svg>
          </button>
          <button @click="store.alignElements('bottom')" :title="t('toolbar.alignBottom')">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 21V3"/><path d="M6 21v-4"/><path d="M18 21v-4"/></svg>
          </button>
        </div>
      </template>

      <template v-if="hasEnoughForDistribute">
        <span class="separator"></span>
        <div class="tool-group">
          <button @click="store.distributeElements('horizontal')" :title="t('toolbar.distributeHorizontal')">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M5 3v18"/><path d="M19 3v18"/><path d="M8 7h8v10H8z"/></svg>
          </button>
          <button @click="store.distributeElements('vertical')" :title="t('toolbar.distributeVertical')">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M3 5h18"/><path d="M3 19h18"/><path d="M7 8v8h10V8z"/></svg>
          </button>
        </div>
      </template>

      <template v-if="hasMultiSelection || hasGroupSelected">
        <span class="separator"></span>
        <div class="tool-group">
          <button v-if="hasMultiSelection" @click="store.groupElements(store.selectedIds)" :title="t('toolbar.group')">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="2" width="20" height="20" rx="3" stroke-dasharray="4 3"/></svg>
          </button>
          <button v-if="hasGroupSelected" @click="store.ungroupElement(store.selectedIds[0])" :title="t('toolbar.ungroup')">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
          </button>
        </div>
      </template>

      <span class="separator"></span>

      <div class="tool-group page-controls">
        <button @click="goToPreviousPage" :disabled="currentPageIndex <= 1" :title="t('toolbar.prevPage')">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 18l-6-6 6-6"/></svg>
        </button>
        <span class="page-counter">{{ currentPageIndex }} / {{ totalPages }}</span>
        <button @click="goToNextPage" :disabled="currentPageIndex >= totalPages" :title="t('toolbar.nextPage')">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18l6-6-6-6"/></svg>
        </button>
        <span class="separator-dot"></span>
        <button @click="store.addPage(store.selectedPageId)">{{ t('toolbar.addPage') }}</button>
        <button @click="store.duplicatePage(store.selectedPageId)">{{ t('toolbar.duplicatePage') }}</button>
        <button class="btn-danger" @click="store.removePage(store.selectedPageId)" :disabled="totalPages <= 1">{{ t('toolbar.removePage') }}</button>
      </div>
    </div>

    <div class="toolbar-right">
      <div class="lang-switcher">
        <button
          v-for="lang in languages"
          :key="lang.code"
          class="lang-btn"
          :class="{ active: locale === lang.code }"
          @click="switchLang(lang.code)"
        >{{ lang.label }}</button>
      </div>
      <span class="separator-dot"></span>
      <button class="theme-toggle" @click="themeStore.toggle()" :title="themeStore.theme === 'light' ? t('toolbar.themeLight') : t('toolbar.themeDark')">
        <svg v-if="themeStore.theme === 'light'" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
        <svg v-else width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>
      </button>
      <span class="separator-dot"></span>
      <button @click="importJson">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
        {{ t('toolbar.import') }}
      </button>
      <button @click="openPasteModal">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><rect x="8" y="2" width="8" height="4" rx="1" ry="1"/></svg>
        {{ t('toolbar.pasteJson') }}
      </button>
      <button @click="exportJson">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
        {{ t('toolbar.export') }}
      </button>
    </div>
  </div>

  <Teleport to="body">
    <div v-if="showPasteModal" class="paste-modal-overlay" @click.self="showPasteModal = false">
      <div class="paste-modal">
        <div class="paste-modal-header">
          <span class="paste-modal-title">{{ t('toolbar.pasteJsonTitle') }}</span>
          <button class="paste-modal-close" @click="showPasteModal = false">✕</button>
        </div>
        <textarea
          v-model="pasteJsonText"
          class="paste-textarea"
          :placeholder="t('toolbar.pasteJsonPlaceholder')"
          spellcheck="false"
        ></textarea>
        <div v-if="pasteError" class="paste-error">{{ pasteError }}</div>
        <div class="paste-modal-actions">
          <button class="paste-btn-cancel" @click="showPasteModal = false">{{ t('toolbar.pasteCancel') }}</button>
          <button class="paste-btn-confirm" @click="confirmPasteJson">{{ t('toolbar.pasteConfirm') }}</button>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<style scoped>
.toolbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 6px 12px;
  background: var(--bg-surface);
  border-bottom: 1px solid var(--border-default);
  min-height: 44px;
  gap: 8px;
}

.toolbar-left, .toolbar-center, .toolbar-right {
  display: flex;
  align-items: center;
  gap: 6px;
}

.logo {
  font-weight: 800;
  font-size: 15px;
  color: var(--bg-accent);
  letter-spacing: -0.3px;
  user-select: none;
}

.doc-name {
  background: var(--bg-inset);
  border: 1px solid var(--border-subtle);
  color: var(--text-primary);
  padding: 5px 10px;
  border-radius: var(--radius-md);
  font-size: 12px;
  width: 170px;
  transition: border-color 0.2s, box-shadow 0.2s;
}

.doc-name:focus {
  outline: none;
  border-color: var(--border-accent);
  box-shadow: 0 0 0 3px var(--bg-accent-subtle);
}

button {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  background: var(--bg-button);
  color: var(--text-secondary);
  border: 1px solid var(--border-subtle);
  padding: 5px 8px;
  border-radius: var(--radius-md);
  cursor: pointer;
  font-size: 12px;
  font-weight: 500;
  white-space: nowrap;
  transition: all 0.15s ease;
  font-family: inherit;
}

button:hover:not(:disabled) {
  background: var(--bg-button-hover);
  color: var(--text-primary);
  border-color: var(--border-strong);
}

button:disabled {
  opacity: 0.35;
  cursor: default;
}

button:active:not(:disabled) {
  transform: scale(0.97);
}

button.btn-danger:hover:not(:disabled) {
  background: var(--bg-danger-subtle);
  color: var(--bg-danger);
  border-color: var(--bg-danger);
}

.tool-group {
  display: flex;
  align-items: center;
  gap: 2px;
}

.separator {
  width: 1px;
  height: 20px;
  background: var(--border-default);
  margin: 0 4px;
  flex-shrink: 0;
}

.separator-dot {
  width: 3px;
  height: 3px;
  border-radius: 50%;
  background: var(--border-strong);
  flex-shrink: 0;
}

.page-controls {
  gap: 4px;
}

.page-counter {
  font-size: 11px;
  color: var(--text-tertiary);
  min-width: 36px;
  text-align: center;
  font-family: 'SF Mono', 'Cascadia Code', 'Consolas', monospace;
  font-weight: 500;
  user-select: none;
}

.theme-toggle {
  width: 32px;
  height: 32px;
  padding: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: var(--radius-md);
  border: 1px solid var(--border-subtle);
}

.theme-toggle:hover:not(:disabled) {
  background: var(--bg-accent-subtle);
  color: var(--text-accent);
  border-color: var(--border-accent);
}

.lang-switcher {
  display: flex;
  gap: 2px;
  background: var(--bg-inset);
  border-radius: var(--radius-md);
  padding: 2px;
}

.lang-btn {
  padding: 3px 6px;
  border: none;
  border-radius: var(--radius-sm);
  font-size: 10px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.15s ease;
  color: var(--text-tertiary);
  background: transparent;
}

.lang-btn:hover {
  color: var(--text-primary);
}

.lang-btn.active {
  background: var(--bg-accent);
  color: var(--text-inverse);
}

.paste-modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
}

.paste-modal {
  background: var(--bg-elevated);
  border: 1px solid var(--border-default);
  border-radius: var(--radius-lg);
  padding: 0;
  width: 560px;
  max-height: 80vh;
  display: flex;
  flex-direction: column;
  box-shadow: var(--shadow-lg);
}

.paste-modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 14px 16px;
  border-bottom: 1px solid var(--border-default);
}

.paste-modal-title {
  font-size: 14px;
  font-weight: 600;
  color: var(--text-primary);
}

.paste-modal-close {
  width: 28px;
  height: 28px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: transparent;
  border: none;
  border-radius: var(--radius-sm);
  cursor: pointer;
  color: var(--text-tertiary);
  font-size: 14px;
}

.paste-modal-close:hover {
  background: var(--bg-button-hover);
  color: var(--text-primary);
}

.paste-textarea {
  width: 100%;
  min-height: 320px;
  padding: 12px 16px;
  background: var(--bg-inset);
  border: none;
  color: var(--text-primary);
  font-family: 'SF Mono', 'Cascadia Code', 'Consolas', monospace;
  font-size: 12px;
  line-height: 1.5;
  resize: vertical;
  outline: none;
}

.paste-textarea::placeholder {
  color: var(--text-tertiary);
}

.paste-error {
  padding: 8px 16px;
  background: var(--bg-danger-subtle);
  color: var(--bg-danger);
  font-size: 12px;
  border-top: 1px solid var(--border-default);
}

.paste-modal-actions {
  display: flex;
  justify-content: flex-end;
  gap: 8px;
  padding: 12px 16px;
  border-top: 1px solid var(--border-default);
}

.paste-btn-cancel {
  padding: 6px 14px;
  background: var(--bg-button);
  border: 1px solid var(--border-subtle);
  border-radius: var(--radius-md);
  color: var(--text-secondary);
  font-size: 12px;
  font-weight: 500;
  cursor: pointer;
}

.paste-btn-cancel:hover {
  background: var(--bg-button-hover);
  color: var(--text-primary);
}

.paste-btn-confirm {
  padding: 6px 14px;
  background: var(--bg-accent);
  border: 1px solid var(--bg-accent);
  border-radius: var(--radius-md);
  color: var(--text-inverse);
  font-size: 12px;
  font-weight: 500;
  cursor: pointer;
}

.paste-btn-confirm:hover {
  opacity: 0.9;
}
</style>
