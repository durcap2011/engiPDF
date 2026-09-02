import { measureText, measureList } from './measureContent'
import type { TextElement, ListElement } from '../types'

export function getDefaultElement(type: string, x: number = 20, y: number = 20): Record<string, unknown> {
  const base = { x, y }

  switch (type) {
    case 'text': {
      const el = {
        type: 'text' as const,
        x, y, width: 0, height: 0,
        text: 'Nuovo testo',
        style: {
          font: 'helvetica', weight: 'normal' as const, style: 'normal' as const, size: 12,
          color: [0, 0, 0] as [number, number, number], align: 'left' as const
        }
      }
      const size = measureText(el as TextElement)
      return { ...base, ...el, width: size.width, height: size.height }
    }
    case 'rectangle':
      return {
        ...base, type: 'rectangle', width: 40, height: 20,
        fill: [0.9, 0.9, 0.9],
        stroke: [0, 0, 0],
        strokeWidth: 0.5
      }
    case 'line':
      return {
        ...base, type: 'line', width: 60, height: 0,
        x2: x + 60, y2: y,
        color: [0, 0, 0],
        lineWidth: 0.5
      }
    case 'list': {
      const el = {
        type: 'list' as const,
        x, y, width: 0, height: 0,
        mode: 'static' as const,
        style: {
          font: 'helvetica', weight: 'normal' as const, style: 'normal' as const, size: 12,
          color: [0, 0, 0] as [number, number, number], align: 'left' as const,
          bullet: 'circle', bulletIndent: 5, textIndent: 15
        },
        items: [
          { text: 'Elemento 1' },
          { text: 'Elemento 2' },
          { text: 'Elemento 3' }
        ]
      }
      const size = measureList(el as ListElement)
      return { ...base, ...el, width: size.width, height: size.height }
    }
    case 'image':
      return {
        ...base, type: 'image', width: 50, height: 30,
        src: '',
        fit: 'contain'
      }
    default:
      return {
        ...base, type: 'text', width: 40, height: 6,
        text: 'Testo', style: {
          font: 'helvetica', weight: 'normal', style: 'normal', size: 12,
          color: [0, 0, 0], align: 'left'
        }
      }
  }
}
