<script setup lang="ts">
import { onMounted, onUnmounted } from 'vue'
import { useEditorStore } from './stores/editorStore'
import ComponentPalette from './components/Sidebar/ComponentPalette.vue'
import EditorCanvas from './components/Canvas/EditorCanvas.vue'
import PropertyPanel from './components/Properties/PropertyPanel.vue'
import EditorToolbar from './components/Toolbar/EditorToolbar.vue'

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
}

onMounted(() => document.addEventListener('keydown', handleKeydown))
onUnmounted(() => document.removeEventListener('keydown', handleKeydown))
</script>

<template>
  <div class="app-layout">
    <EditorToolbar />
    <div class="app-body">
      <ComponentPalette />
      <EditorCanvas />
      <PropertyPanel />
    </div>
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
