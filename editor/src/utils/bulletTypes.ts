export interface BulletType {
  id: string
  label: string
  svg: string
  pdfType: 'circle' | 'square' | 'dash' | 'diamond' | 'arrow'
  pdfSize?: number
}

export const BULLET_TYPES: BulletType[] = [
  {
    id: 'circle',
    label: 'Cerchio',
    svg: '<svg viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="6"/></svg>',
    pdfType: 'circle',
  },
  {
    id: 'square',
    label: 'Quadrato',
    svg: '<svg viewBox="0 0 24 24" fill="currentColor"><rect x="6" y="6" width="12" height="12"/></svg>',
    pdfType: 'square',
  },
  {
    id: 'dash',
    label: 'Tratto',
    svg: '<svg viewBox="0 0 24 24" fill="currentColor"><rect x="4" y="10" width="16" height="4"/></svg>',
    pdfType: 'dash',
  },
  {
    id: 'diamond',
    label: 'Diamante',
    svg: '<svg viewBox="0 0 24 24" fill="currentColor"><polygon points="12,4 20,12 12,20 4,12"/></svg>',
    pdfType: 'diamond',
  },
  {
    id: 'arrow',
    label: 'Freccia',
    svg: '<svg viewBox="0 0 24 24" fill="currentColor"><polygon points="4,12 16,12 12,6"/><polygon points="4,12 16,12 12,18"/></svg>',
    pdfType: 'arrow',
  },
  {
    id: 'number',
    label: 'Numero',
    svg: '<svg viewBox="0 0 24 24" fill="currentColor"><text x="12" y="16" text-anchor="middle" font-size="14">1</text></svg>',
    pdfType: 'circle',
  },
]

export function getBulletById(id: string): BulletType {
  return BULLET_TYPES.find(b => b.id === id) || BULLET_TYPES[0]
}
