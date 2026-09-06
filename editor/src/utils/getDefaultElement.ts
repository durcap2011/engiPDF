import { measureText, measureList } from './measureContent'
import type { TextElement, ListElement, TextStyle } from '../types'

export function getDefaultElement(type: string, x: number = 20, y: number = 20, pageId: string = ''): Record<string, unknown> {
  const base = { x, y, pageId }

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
    case 'table': {
      const defaultTextStyle: TextStyle = {
        font: 'helvetica', weight: 'normal', style: 'normal', size: 10,
        color: [0, 0, 0], align: 'left'
      }
      const el = {
        type: 'table' as const,
        x, y, width: 150, height: 30,
        name: 'tabella',
        columns: [
          { width: 50, header: 'Colonna 1', headerStyle: { ...defaultTextStyle, weight: 'bold' as const, align: 'center' as const } },
          { width: 50, header: 'Colonna 2', headerStyle: { ...defaultTextStyle, weight: 'bold' as const, align: 'center' as const } },
          { width: 50, header: 'Colonna 3', headerStyle: { ...defaultTextStyle, weight: 'bold' as const, align: 'center' as const } }
        ],
        rows: [
          { cells: [
            { text: '', style: {} },
            { text: '', style: {} },
            { text: '', style: {} }
          ] }
        ],
        repeatHeader: true,
        headerStyle: { ...defaultTextStyle, weight: 'bold' as const, align: 'center' as const },
        cellStyle: { ...defaultTextStyle },
        borderColor: [0, 0, 0] as [number, number, number],
        borderWidth: 0.5,
        mode: 'static' as const
      }
      return { ...base, ...el }
    }
    case 'image':
      return {
        ...base, type: 'image', width: 50, height: 30,
        src: '',
        fit: 'contain'
      }
    case 'ellipse':
      return {
        ...base, type: 'ellipse', width: 40, height: 40,
        fill: [0.9, 0.9, 0.9],
        stroke: [0, 0, 0],
        strokeWidth: 0.5
      }
    case 'divider':
      return {
        ...base, type: 'divider', width: 100, height: 1,
        color: [0, 0, 0],
        lineWidth: 0.5,
        lineStyle: 'solid'
      }
    case 'signature':
      return {
        ...base, type: 'signature', width: 60, height: 20,
        label: 'Firma',
        color: [0, 0, 0]
      }
    case 'container':
      return {
        ...base, type: 'container', width: 80, height: 60,
        fill: undefined,
        borderColor: [0, 0, 0],
        borderWidth: 0.5
      }
    case 'pageNumber':
      return {
        ...base, type: 'pageNumber', width: 30, height: 8,
        style: {
          font: 'helvetica', weight: 'normal', style: 'normal', size: 10,
          color: [0, 0, 0], align: 'center'
        }
      }
    case 'date':
      return {
        ...base, type: 'date', width: 40, height: 8,
        style: {
          font: 'helvetica', weight: 'normal', style: 'normal', size: 10,
          color: [0, 0, 0], align: 'left'
        },
        format: 'd/m/Y'
      }
    case 'watermark':
      return {
        ...base, type: 'watermark', width: 120, height: 30,
        text: 'BOZZA',
        fontSize: 48,
        color: [0.8, 0.8, 0.8],
        rotation: -45,
        opacity: 0.3
      }
    case 'qrcode':
      return {
        ...base, type: 'qrcode', width: 30, height: 30,
        text: 'https://example.com'
      }
    case 'spacer':
      return {
        ...base, type: 'spacer', width: 10, height: 20
      }
    case 'stamp':
      return {
        ...base, type: 'stamp', width: 60, height: 25,
        text: 'APPROVATO',
        preset: 'approved',
        color: [0, 0.6, 0]
      }
    case 'quote':
      return {
        ...base, type: 'quote', width: 100, height: 30,
        text: 'Una citazione importante...',
        author: 'Autore',
        barColor: [0.5, 0.5, 0.5],
        style: {
          font: 'times', weight: 'normal', style: 'italic', size: 12,
          color: [0.2, 0.2, 0.2], align: 'left'
        }
      }
    case 'callout':
      return {
        ...base, type: 'callout', width: 120, height: 25,
        text: 'Messaggio importante',
        style: 'info',
        icon: 'ℹ',
        bgColor: [0.9, 0.95, 1],
        borderColor: [0.2, 0.4, 0.8]
      }
    case 'codeBlock':
      return {
        ...base, type: 'codeBlock', width: 120, height: 40,
        text: 'console.log("Hello World");',
        language: 'javascript'
      }
    case 'progressBar':
      return {
        ...base, type: 'progressBar', width: 100, height: 10,
        value: 65,
        color: [0.2, 0.6, 0.9],
        bgColor: [0.9, 0.9, 0.9],
        label: '65%'
      }
    case 'icon':
      return {
        ...base, type: 'icon', width: 10, height: 10,
        name: 'check',
        color: [0, 0.6, 0]
      }
    case 'barcode':
      return {
        ...base, type: 'barcode', width: 60, height: 30,
        text: '123456789',
        format: 'code128',
        showText: true
      }
    case 'chart':
      return {
        ...base, type: 'chart', width: 100, height: 60,
        chartType: 'bar',
        data: [
          { label: 'A', value: 30 },
          { label: 'B', value: 50 },
          { label: 'C', value: 20 }
        ],
        colors: [[0.2, 0.4, 0.8], [0.8, 0.4, 0.2], [0.2, 0.8, 0.4]]
      }
    case 'pageBreak':
      return {
        ...base, type: 'pageBreak', width: 170, height: 5
      }
    case 'group':
      return {
        ...base, type: 'group', width: 60, height: 40,
        children: []
      }
    case 'dataRepeat':
      return {
        ...base, type: 'dataRepeat', width: 100, height: 60,
        repeatField: 'articoli',
        children: [],
        direction: 'vertical',
        spacing: 5
      }
    case 'checklist':
      return {
        ...base, type: 'checklist', width: 80, height: 30,
        items: [
          { text: 'Voce completata', checked: true },
          { text: 'Voce in corso', checked: false },
          { text: 'Voce da fare', checked: false },
        ],
        size: 11,
        color: [0, 0, 0],
        checkedColor: [0, 0.6, 0],
        gap: 3
      }
    case 'radio':
      return {
        ...base, type: 'radio', width: 80, height: 30,
        items: [
          { text: 'Opzione A', selected: true },
          { text: 'Opzione B', selected: false },
          { text: 'Opzione C', selected: false },
        ],
        size: 11,
        color: [0, 0, 0],
        selectedColor: [0, 0.5, 1],
        gap: 3
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
