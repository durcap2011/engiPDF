<script setup lang="ts">
import { onMounted, onUnmounted } from 'vue'
import { useEditorStore } from './stores/editorStore'
import ComponentPalette from './components/Sidebar/ComponentPalette.vue'
import EditorCanvas from './components/Canvas/EditorCanvas.vue'
import PropertyPanel from './components/Properties/PropertyPanel.vue'
import EditorToolbar from './components/Toolbar/EditorToolbar.vue'
import SearchReplace from './components/SearchReplace.vue'
import LayersPanel from './components/LayersPanel.vue'

const store = useEditorStore()

function handleKeydown(e: KeyboardEvent) {
  if ((e.ctrlKey || e.metaKey) && e.key === 'z') {
    e.preventDefault()
    if (e.shiftKey) store.redo()
    else store.undo()
  }
  if ((e.ctrlKey || e.metaKey) && e.key === 'y') {
    e.preventDefault()
    store.redo()
  }
  if (e.key === 'Delete' && store.selectedId) {
    store.removeElement(store.selectedId)
  }
  if ((e.ctrlKey || e.metaKey) && e.key === 'd' && store.selectedId) {
    e.preventDefault()
    store.duplicateElement(store.selectedId)
  }
  if ((e.ctrlKey || e.metaKey) && e.key === 'c') {
    e.preventDefault()
    store.copyElements()
  }
  if ((e.ctrlKey || e.metaKey) && e.key === 'v') {
    e.preventDefault()
    store.pasteElements()
  }
  if ((e.ctrlKey || e.metaKey) && e.key === 'g' && store.selectedIds.length >= 2) {
    e.preventDefault()
    if (e.shiftKey) {
      if (store.selectedIds.length === 1) {
        store.ungroupElement(store.selectedIds[0])
      }
    } else {
      store.groupElements(store.selectedIds)
    }
  }
  if ((e.ctrlKey || e.metaKey) && e.shiftKey && e.key === 'G' && store.selectedIds.length === 1) {
    e.preventDefault()
    store.ungroupElement(store.selectedIds[0])
  }
  if ((e.ctrlKey || e.metaKey) && e.key === 'f') {
    e.preventDefault()
    store.toggleSearch()
  }
}

onMounted(() => document.addEventListener('keydown', handleKeydown))
onUnmounted(() => document.removeEventListener('keydown', handleKeydown))
</script>

<template>
  <div class="app-layout">
    <EditorToolbar />
    <div class="app-body">
      <LayersPanel />
      <ComponentPalette />
      <EditorCanvas />
      <PropertyPanel />
    </div>
    <SearchReplace />
  </div>
</template>

<style>
.app-layout {
  display: flex;
  flex-direction: column;
  height: 100vh;
  background: #1a1a2e;
  color: #eee;
}

.app-body {
  display: flex;
  flex: 1;
  overflow: hidden;
}
</style>
