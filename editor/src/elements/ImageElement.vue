<script setup lang="ts">
import { computed, ref } from 'vue'
import { useEditorStore } from '../stores/editorStore'
import type { ImageElement } from '../types'

const props = defineProps<{
  element: ImageElement
}>()

const store = useEditorStore()
const fileInputRef = ref<HTMLInputElement | null>(null)

const style = computed(() => ({
  width: '100%',
  height: '100%',
  display: 'flex',
  alignItems: 'center',
  justifyContent: 'center',
  backgroundColor: props.element.src ? 'transparent' : '#f0f0f0',
  border: props.element.src ? 'none' : '1px dashed #ccc',
  overflow: 'hidden' as const,
}))

const objectFit = computed(() => {
  switch (props.element.fit) {
    case 'cover': return 'cover'
    case 'stretch': return 'fill'
    default: return 'contain'
  }
})

function triggerFilePicker() {
  fileInputRef.value?.click()
}

function onFileChange(e: Event) {
  const input = e.target as HTMLInputElement
  const file = input.files?.[0]
  if (!file) return

  const reader = new FileReader()
  reader.onload = () => {
    store.updateElement(props.element.id, { src: reader.result as string })
  }
  reader.readAsDataURL(file)

  input.value = ''
}
</script>

<template>
  <div :style="style" @dblclick.stop="triggerFilePicker">
    <img
      v-if="element.src"
      :src="element.src"
      :style="{ width: '100%', height: '100%', objectFit }"
    />
    <span v-else style="color: #999; font-size: 12px">Doppio click per caricare</span>
    <input
      ref="fileInputRef"
      type="file"
      accept="image/*"
      style="display: none"
      @change="onFileChange"
    />
  </div>
</template>
