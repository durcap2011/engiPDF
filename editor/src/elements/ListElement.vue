<script setup lang="ts">
import { computed, ref, nextTick } from 'vue'
import type { ListElement, ListItem } from '../types'
import { getBulletById, type BulletType } from '../utils/bulletTypes'

const props = defineProps<{
  element: ListElement
  level?: number
}>()

const emit = defineEmits<{
  update: [items: ListItem[]]
}>()

const isEditing = ref(false)
const editText = ref('')
const textareaRef = ref<HTMLTextAreaElement | null>(null)

const displayStyle = computed(() => {
  const s = props.element.style
  return {
    fontFamily: s.font === 'helvetica' ? 'Helvetica, Arial, sans-serif' :
                s.font === 'times' ? 'Times New Roman, serif' :
                s.font === 'courier' ? 'Courier New, monospace' :
                s.font === 'arial' ? 'Helvetica, Arial, sans-serif' :
                s.font === 'serif' ? 'Times New Roman, serif' :
                s.font === 'sans-serif' ? 'Helvetica, Arial, sans-serif' :
                s.font === 'monospace' ? 'Courier New, monospace' : s.font,
    fontSize: `${s.size * (96 / 72)}px`,
    fontWeight: (s.weight === 'bold' ? '700' : '400'),
    fontStyle: (s.style || 'normal') as 'normal' | 'italic' | 'oblique',
    textDecoration: s.underline ? 'underline' : 'none',
    color: `rgb(${s.color[0] * 255}, ${s.color[1] * 255}, ${s.color[2] * 255})`,
    lineHeight: s.lineHeight || 1.4,
    width: '100%',
  }
})

const currentBulletType = computed<BulletType>(() => {
  const level = props.level || 0
  const bullets = props.element.style.bullets || []
  const bulletId = level === 0
    ? props.element.style.bullet
    : (bullets[level] || bullets[bullets.length - 1] || props.element.style.bullet)
  return getBulletById(bulletId || 'circle')
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
  width: '100%',
  height: '100%',
}))

function itemsToText(items: ListItem[], level: number = 0): string {
  return items.map(item => {
    const indent = '\t'.repeat(level)
    const childText = item.items && item.items.length > 0
      ? '\n' + itemsToText(item.items, level + 1)
      : ''
    return indent + item.text + childText
  }).join('\n')
}

function textToItems(text: string): ListItem[] {
  const lines = text.split('\n')
  const result: ListItem[] = []
  const stack: { items: ListItem[], level: number }[] = [{ items: result, level: -1 }]

  for (const line of lines) {
    if (line.trim() === '') continue

    const match = line.match(/^(\t*)/)
    const level = match ? match[1].length : 0
    const cleanText = line.replace(/^\t+/, '')

    // Risali lo stack fino a trovare un livello inferiore
    while (stack.length > 1 && stack[stack.length - 1].level >= level) {
      stack.pop()
    }

    const newItem: ListItem = { text: cleanText }
    stack[stack.length - 1].items.push(newItem)

    // Crea un container per eventuali figli e pushalo sullo stack
    const children: ListItem[] = []
    newItem.items = children
    stack.push({ items: children, level })
  }

  return result
}

function startEditing() {
  isEditing.value = true
  editText.value = itemsToText(props.element.items)
  nextTick(() => {
    textareaRef.value?.focus()
    textareaRef.value?.select()
  })
}

function stopEditing() {
  if (!isEditing.value) return
  isEditing.value = false
  const newItems = textToItems(editText.value)
  if (newItems.length > 0) {
    // Pulisci gli array items vuoti
    function cleanItems(items: ListItem[]): ListItem[] {
      return items.map(item => {
        const cleaned = { text: item.text }
        if (item.items && item.items.length > 0) {
          return { ...cleaned, items: cleanItems(item.items) }
        }
        return cleaned
      })
    }
    emit('update', cleanItems(newItems))
  }
}

function onKeydown(e: KeyboardEvent) {
  if (e.key === 'Escape') {
    isEditing.value = false
  } else if (e.key === 'Enter' && !e.shiftKey) {
    e.preventDefault()
    stopEditing()
  } else if (e.key === 'Tab') {
    e.preventDefault()
    const textarea = textareaRef.value
    if (!textarea) return

    const start = textarea.selectionStart
    const end = textarea.selectionEnd
    const value = textarea.value

    if (e.shiftKey) {
      // Shift+Tab: de-indent
      const lineStart = value.lastIndexOf('\n', start - 1) + 1
      if (value[lineStart] === '\t') {
        editText.value = value.substring(0, lineStart) + value.substring(lineStart + 1)
        nextTick(() => {
          textarea.selectionStart = Math.max(lineStart, start - 1)
          textarea.selectionEnd = Math.max(lineStart, end - 1)
        })
      }
    } else {
      // Tab: indent
      editText.value = value.substring(0, start) + '\t' + value.substring(end)
      nextTick(() => {
        textarea.selectionStart = start + 1
        textarea.selectionEnd = start + 1
      })
    }
  }
}
</script>

<template>
  <div
    v-if="!isEditing"
    :style="displayStyle"
    @dblclick.stop="startEditing"
  >
    <template v-for="(item, idx) in element.items" :key="idx">
      <div
        class="list-item"
        :style="{ paddingLeft: (element.style.textIndent * 96 / 25.4) + 'px' }"
      >
        <span class="bullet" v-html="currentBulletType.svg"></span>
        <span class="text">{{ item.text }}</span>
      </div>
      <div v-if="item.items && item.items.length > 0" class="nested-list">
        <ListElement
          v-for="(child, cIdx) in item.items"
          :key="cIdx"
          :element="{ ...element, items: [child] }"
          :level="(level || 0) + 1"
          @update="(newItems) => {
            if (item.items) {
              item.items[cIdx] = newItems[0]
            }
          }"
        />
      </div>
    </template>
  </div>
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

<style scoped>
.list-item {
  display: flex;
  gap: 4px;
  margin-bottom: 2px;
}
.bullet {
  flex-shrink: 0;
  width: 1em;
  height: 1em;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}
.bullet :deep(svg) {
  width: 100%;
  height: 100%;
  fill: currentColor;
}
.text {
  flex: 1;
}
.nested-list {
  padding-left: 20px;
}
</style>
