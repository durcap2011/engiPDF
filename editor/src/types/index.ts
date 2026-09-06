export interface PageSettings {
  width: number
  height: number
  unit: 'mm' | 'pt' | 'px'
  margins: { top: number; right: number; bottom: number; left: number }
  headerHeight: number
  footerHeight: number
  headerSourcePageId?: string
  footerSourcePageId?: string
}

export interface Page {
  id: string
  name?: string
  settings: PageSettings
}

export interface Document {
  version: number
  name: string
  pages: Page[]
  defaultFont: string
  elements: Element[]
  sampleData?: Record<string, unknown>
}

export type RGB = [number, number, number]

export type ElementType = 'text' | 'image' | 'list' | 'rectangle' | 'line' | 'table' | 'group' | 'ellipse' | 'divider' | 'signature' | 'container' | 'pageNumber' | 'date' | 'watermark' | 'qrcode' | 'spacer' | 'stamp' | 'quote' | 'callout' | 'codeBlock' | 'progressBar' | 'icon' | 'barcode' | 'chart' | 'pageBreak' | 'dataRepeat' | 'checklist' | 'radio'

export interface ConditionalRule {
  field: string
  op: 'eq' | 'neq' | 'gt' | 'lt' | 'gte' | 'lte' | 'empty' | 'notempty' | 'contains'
  value?: string
}

export interface StyleRule extends ConditionalRule {
  then: Record<string, unknown>
}

export interface BaseElement {
  id: string
  pageId: string
  type: ElementType
  x: number
  y: number
  width: number
  height: number
  showIf?: ConditionalRule
  styleIf?: StyleRule[]
  repeatOnAllPages?: boolean
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

export interface TableColumn {
  width: number
  header: string
  headerStyle: TextStyle
}

export interface TableCell {
  text: string
  style: Partial<TextStyle>
}

export interface TableRow {
  cells: TableCell[]
}

export interface TableElement extends BaseElement {
  type: 'table'
  name: string
  columns: TableColumn[]
  rows: TableRow[]
  repeatHeader: boolean
  headerStyle: TextStyle
  cellStyle: TextStyle
  borderColor: RGB
  borderWidth: number
  mode: 'static' | 'dynamic'
  dynamicConfig?: { repeatField: string; columns: { field: string; header: string }[] }
}

export interface GroupElement extends BaseElement {
  type: 'group'
  children: Element[]
}

export interface EllipseElement extends BaseElement {
  type: 'ellipse'
  fill?: RGB
  stroke?: RGB
  strokeWidth?: number
}

export interface DividerElement extends BaseElement {
  type: 'divider'
  color: RGB
  lineWidth: number
  lineStyle: 'solid' | 'dashed' | 'dotted'
}

export interface SignatureElement extends BaseElement {
  type: 'signature'
  label: string
  color: RGB
}

export interface ContainerElement extends BaseElement {
  type: 'container'
  fill?: RGB
  borderColor: RGB
  borderWidth: number
}

export interface PageNumberElement extends BaseElement {
  type: 'pageNumber'
  style: TextStyle
}

export interface DateElement extends BaseElement {
  type: 'date'
  style: TextStyle
  format: string
}

export interface WatermarkElement extends BaseElement {
  type: 'watermark'
  text: string
  fontSize: number
  color: RGB
  rotation: number
  opacity: number
}

export interface QrCodeElement extends BaseElement {
  type: 'qrcode'
  text: string
}

export interface SpacerElement extends BaseElement {
  type: 'spacer'
}

export interface StampElement extends BaseElement {
  type: 'stamp'
  text: string
  preset: 'approved' | 'confidential' | 'draft' | 'paid' | 'urgent'
  color: RGB
}

export interface QuoteElement extends BaseElement {
  type: 'quote'
  text: string
  author: string
  barColor: RGB
  style: TextStyle
}

export interface CalloutElement extends BaseElement {
  type: 'callout'
  text: string
  style: 'info' | 'warning' | 'error' | 'success'
  icon: string
  bgColor: RGB
  borderColor: RGB
}

export interface CodeBlockElement extends BaseElement {
  type: 'codeBlock'
  text: string
  language: string
}

export interface ProgressBarElement extends BaseElement {
  type: 'progressBar'
  value: number
  color: RGB
  bgColor: RGB
  label: string
}

export interface IconElement extends BaseElement {
  type: 'icon'
  name: 'check' | 'warning' | 'info' | 'error' | 'star' | 'heart' | 'arrow'
  color: RGB
}

export interface BarcodeElement extends BaseElement {
  type: 'barcode'
  text: string
  format: 'code128' | 'code39' | 'ean13'
  showText: boolean
}

export interface ChartElement extends BaseElement {
  type: 'chart'
  chartType: 'bar' | 'pie' | 'line'
  data: { label: string; value: number }[]
  colors: RGB[]
}

export interface PageBreakElement extends BaseElement {
  type: 'pageBreak'
}

export interface DataRepeatElement extends BaseElement {
  type: 'dataRepeat'
  repeatField: string
  children: Element[]
  direction: 'vertical' | 'horizontal'
  spacing: number
}

export interface ChecklistElement extends BaseElement {
  type: 'checklist'
  items: { text: string; checked: boolean }[]
  size: number
  color: RGB
  checkedColor: RGB
  gap: number
}

export interface RadioElement extends BaseElement {
  type: 'radio'
  items: { text: string; selected: boolean }[]
  size: number
  color: RGB
  selectedColor: RGB
  gap: number
}

export type Element = TextElement | ImageElement | ListElement | RectangleElement | LineElement | TableElement | GroupElement | EllipseElement | DividerElement | SignatureElement | ContainerElement | PageNumberElement | DateElement | WatermarkElement | QrCodeElement | SpacerElement | StampElement | QuoteElement | CalloutElement | CodeBlockElement | ProgressBarElement | IconElement | BarcodeElement | ChartElement | PageBreakElement | DataRepeatElement | ChecklistElement | RadioElement

export const DEFAULT_PAGE_SETTINGS: PageSettings = {
  width: 210,
  height: 297,
  unit: 'mm',
  margins: { top: 30, right: 20, bottom: 30, left: 20 },
  headerHeight: 20,
  footerHeight: 20
}

export function createDefaultPage(id?: string): Page {
  return {
    id: id || crypto.randomUUID(),
    name: undefined,
    settings: { ...DEFAULT_PAGE_SETTINGS }
  }
}

export const MM_TO_PT = 72 / 25.4
export const MM_TO_PX = 96 / 25.4
