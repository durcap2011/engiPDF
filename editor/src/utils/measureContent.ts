import type { TextElement, ListElement } from '../types'

const PX_TO_MM = 25.4 / 96

const FONT_MAP: Record<string, string> = {
  helvetica: 'Helvetica, Arial, sans-serif',
  arial: 'Helvetica, Arial, sans-serif',
  'sans-serif': 'Helvetica, Arial, sans-serif',
  times: 'Times New Roman, serif',
  serif: 'Times New Roman, serif',
  courier: 'Courier New, monospace',
  monospace: 'Courier New, monospace',
}

/**
 * Misura la dimensione del testo in mm (width × height)
 */
export function measureText(el: TextElement): { width: number; height: number } {
  const s = el.style
  const fontCSS = FONT_MAP[s.font] || s.font
  const weight = s.weight === 'bold' ? '700' : '400'
  const fontStyle = s.style || 'normal'
  const fontSizePx = s.size * (96 / 72)

  const canvas = document.createElement('canvas')
  const ctx = canvas.getContext('2d')!
  ctx.font = `${fontStyle} ${weight} ${fontSizePx}px ${fontCSS}`

  const lines = el.text.split('\n')
  let maxWidth = 0
  for (const line of lines) {
    const m = ctx.measureText(line)
    if (m.width > maxWidth) maxWidth = m.width
  }

  const lineHeight = fontSizePx * (s.lineHeight || 1.4)
  const totalHeight = lines.length * lineHeight

  // Aggiunge un piccolo padding
  const padX = 2
  const padY = 2

  return {
    width: Math.ceil((maxWidth + padX) * PX_TO_MM),
    height: Math.ceil((totalHeight + padY) * PX_TO_MM),
  }
}

/**
 * Misura l'altezza della lista in mm (ricorsivo per liste annidate)
 */
export function measureList(el: ListElement): { width: number; height: number } {
  const s = el.style
  const fontCSS = FONT_MAP[s.font] || s.font
  const weight = s.weight === 'bold' ? '700' : '400'
  const fontStyle = s.style || 'normal'
  const fontSizePx = s.size * (96 / 72)

  const canvas = document.createElement('canvas')
  const ctx = canvas.getContext('2d')!
  ctx.font = `${fontStyle} ${weight} ${fontSizePx}px ${fontCSS}`

  let maxWidth = 0
  let totalHeight = 0

  function measureItems(items: { text: string; items?: { text: string; items?: any[] }[] }[], level: number) {
    const textIndentPx = (s.textIndent || 15) * (96 / 25.4)
    const levelIndent = level * 20

    for (const item of items) {
      const m = ctx.measureText(item.text)
      const itemWidth = m.width + textIndentPx + levelIndent
      if (itemWidth > maxWidth) maxWidth = itemWidth
      totalHeight += fontSizePx * (s.lineHeight || 1.4)

      if (item.items && item.items.length > 0) {
        measureItems(item.items, level + 1)
      }
    }
  }

  measureItems(el.items, 0)

  return {
    width: Math.ceil((maxWidth * PX_TO_MM) + 5),
    height: Math.ceil(totalHeight * PX_TO_MM + 5),
  }
}
