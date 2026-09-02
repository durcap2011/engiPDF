import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import type { Document, Element, PageSettings } from '../types'
import { DEFAULT_PAGE } from '../types'
import { measureText, measureList } from '../utils/measureContent'

export const useEditorStore = defineStore('editor', () => {
  // --- State ---
  const document = ref<Document>({
    version: 1,
    name: 'Nuovo Template',
    page: { ...DEFAULT_PAGE },
    defaultFont: 'helvetica',
    elements: []
  })

  const selectedId = ref<string | null>(null)
  const gridSize = ref(1)
  const zoom = ref(1)

  // --- History ---
  const history = ref<string[]>([])
  const future = ref<string[]>([])
  const maxHistory = 50

  function saveHistory() {
    history.value.push(JSON.stringify(document.value))
    if (history.value.length > maxHistory) {
      history.value.shift()
    }
    future.value = []
  }

  const canUndo = computed(() => history.value.length > 0)
  const canRedo = computed(() => future.value.length > 0)

  function undo() {
    if (!canUndo.value) return
    future.value.push(JSON.stringify(document.value))
    document.value = JSON.parse(history.value.pop()!)
    selectedId.value = null
  }

  function redo() {
    if (!canRedo.value) return
    history.value.push(JSON.stringify(document.value))
    document.value = JSON.parse(future.value.pop()!)
    selectedId.value = null
  }

  // --- Element CRUD ---
  function addElement(el: Record<string, unknown>) {
    saveHistory()
    const id = crypto.randomUUID()
    document.value.elements.push({ id, ...el } as Element)
    selectedId.value = id
  }

  function updateElement(id: string, patch: Partial<Element>) {
    saveHistory()
    const idx = document.value.elements.findIndex((el: Element) => el.id === id)
    if (idx !== -1) {
      const current = document.value.elements[idx]
      document.value.elements[idx] = { ...current, ...patch } as Element
    }
  }

  function removeElement(id: string) {
    saveHistory()
    document.value.elements = document.value.elements.filter((el: Element) => el.id !== id)
    if (selectedId.value === id) selectedId.value = null
  }

  function duplicateElement(id: string) {
    const el = document.value.elements.find((item: Element) => item.id === id)
    if (!el) return
    saveHistory()
    const newId = crypto.randomUUID()
    const clone = { ...JSON.parse(JSON.stringify(el)), id: newId, x: el.x + 5, y: el.y + 5 }
    document.value.elements.push(clone as Element)
    selectedId.value = newId
  }

  function selectElement(id: string | null) {
    selectedId.value = id
  }

  function moveElementUp(id: string) {
    const idx = document.value.elements.findIndex((el: Element) => el.id === id)
    if (idx < document.value.elements.length - 1) {
      saveHistory()
      const temp = document.value.elements[idx]
      document.value.elements[idx] = document.value.elements[idx + 1]
      document.value.elements[idx + 1] = temp
    }
  }

  function moveElementDown(id: string) {
    const idx = document.value.elements.findIndex((el: Element) => el.id === id)
    if (idx > 0) {
      saveHistory()
      const temp = document.value.elements[idx]
      document.value.elements[idx] = document.value.elements[idx - 1]
      document.value.elements[idx - 1] = temp
    }
  }

  // --- Auto-Fit ---
  function autoFitElement(id: string) {
    const el = document.value.elements.find((e: Element) => e.id === id)
    if (!el) return

    let size: { width: number; height: number } | null = null

    if (el.type === 'text') {
      size = measureText(el)
    } else if (el.type === 'list') {
      size = measureList(el)
    }

    if (size) {
      saveHistory()
      const idx = document.value.elements.findIndex((e: Element) => e.id === id)
      if (idx !== -1) {
        document.value.elements[idx] = {
          ...document.value.elements[idx],
          width: size.width,
          height: size.height,
        } as Element
      }
    }
  }

  // --- Page ---
  function updatePage(settings: Partial<PageSettings>) {
    saveHistory()
    document.value.page = { ...document.value.page, ...settings }
  }

  // --- Import/Export ---
  function exportJson(): string {
    return JSON.stringify(document.value, null, 2)
  }

  function importJson(json: string) {
    saveHistory()
    const data = JSON.parse(json)
    document.value = data
    selectedId.value = null
  }

  function getSelectedElement(): Element | undefined {
    return document.value.elements.find((el: Element) => el.id === selectedId.value)
  }

  return {
    document, selectedId, gridSize, zoom,
    canUndo, canRedo, undo, redo,
    addElement, updateElement, removeElement, duplicateElement,
    selectElement, moveElementUp, moveElementDown,
    autoFitElement,
    updatePage, exportJson, importJson, getSelectedElement
  }
})
