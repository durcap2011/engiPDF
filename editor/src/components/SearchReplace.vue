<script setup lang="ts">
import { computed, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useEditorStore } from '../stores/editorStore'

const { t } = useI18n()
const store = useEditorStore()
const searchInput = ref<HTMLInputElement | null>(null)

const matchCount = computed(() => {
  return store.getSearchMatches().length
})

function handleKeydown(e: KeyboardEvent) {
  if (e.key === 'Enter') {
    e.preventDefault()
    if (e.shiftKey) {
      store.findPrevious()
    } else {
      store.findNext()
    }
  }
  if (e.key === 'Escape') {
    store.toggleSearch()
  }
}
</script>

<template>
  <div v-if="store.searchVisible" class="search-replace-bar">
    <div class="search-row">
      <input
        ref="searchInput"
        v-model="store.searchText"
        type="text"
        :placeholder="t('search.placeholder')"
        class="search-input"
        @keydown="handleKeydown"
      />
      <span class="match-info" v-if="store.searchText">
        {{ matchCount > 0 ? `${store.searchMatchIndex + 1}/${matchCount}` : t('search.noResults') }}
      </span>
      <button class="search-btn" @click="store.findPrevious" :disabled="matchCount === 0" :title="t('search.prev')">◀</button>
      <button class="search-btn" @click="store.findNext" :disabled="matchCount === 0" :title="t('search.next')">▶</button>
      <button class="search-btn close" @click="store.toggleSearch" :title="t('search.close')">✕</button>
    </div>
    <div class="replace-row">
      <input
        v-model="store.replaceText"
        type="text"
        :placeholder="t('search.replacePlaceholder')"
        class="search-input"
        @keydown="handleKeydown"
      />
      <button class="search-btn" @click="store.replaceCurrent" :disabled="matchCount === 0" :title="t('search.replace')">S</button>
      <button class="search-btn" @click="store.replaceAll" :disabled="matchCount === 0" :title="t('search.replaceAll')">SA</button>
    </div>
  </div>
</template>

<style scoped>
.search-replace-bar {
  position: fixed;
  top: 56px;
  right: 272px;
  background: var(--bg-elevated);
  border: 1px solid var(--border-default);
  border-radius: var(--radius-lg);
  padding: 10px;
  z-index: 1000;
  display: flex;
  flex-direction: column;
  gap: 6px;
  min-width: 300px;
  box-shadow: var(--shadow-lg);
}

.search-row, .replace-row {
  display: flex;
  align-items: center;
  gap: 4px;
}

.search-input {
  flex: 1;
  background: var(--bg-inset);
  border: 1px solid var(--border-subtle);
  color: var(--text-primary);
  padding: 5px 10px;
  border-radius: var(--radius-sm);
  font-size: 12px;
  font-family: inherit;
  transition: border-color 0.2s, box-shadow 0.2s;
}

.search-input:focus {
  outline: none;
  border-color: var(--border-accent);
  box-shadow: 0 0 0 3px var(--bg-accent-subtle);
}

.match-info {
  font-size: 11px;
  color: var(--text-tertiary);
  min-width: 50px;
  text-align: center;
  font-family: 'SF Mono', 'Cascadia Code', 'Consolas', monospace;
  font-weight: 500;
}

.search-btn {
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
  font-size: 11px;
  padding: 0;
  transition: all 0.15s ease;
}

.search-btn:hover:not(:disabled) {
  background: var(--bg-accent);
  color: var(--text-inverse);
  border-color: var(--bg-accent);
}

.search-btn:disabled {
  opacity: 0.3;
  cursor: default;
}

.search-btn.close {
  color: var(--text-danger);
}

.search-btn.close:hover {
  background: var(--bg-danger);
  color: var(--text-inverse);
}
</style>
