<script setup lang="ts">
import { computed, ref, nextTick } from 'vue'
import type { TextElement } from '../types'

const props = defineProps<{
  element: TextElement
}>()

const emit = defineEmits<{
  update: [text: string]
}>()

const isEditing = ref(false)
const editText = ref('')
const textareaRef = ref<HTMLTextAreaElement | null>(null)

const displayStyle = computed(() => {
  const s = props.element.style
  const weightMap: Record<string, string> = { bold: '700', normal: '400' }
  const alignMap: Record<string, string> = { left: 'left', center: 'center', right: 'right', justify: 'justify' }

  return {
    fontFamily: s.font === 'helvetica' ? 'Helvetica, Arial, sans-serif' :
                s.font === 'times' ? 'Times New Roman, serif' :
                s.font === 'courier' ? 'Courier New, monospace' :
                s.font === 'arial' ? 'Helvetica, Arial, sans-serif' :
                s.font === 'serif' ? 'Times New Roman, serif' :
                s.font === 'sans-serif' ? 'Helvetica, Arial, sans-serif' :
                s.font === 'monospace' ? 'Courier New, monospace' : s.font,
    fontSize: `${s.size * (96 / 72)}px`,
    fontWeight: weightMap[s.weight] || '400',
    fontStyle: (s.style || 'normal') as 'normal' | 'italic' | 'oblique',
    textDecoration: s.underline ? 'underline' : 'none',
    color: `rgb(${s.color[0] * 255}, ${s.color[1] * 255}, ${s.color[2] * 255})`,
    textAlign: (alignMap[s.align] || 'left') as 'left' | 'center' | 'right' | 'justify',
    lineHeight: s.lineHeight || 1.4,
    width: '100%',
    height: '100%',
    overflow: 'hidden',
    whiteSpace: 'pre-wrap' as const,
    wordBreak: 'break-word' as const,
  }
})

const textareaStyle = computed(() => ({
  ...displayStyle.value,
  border: 'none',
  outline: 'none',
  background: 'transparent',
  resize: 'none' as const,
  padding: '0',
  margin: '0',
  cursor: 'text',
}))

function startEditing() {
  isEditing.value = true
  editText.value = props.element.text
  nextTick(() => {
    textareaRef.value?.focus()
    textareaRef.value?.select()
  })
}

function stopEditing() {
  if (!isEditing.value) return
  isEditing.value = false
  if (editText.value !== props.element.text) {
    emit('update', editText.value)
  }
}

function onKeydown(e: KeyboardEvent) {
  if (e.key === 'Escape') {
    editText.value = props.element.text
    stopEditing()
  } else if (e.key === 'Enter' && !e.shiftKey) {
    e.preventDefault()
    stopEditing()
  }
}
</script>

<template>
  <div
    v-if="!isEditing"
    :style="displayStyle"
    @dblclick.stop="startEditing"
  >{{ element.text }}</div>
  <textarea
    v-else
    ref="textareaRef"
    :style="textareaStyle"
    v-model="editText"
    @blur="stopEditing"
    @keydown="onKeydown"
    @click.stop
    @pointerdown.stop
  />
</template>
