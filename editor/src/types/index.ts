export interface Document {
  version: number
  name: string
  page: PageSettings
  defaultFont: string
  elements: Element[]
}

export interface PageSettings {
  width: number
  height: number
  unit: 'mm' | 'pt' | 'px'
  margins: { top: number; right: number; bottom: number; left: number }
}

export type RGB = [number, number, number]

export type ElementType = 'text' | 'image' | 'list' | 'rectangle' | 'line'

export interface BaseElement {
  id: string
  type: ElementType
  x: number
  y: number
  width: number
  height: number
}

export interface TextStyle {
  font: string
  weight: 'normal' | 'bold'
  style: 'normal' | 'italic' | 'oblique'
  underline?: boolean
  size: number
  color: RGB
  align: 'left' | 'center' | 'right' | 'justify'
  lineHeight?: number
}

export interface TextElement extends BaseElement {
  type: 'text'
  text: string
  style: TextStyle
}

export interface ImageElement extends BaseElement {
  type: 'image'
  src: string
  fit: 'contain' | 'cover' | 'stretch'
}

export interface ListStyle extends TextStyle {
  bullet: string
  bullets?: string[]
  bulletIndent: number
  textIndent: number
}

export interface ListItem {
  text: string
  items?: ListItem[]
}

export interface ListElement extends BaseElement {
  type: 'list'
  mode: 'static' | 'dynamic'
  style: ListStyle
  items: ListItem[]
  dynamicConfig?: { repeatField: string; textField: string }
}

export interface RectangleElement extends BaseElement {
  type: 'rectangle'
  fill?: RGB
  stroke?: RGB
  strokeWidth?: number
}

export interface LineElement extends BaseElement {
  type: 'line'
  x2: number
  y2: number
  color: RGB
  lineWidth: number
}

export type Element = TextElement | ImageElement | ListElement | RectangleElement | LineElement

export const DEFAULT_PAGE: PageSettings = {
  width: 210,
  height: 297,
  unit: 'mm',
  margins: { top: 20, right: 20, bottom: 20, left: 20 }
}

export const MM_TO_PT = 72 / 25.4
export const MM_TO_PX = 96 / 25.4
