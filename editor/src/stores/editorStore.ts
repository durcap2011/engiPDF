import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import type { Document, Element, PageSettings, Page, GroupElement } from '../types'
import { DEFAULT_PAGE_SETTINGS, createDefaultPage } from '../types'
import { measureText, measureList } from '../utils/measureContent'

export const useEditorStore = defineStore('editor', () => {
  const document = ref<Document>({
    version: 2,
    name: 'Nuovo Template',
    pages: [createDefaultPage()],
    defaultFont: 'helvetica',
    elements: [],
    sampleData: {
      cliente: { nome: 'Mario Rossi', email: 'mario@example.com' },
      ordine: { numero: 'ORD-2024-001', data: '2024-01-15', stato: 'pagato' },
      importi: [100, 250, 75],
      articoli: [
        { nome: 'Laptop', quantita: 1, prezzo: 899 },
        { nome: 'Mouse', quantita: 2, prezzo: 29.90 },
      ]
    }
  })

  const selectedId = ref<string | null>(null)
  const selectedIds = ref<string[]>([])
  const selectedPageId = ref<string>(document.value.pages[0].id)
  const gridSize = ref(1)
  const zoom = ref(1)

  const history = ref<string[]>([])
  const future = ref<string[]>([])
  const maxHistory = 50
  const clipboard = ref<Element[]>([])
  const searchText = ref('')
  const replaceText = ref('')
  const searchVisible = ref(false)
  const searchMatchIndex = ref(-1)
  const hiddenIds = ref<Set<string>>(new Set())
  const lockedIds = ref<Set<string>>(new Set())

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
    if (!document.value.pages.find(p => p.id === selectedPageId.value)) {
      selectedPageId.value = document.value.pages[0]?.id || ''
    }
  }

  function redo() {
    if (!canRedo.value) return
    history.value.push(JSON.stringify(document.value))
    document.value = JSON.parse(future.value.pop()!)
    selectedId.value = null
    if (!document.value.pages.find(p => p.id === selectedPageId.value)) {
      selectedPageId.value = document.value.pages[0]?.id || ''
    }
  }

  const activePage = computed(() => {
    return document.value.pages.find(p => p.id === selectedPageId.value) || document.value.pages[0]
  })

  const currentPageElements = computed(() => {
    return document.value.elements.filter(el => el.pageId === selectedPageId.value)
  })

  function selectPage(pageId: string) {
    if (document.value.pages.find(p => p.id === pageId)) {
      selectedPageId.value = pageId
      selectedId.value = null
    }
  }

  function addPage(afterPageId?: string) {
    saveHistory()
    const newPage = createDefaultPage()
    if (afterPageId) {
      const idx = document.value.pages.findIndex(p => p.id === afterPageId)
      if (idx !== -1) {
        document.value.pages.splice(idx + 1, 0, newPage)
      } else {
        document.value.pages.push(newPage)
      }
    } else {
      document.value.pages.push(newPage)
    }
    selectedPageId.value = newPage.id
    return newPage.id
  }

  function removePage(pageId: string) {
    if (document.value.pages.length <= 1) return
    saveHistory()
    document.value.elements = document.value.elements.filter(el => el.pageId !== pageId)
    document.value.pages = document.value.pages.filter(p => p.id !== pageId)
    if (selectedPageId.value === pageId) {
      selectedPageId.value = document.value.pages[0].id
    }
  }

  function duplicatePage(pageId: string) {
    const sourcePage = document.value.pages.find(p => p.id === pageId)
    if (!sourcePage) return
    saveHistory()
    const newPageId = crypto.randomUUID()
    const newPage: Page = {
      id: newPageId,
      name: sourcePage.name ? `${sourcePage.name} (copia)` : undefined,
      settings: { ...JSON.parse(JSON.stringify(sourcePage.settings)) }
    }
    const idx = document.value.pages.findIndex(p => p.id === pageId)
    document.value.pages.splice(idx + 1, 0, newPage)
    const sourceElements = document.value.elements.filter(el => el.pageId === pageId)
    for (const el of sourceElements) {
      const clone = { ...JSON.parse(JSON.stringify(el)), id: crypto.randomUUID(), pageId: newPageId }
      document.value.elements.push(clone as Element)
    }
    selectedPageId.value = newPageId
  }

  function updatePageSettings(settings: Partial<PageSettings>) {
    saveHistory()
    const page = document.value.pages.find(p => p.id === selectedPageId.value)
    if (page) {
      page.settings = { ...page.settings, ...settings }
    }
  }

  function copyHeaderFooterFromPage(sourcePageId: string, targetPageId: string, zone: 'header' | 'footer') {
    const sourcePage = document.value.pages.find(p => p.id === sourcePageId)
    const targetPage = document.value.pages.find(p => p.id === targetPageId)
    if (!sourcePage || !targetPage) return

    const sourceHeight = sourcePage.settings.height
    const sourceHeaderH = sourcePage.settings.headerHeight
    const sourceFooterH = sourcePage.settings.footerHeight
    const targetHeight = targetPage.settings.height
    const targetFooterH = targetPage.settings.footerHeight

    const sourceElements = document.value.elements.filter(el => el.pageId === sourcePageId)
    let zoneElements: Element[]

    if (zone === 'header') {
      zoneElements = sourceElements.filter(el => {
        const elTop = el.y + el.height
        return elTop <= sourceHeaderH && sourceHeaderH > 0
      })
      saveHistory()
      const clonedIds: string[] = []
      for (const el of zoneElements) {
        const newId = crypto.randomUUID()
        clonedIds.push(newId)
        const clone = {
          ...JSON.parse(JSON.stringify(el)),
          id: newId,
          pageId: targetPageId,
          y: el.y
        }
        document.value.elements.push(clone as Element)
      }
      targetPage.settings = {
        ...targetPage.settings,
        headerSourcePageId: sourcePageId,
        headerHeight: sourceHeaderH
      }
    } else {
      zoneElements = sourceElements.filter(el => {
        return el.y >= sourceHeight - sourceFooterH && sourceFooterH > 0
      })
      saveHistory()
      for (const el of zoneElements) {
        const newId = crypto.randomUUID()
        const relativeY = el.y - (sourceHeight - sourceFooterH)
        const newY = targetHeight - targetFooterH + relativeY
        const clone = {
          ...JSON.parse(JSON.stringify(el)),
          id: newId,
          pageId: targetPageId,
          y: newY
        }
        document.value.elements.push(clone as Element)
      }
      targetPage.settings = {
        ...targetPage.settings,
        footerSourcePageId: sourcePageId,
        footerHeight: sourceFooterH
      }
    }
  }

  function renamePage(pageId: string, name: string) {
    saveHistory()
    const page = document.value.pages.find(p => p.id === pageId)
    if (page) {
      page.name = name || undefined
    }
  }

  function addElement(el: Record<string, unknown>) {
    saveHistory()
    const id = crypto.randomUUID()
    const pageId = (el.pageId as string) || selectedPageId.value
    document.value.elements.push({ id, pageId, ...el } as Element)
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
    const idsToRemove = selectedIds.value.length > 0 ? [...selectedIds.value] : [id]
    document.value.elements = document.value.elements.filter((el: Element) => !idsToRemove.includes(el.id))
    selectedIds.value = []
    selectedId.value = null
  }

  function duplicateElement(id: string) {
    const idsToClone = selectedIds.value.length > 0 ? [...selectedIds.value] : [id]
    const elementsToClone = document.value.elements.filter((el: Element) => idsToClone.includes(el.id))
    if (elementsToClone.length === 0) return
    saveHistory()
    const newIds: string[] = []
    for (const el of elementsToClone) {
      const newId = crypto.randomUUID()
      newIds.push(newId)
      const clone = { ...JSON.parse(JSON.stringify(el)), id: newId, x: el.x + 5, y: el.y + 5 }
      document.value.elements.push(clone as Element)
    }
    selectedIds.value = newIds
    selectedId.value = newIds[0]
  }

  function selectElement(id: string | null) {
    selectedId.value = id
    if (id) {
      selectedIds.value = [id]
    } else {
      selectedIds.value = []
    }
  }

  function toggleSelection(id: string) {
    const idx = selectedIds.value.indexOf(id)
    if (idx === -1) {
      selectedIds.value.push(id)
      selectedId.value = id
    } else {
      selectedIds.value.splice(idx, 1)
      selectedId.value = selectedIds.value[0] || null
    }
  }

  function selectAll() {
    selectedIds.value = currentPageElements.value.map(el => el.id)
    selectedId.value = selectedIds.value[0] || null
  }

  function clearSelection() {
    selectedIds.value = []
    selectedId.value = null
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

  function moveSelectedElements(dx: number, dy: number) {
    if (selectedIds.value.length === 0) return
    saveHistory()
    for (const id of selectedIds.value) {
      const idx = document.value.elements.findIndex((el: Element) => el.id === id)
      if (idx !== -1) {
        const el = document.value.elements[idx]
        document.value.elements[idx] = { ...el, x: el.x + dx, y: el.y + dy } as Element
      }
    }
  }

  function resizeSelectedElements(dw: number, dh: number) {
    if (selectedIds.value.length <= 1) return
    saveHistory()
    for (const id of selectedIds.value) {
      const idx = document.value.elements.findIndex((el: Element) => el.id === id)
      if (idx !== -1) {
        const el = document.value.elements[idx]
        document.value.elements[idx] = {
          ...el,
          width: Math.max(5, el.width + dw),
          height: Math.max(5, el.height + dh)
        } as Element
      }
    }
  }

  function alignElements(direction: 'left' | 'center' | 'right' | 'top' | 'middle' | 'bottom') {
    if (selectedIds.value.length < 2) return
    saveHistory()
    const selectedElements = document.value.elements.filter(
      (el: Element) => selectedIds.value.includes(el.id)
    )

    let refValue: number
    switch (direction) {
      case 'left':
        refValue = Math.min(...selectedElements.map(e => e.x))
        for (const el of selectedElements) {
          const idx = document.value.elements.findIndex((e: Element) => e.id === el.id)
          document.value.elements[idx] = { ...el, x: refValue } as Element
        }
        break
      case 'center': {
        const minX = Math.min(...selectedElements.map(e => e.x))
        const maxX = Math.max(...selectedElements.map(e => e.x + e.width))
        const centerX = (minX + maxX) / 2
        for (const el of selectedElements) {
          const idx = document.value.elements.findIndex((e: Element) => e.id === el.id)
          document.value.elements[idx] = { ...el, x: centerX - el.width / 2 } as Element
        }
        break
      }
      case 'right':
        refValue = Math.max(...selectedElements.map(e => e.x + e.width))
        for (const el of selectedElements) {
          const idx = document.value.elements.findIndex((e: Element) => e.id === el.id)
          document.value.elements[idx] = { ...el, x: refValue - el.width } as Element
        }
        break
      case 'top':
        refValue = Math.min(...selectedElements.map(e => e.y))
        for (const el of selectedElements) {
          const idx = document.value.elements.findIndex((e: Element) => e.id === el.id)
          document.value.elements[idx] = { ...el, y: refValue } as Element
        }
        break
      case 'middle': {
        const minY = Math.min(...selectedElements.map(e => e.y))
        const maxY = Math.max(...selectedElements.map(e => e.y + e.height))
        const centerY = (minY + maxY) / 2
        for (const el of selectedElements) {
          const idx = document.value.elements.findIndex((e: Element) => e.id === el.id)
          document.value.elements[idx] = { ...el, y: centerY - el.height / 2 } as Element
        }
        break
      }
      case 'bottom':
        refValue = Math.max(...selectedElements.map(e => e.y + e.height))
        for (const el of selectedElements) {
          const idx = document.value.elements.findIndex((e: Element) => e.id === el.id)
          document.value.elements[idx] = { ...el, y: refValue - el.height } as Element
        }
        break
    }
  }

  function distributeElements(axis: 'horizontal' | 'vertical') {
    if (selectedIds.value.length < 3) return
    saveHistory()
    const selectedElements = document.value.elements.filter(
      (el: Element) => selectedIds.value.includes(el.id)
    )

    if (axis === 'horizontal') {
      const sorted = [...selectedElements].sort((a, b) => a.x - b.x)
      const minX = sorted[0].x
      const maxX = sorted[sorted.length - 1].x + sorted[sorted.length - 1].width
      const totalWidth = sorted.reduce((sum, e) => sum + e.width, 0)
      const gap = (maxX - minX - totalWidth) / (sorted.length - 1)
      let currentX = minX
      for (const el of sorted) {
        const idx = document.value.elements.findIndex((e: Element) => e.id === el.id)
        document.value.elements[idx] = { ...el, x: currentX } as Element
        currentX += el.width + gap
      }
    } else {
      const sorted = [...selectedElements].sort((a, b) => a.y - b.y)
      const minY = sorted[0].y
      const maxY = sorted[sorted.length - 1].y + sorted[sorted.length - 1].height
      const totalHeight = sorted.reduce((sum, e) => sum + e.height, 0)
      const gap = (maxY - minY - totalHeight) / (sorted.length - 1)
      let currentY = minY
      for (const el of sorted) {
        const idx = document.value.elements.findIndex((e: Element) => e.id === el.id)
        document.value.elements[idx] = { ...el, y: currentY } as Element
        currentY += el.height + gap
      }
    }
  }

  function groupElements(ids: string[]) {
    if (ids.length < 2) return
    saveHistory()
    const elementsToGroup = document.value.elements.filter(
      (el: Element) => ids.includes(el.id)
    )
    if (elementsToGroup.length < 2) return

    const minX = Math.min(...elementsToGroup.map(e => e.x))
    const minY = Math.min(...elementsToGroup.map(e => e.y))
    const maxX = Math.max(...elementsToGroup.map(e => e.x + e.width))
    const maxY = Math.max(...elementsToGroup.map(e => e.y + e.height))

    const groupElement: GroupElement = {
      id: crypto.randomUUID(),
      pageId: elementsToGroup[0].pageId,
      type: 'group',
      x: minX,
      y: minY,
      width: maxX - minX,
      height: maxY - minY,
      children: elementsToGroup.map(el => ({
        ...JSON.parse(JSON.stringify(el)),
        x: el.x - minX,
        y: el.y - minY
      }))
    }

    document.value.elements = document.value.elements.filter(
      (el: Element) => !ids.includes(el.id)
    )
    document.value.elements.push(groupElement as Element)
    selectedIds.value = [groupElement.id]
    selectedId.value = groupElement.id
  }

  function ungroupElement(groupId: string) {
    const group = document.value.elements.find(
      (el: Element) => el.id === groupId && el.type === 'group'
    )
    if (!group || group.type !== 'group') return
    saveHistory()

    const children = (group as GroupElement).children.map(child => ({
      ...JSON.parse(JSON.stringify(child)),
      id: crypto.randomUUID(),
      pageId: group.pageId,
      x: child.x + group.x,
      y: child.y + group.y
    }))

    document.value.elements = document.value.elements.filter(
      (el: Element) => el.id !== groupId
    )
    document.value.elements.push(...children as Element[])
    selectedIds.value = children.map(c => c.id)
    selectedId.value = children[0]?.id || null
  }

  function copyElements() {
    const elementsToCopy = document.value.elements.filter(
      (el: Element) => selectedIds.value.includes(el.id)
    )
    clipboard.value = JSON.parse(JSON.stringify(elementsToCopy))
  }

  function pasteElements(targetPageId?: string) {
    if (clipboard.value.length === 0) return
    saveHistory()
    const pageId = targetPageId || selectedPageId.value
    const newIds: string[] = []
    for (const el of clipboard.value) {
      const newId = crypto.randomUUID()
      newIds.push(newId)
      const clone = {
        ...JSON.parse(JSON.stringify(el)),
        id: newId,
        pageId: pageId,
        x: el.x + 5,
        y: el.y + 5
      }
      document.value.elements.push(clone as Element)
    }
    selectedIds.value = newIds
    selectedId.value = newIds[0]
  }

  function getSearchMatches(): Element[] {
    if (!searchText.value) return []
    const term = searchText.value.toLowerCase()
    return document.value.elements.filter(el => {
      if (el.type === 'text') {
        return (el as any).text?.toLowerCase().includes(term)
      }
      if (el.type === 'list') {
        const items = (el as any).items || []
        return items.some((item: any) => item.text?.toLowerCase().includes(term))
      }
      if (el.type === 'table') {
        const cols = (el as any).columns || []
        if (cols.some((c: any) => c.header?.toLowerCase().includes(term))) return true
        const rows = (el as any).rows || []
        return rows.some((r: any) => r.cells?.some((c: any) => c.text?.toLowerCase().includes(term)))
      }
      return false
    })
  }

  function findNext() {
    const matches = getSearchMatches()
    if (matches.length === 0) return
    searchMatchIndex.value = (searchMatchIndex.value + 1) % matches.length
    const match = matches[searchMatchIndex.value]
    selectElement(match.id)
    selectedPageId.value = match.pageId
  }

  function findPrevious() {
    const matches = getSearchMatches()
    if (matches.length === 0) return
    searchMatchIndex.value = searchMatchIndex.value <= 0 ? matches.length - 1 : searchMatchIndex.value - 1
    const match = matches[searchMatchIndex.value]
    selectElement(match.id)
    selectedPageId.value = match.pageId
  }

  function replaceCurrent() {
    if (!searchText.value || searchMatchIndex.value < 0) return
    const matches = getSearchMatches()
    if (searchMatchIndex.value >= matches.length) return
    const el = matches[searchMatchIndex.value]
    saveHistory()
    if (el.type === 'text') {
      const newText = (el as any).text.replace(new RegExp(searchText.value, 'gi'), replaceText.value)
      updateElement(el.id, { text: newText } as any)
    } else if (el.type === 'list') {
      const items = (el as any).items.map((item: any) => ({
        ...item,
        text: item.text.replace(new RegExp(searchText.value, 'gi'), replaceText.value)
      }))
      updateElement(el.id, { items } as any)
    }
  }

  function replaceAll() {
    if (!searchText.value) return
    saveHistory()
    const term = searchText.value
    const regex = new RegExp(term, 'gi')
    for (const el of document.value.elements) {
      if (el.type === 'text') {
        const newText = (el as any).text.replace(regex, replaceText.value)
        if (newText !== (el as any).text) {
          const idx = document.value.elements.findIndex((e: Element) => e.id === el.id)
          document.value.elements[idx] = { ...el, text: newText } as Element
        }
      } else if (el.type === 'list') {
        const items = (el as any).items.map((item: any) => ({
          ...item,
          text: item.text.replace(regex, replaceText.value)
        }))
        const idx = document.value.elements.findIndex((e: Element) => e.id === el.id)
        document.value.elements[idx] = { ...el, items } as Element
      }
    }
  }

  function toggleSearch() {
    searchVisible.value = !searchVisible.value
    if (!searchVisible.value) {
      searchText.value = ''
      replaceText.value = ''
      searchMatchIndex.value = -1
    }
  }

  function toggleVisibility(id: string) {
    const newSet = new Set(hiddenIds.value)
    if (newSet.has(id)) {
      newSet.delete(id)
    } else {
      newSet.add(id)
    }
    hiddenIds.value = newSet
  }

  function toggleLock(id: string) {
    const newSet = new Set(lockedIds.value)
    if (newSet.has(id)) {
      newSet.delete(id)
    } else {
      newSet.add(id)
    }
    lockedIds.value = newSet
  }

  function isHidden(id: string): boolean {
    return hiddenIds.value.has(id)
  }

  function isLocked(id: string): boolean {
    return lockedIds.value.has(id)
  }

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

  function getPageForElement(el: Element): Page | undefined {
    return document.value.pages.find(p => p.id === el.pageId)
  }

  function isElementOverflowing(el: Element): boolean {
    const page = getPageForElement(el)
    if (!page) return false
    const pageHeight = page.settings.height
    const pageWidth = page.settings.width
    const overflowsBottom = el.y + el.height > pageHeight
    const overflowsTop = el.y < 0
    const overflowsLeft = el.x < 0
    const overflowsRight = el.x + el.width > pageWidth
    return overflowsBottom || overflowsTop || overflowsLeft || overflowsRight
  }

  function getOverflowingElements(): Element[] {
    return document.value.elements.filter(el => isElementOverflowing(el))
  }

  function moveElementToNextPage(id: string) {
    const el = document.value.elements.find((e: Element) => e.id === id)
    if (!el) return
    const currentIdx = document.value.pages.findIndex(p => p.id === el.pageId)
    if (currentIdx === -1) return
    let nextPage = document.value.pages[currentIdx + 1]
    if (!nextPage) {
      nextPage = createDefaultPage()
      document.value.pages.push(nextPage)
    }
    saveHistory()
    const page = document.value.pages.find(p => p.id === el.pageId)!
    const newY = el.y - page.settings.height
    const idx = document.value.elements.findIndex(e => e.id === id)
    document.value.elements[idx] = { ...el, pageId: nextPage.id, y: Math.max(0, newY) } as Element
  }

  function exportJson(): string {
    return JSON.stringify(document.value, null, 2)
  }

  function migrateLegacyDocument(data: any): Document {
    let pages: Page[] = []
    let elements: Element[] = []

    if (data.page && !data.pages) {
      const pageId = data.page.id || createDefaultPage().id
      pages = [{
        id: pageId,
        name: data.page.name,
        settings: { ...DEFAULT_PAGE_SETTINGS, ...data.page.settings }
      }]
      elements = (data.elements || []).map((el: any) => ({
        ...el,
        id: el.id || crypto.randomUUID(),
        pageId: el.pageId || pageId
      }))
    } else {
      pages = (data.pages || []).map((p: any) => ({
        id: p.id || crypto.randomUUID(),
        name: p.name,
        settings: { ...DEFAULT_PAGE_SETTINGS, ...(p.settings || {}) }
      }))
      elements = (data.elements || []).map((el: any) => ({
        ...el,
        id: el.id || crypto.randomUUID(),
        pageId: el.pageId || (pages.length > 0 ? pages[0].id : '')
      }))
    }

    return {
      version: 2,
      name: data.name || 'Template',
      pages,
      defaultFont: data.defaultFont || 'helvetica',
      elements,
      sampleData: data.sampleData
    }
  }

  function importJson(json: string) {
    saveHistory()
    const data = JSON.parse(json)
    const migrated = migrateLegacyDocument(data)
    document.value = migrated
    selectedId.value = null
    if (migrated.pages.length > 0) {
      selectedPageId.value = migrated.pages[0].id
    }
  }

  function getSelectedElement(): Element | undefined {
    return document.value.elements.find((el: Element) => el.id === selectedId.value)
  }

  return {
    document, selectedId, selectedIds, selectedPageId, gridSize, zoom,
    activePage, currentPageElements,
    canUndo, canRedo, undo, redo,
    selectPage, addPage, removePage, duplicatePage,
    updatePageSettings, copyHeaderFooterFromPage, renamePage,
    addElement, updateElement, removeElement, duplicateElement,
    selectElement, toggleSelection, selectAll, clearSelection,
    moveElementUp, moveElementDown, moveSelectedElements, resizeSelectedElements,
    alignElements, distributeElements,
    groupElements, ungroupElement,
    copyElements, pasteElements,
    searchText, replaceText, searchVisible, searchMatchIndex,
    findNext, findPrevious, replaceCurrent, replaceAll, toggleSearch, getSearchMatches,
    hiddenIds, lockedIds, toggleVisibility, toggleLock, isHidden, isLocked,
    autoFitElement, moveElementToNextPage, isElementOverflowing, getOverflowingElements,
    exportJson, importJson, getSelectedElement
  }
})
