<script setup lang="ts">
import { computed, ref } from 'vue'
import { useEditorStore } from '../stores/editorStore'

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
        placeholder="Cerca..."
        class="search-input"
        @keydown="handleKeydown"
      />
      <span class="match-info" v-if="store.searchText">
        {{ matchCount > 0 ? `${store.searchMatchIndex + 1}/${matchCount}` : '0 risultati' }}
      </span>
      <button class="search-btn" @click="store.findPrevious" :disabled="matchCount === 0" title="Precedente (Shift+Enter)">◀</button>
      <button class="search-btn" @click="store.findNext" :disabled="matchCount === 0" title="Successiva (Enter)">▶</button>
      <button class="search-btn close" @click="store.toggleSearch" title="Chiudi (Esc)">✕</button>
    </div>
    <div class="replace-row">
      <input
        v-model="store.replaceText"
        type="text"
        placeholder="Sostituisci con..."
        class="search-input"
        @keydown="handleKeydown"
      />
      <button class="search-btn" @click="store.replaceCurrent" :disabled="matchCount === 0" title="Sostituisci">S</button>
      <button class="search-btn" @click="store.replaceAll" :disabled="matchCount === 0" title="Sostituisci tutti">SA</button>
    </div>
  </div>
</template>

<style scoped>
.search-replace-bar {
  position: fixed;
  top: 56px;
  right: 272px;
  background: #16213e;
  border: 1px solid #0f3460;
  border-radius: 6px;
  padding: 8px;
  z-index: 1000;
  display: flex;
  flex-direction: column;
  gap: 6px;
  min-width: 300px;
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.4);
}

.search-row, .replace-row {
  display: flex;
  align-items: center;
  gap: 4px;
}

.search-input {
  flex: 1;
  background: #0f3460;
  border: 1px solid #1a1a4e;
  color: #eee;
  padding: 4px 8px;
  border-radius: 3px;
  font-size: 12px;
}

.search-input:focus {
  outline: none;
  border-color: #4A90D9;
}

.match-info {
  font-size: 11px;
  color: #888;
  min-width: 50px;
  text-align: center;
}

.search-btn {
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
  font-size: 11px;
  padding: 0;
}

.search-btn:hover:not(:disabled) {
  background: #4A90D9;
}

.search-btn:disabled {
  opacity: 0.3;
  cursor: default;
}

.search-btn.close {
  color: #e94560;
}

.search-btn.close:hover {
  background: #e94560;
  color: #fff;
}
</style>
