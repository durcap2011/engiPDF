<script setup lang="ts">
const components = [
  { type: 'text' as const, label: 'Testo', icon: 'T', desc: 'Blocco di testo' },
  { type: 'rectangle' as const, label: 'Rettangolo', icon: '▭', desc: 'Forma rettangolare' },
  { type: 'line' as const, label: 'Linea', icon: '—', desc: 'Linea retta' },
  { type: 'list' as const, label: 'Lista', icon: '☰', desc: 'Lista puntata/numerata' },
  { type: 'table' as const, label: 'Tabella', icon: '⊞', desc: 'Tabella con intestazioni' },
  { type: 'image' as const, label: 'Immagine', icon: '🖼', desc: 'Immagine JPEG' },
]

function onDragStart(e: DragEvent, type: string) {
  e.dataTransfer?.setData('component-type', type)
  e.dataTransfer!.effectAllowed = 'copy'
}
</script>

<template>
  <div class="palette">
    <h3 class="palette-title">Componenti</h3>
    <div
      v-for="comp in components"
      :key="comp.type"
      class="palette-item"
      draggable="true"
      @dragstart="onDragStart($event, comp.type)"
    >
      <span class="item-icon">{{ comp.icon }}</span>
      <div class="item-info">
        <span class="item-label">{{ comp.label }}</span>
        <span class="item-desc">{{ comp.desc }}</span>
      </div>
    </div>
  </div>
</template>

<style scoped>
.palette {
  width: 200px;
  background: #16213e;
  border-right: 1px solid #0f3460;
  padding: 12px;
  overflow-y: auto;
}

.palette-title {
  font-size: 12px;
  text-transform: uppercase;
  letter-spacing: 1px;
  color: #888;
  margin-bottom: 12px;
}

.palette-item {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 10px;
  border-radius: 6px;
  cursor: grab;
  transition: background 0.15s;
  margin-bottom: 4px;
}

.palette-item:hover {
  background: #0f3460;
}

.palette-item:active {
  cursor: grabbing;
}

.item-icon {
  font-size: 20px;
  width: 32px;
  text-align: center;
  flex-shrink: 0;
}

.item-info {
  display: flex;
  flex-direction: column;
}

.item-label {
  font-size: 13px;
  font-weight: 500;
}

.item-desc {
  font-size: 11px;
  color: #888;
}
</style>
