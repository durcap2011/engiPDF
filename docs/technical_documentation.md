# engiPDF - Technical Documentation

## General Architecture

engiPDF is a two-phase system composed of a web editor (Vue 3 + TypeScript + Vite + Pinia) and a native PHP 8.2+ PDF generator (without external libraries). The two systems communicate via JSON files that represent the document template.

```
┌──────────────────────────┐     ┌──────────────────────────┐
│      Frontend (Vue 3)    │     │      Backend (PHP 8.2+)  │
│                          │     │                          │
│  editor/                 │ JSON│  engine/                  │
│  ├── components/         │────►│  ├── src/                 │
│  │   ├── Canvas/         │     │  │   ├── PdfWriter/       │
│  │   │   ├── EditorCanvas│     │  │   ├── Font/            │
│  │   │   ├── PageArtboard│     │  │   ├── Renderer/        │
│  │   │   ├── Horizontal  │     │  │   └── Template/        │
│  │   │   ├── Vertical    │     │  └── test_*.php           │
│  │   │   └── ElementWrap │     │                          │
│  │   ├── Sidebar/        │     │                          │
│  │   ├── Properties/     │     │                          │
│  │   └── Toolbar/        │     │                          │
│  ├── elements/           │     │                          │
│  ├── stores/             │     │                          │
│  ├── types/              │     │                          │
│  └── utils/              │     │                          │
└──────────────────────────┘     └──────────────────────────┘
```

---

## Frontend - Vue 3 Editor

### Technology Stack
- **Vue 3** (Composition API, `<script setup>`)
- **TypeScript** (type checking with `vue-tsc`)
- **Vite** (bundler, dev server on port 5173)
- **Pinia** (state management with undo/redo)
- **vue-i18n** (internationalization, 5 languages: en, it, es, de, fr)

### UI Layout (`App.vue`)

CSS grid layout with 3 panels:

```css
.app-layout {
  display: flex;
  flex-direction: column;
  height: 100vh;
}
.app-body {
  display: flex;
  flex: 1;
}
```

Layout components:
- `EditorToolbar` (at top, fixed height ~48px)
- `LayersPanel` (left, fixed width 220px)
- `ComponentPalette` (left, fixed width 160px) — palette with accordion for categories, internationalized texts via vue-i18n (keys `palette.*`)
- `EditorCanvas` (center, flex: 1) — includes rulers
- `PropertyPanel` (right, fixed width 260px)
- `SearchReplace` (overlay at top right)

### Canvas Structure (`EditorCanvas.vue`)

The canvas is organized in a 2×2 CSS grid:

```
┌──────────────────────────────────┐
│ RulerCorner │ HorizontalRuler    │  ← row 1 (24px)
├─────────────┼────────────────────┤
│ VerticalR.  │    EditorCanvas    │  ← row 2 (flex: 1)
└─────────────┴────────────────────┘
```

**Multi-Page Rendering**:
- The canvas renders multiple `PageArtboard` components in a column with spacing `PAGE_GAP = 40px`
- Each artboard is independent, with its own dimensions and rulers
- Initial centering on the first page at component mount
- `panY` is calculated to position the active page at the center of the viewport

**Reactive properties**:
- `panX`, `panY`: movement offset in pixels
- `zoom`: scale factor (0.2 – 5.0)
- `isPanning`: flag for active dragging

**CSS Transformation**: the canvas content is transformed with:
```css
transform: translate(panXpx, panYpx) scale(zoom);
transformOrigin: 0 0;
```

### PageArtboard (`PageArtboard.vue`)

Each page is rendered as a `PageArtboard` component:

**Props**:
- `page: Page` — page object with id, name and settings
- `isActive: boolean` — indicates whether it is the currently selected page

**Behavior**:
- Filters elements from the document by `element.pageId === page.id`
- Emits the `selectPage` event when the user clicks on the artboard
- Shows a label with the progressive page number at top left
- Highlights the border when the page is active

**Layout**:
- Each artboard has fixed dimensions based on `page.settings.width` and `page.settings.height`
- Contains its own set of horizontal and vertical rulers
- Elements are rendered inside it with the same drag, resize and selection logic

### Properties Panel (`PropertyPanel.vue`)

The properties panel has been updated for the multi-page system and i18n support:

**Internationalization (i18n)**:
- All visible template strings are translated via `useI18n()` and the `properties.*` keys
- The keys are defined in 5 locale files: `it.ts`, `en.ts`, `es.ts`, `de.ts`, `fr.ts`
- Tooltips (attributes `title`) are dynamic bindings (`:title="t('properties.xxx')"`)
- Dynamic strings use parameter syntax: `t('properties.pageNName', { n: ..., name: ... })`

**Page references**:
- All references to `store.document.page` have been changed to `store.activePage.settings`
- The `updatePage()` method has been renamed to `updatePageSettings(pageId, settings)`
- Page setting changes apply only to the active page

**Content**:
- Element properties (position, dimensions, style)
- Page properties (dimensions, margins, header, footer) — only for the active page
- Page selection in the toolbar automatically updates the panel

### Editor Toolbar (`EditorToolbar.vue`)

The toolbar includes specific controls for page management and new features:

**Navigation controls**:
- ◀/▶ buttons to navigate between pages
- Page counter (e.g. "2 / 5")
- Navigation is cyclic: from the first it goes to the last, from the last to the first

**Page actions**:
- Add page: creates a new page at the end of the document
- Duplicate page: duplicates the active page with all its elements
- Delete page: removes the active page (disabled if it's the only page)

**Multi-selection**:
- Alignment buttons (6 directions): visible with 2+ elements selected
- Distribution buttons (2 axes): visible with 3+ elements selected
- Group button: visible with 2+ elements selected
- Ungroup button: visible with 1 group selected

### Layers Panel (`LayersPanel.vue`)

The layers panel shows all elements of the current page in z-order. All interface texts are internationalized via vue-i18n (keys `layers.*` and `element.*`).

**Features**:
- **Selection**: click on an element in the list to select it
- **Visibility**: eye icon to hide/show an element
- **Lock**: lock icon to lock/unlock an element
- Reverse order: foreground elements are at the top of the list
- Highlighting of the selected element

**Editor integration**:
- Hidden elements are displayed with reduced opacity on the canvas
- Locked elements cannot be selected or modified
- Visibility and lock state are persisted in the Pinia state

### Search and Replace Bar (`SearchReplace.vue`)

Overlay component for text search and replace:

**Features**:
- Case-insensitive search in all text elements and lists
- Navigation between results with ◀/▶ or Enter/Shift+Enter
- Single replace or replace all results
- Close with Esc or ✕ button
- Result counter (e.g. "3/15")

**Integration**:
- `Ctrl+F` opens/closes the bar
- Found elements are automatically selected
- Navigation changes the current page if necessary

### Coordinate System

The editor operates in millimeters (`MM_TO_PX = 96 / 25.4 ≈ 3.7795`):
- All element coordinates are in mm
- Conversion to pixels happens at CSS rendering time
- The PDF uses points (`MM_TO_PT = 72 / 25.4 ≈ 2.8346`)
- The coordinate origin is the top-left corner of the page (consistent between editor and rendering, but inverted compared to PDF which has origin at bottom-left)

### Pinia Store (`editorStore.ts`)

**Main state**:
```typescript
interface EditorState {
  document: Document
  selectedId: string | null
  selectedIds: string[]        // Array of selected IDs (multi-selection)
  selectedPageId: string | null   // ID of the active page
  gridSize: 1            // Constant fixed at 1 mm (not modifiable)
  zoom: number
  clipboard: Element[]   // Elements copied for paste
  searchText: string     // Search text
  replaceText: string    // Replace text
  searchVisible: boolean // Search bar visibility
  searchMatchIndex: number // Index of the current match
  hiddenIds: Set<string> // IDs of hidden elements
  lockedIds: Set<string> // IDs of locked elements
}
```

**Computed properties**:
- `activePage`: returns the current `Page` object based on `selectedPageId`
- `currentPageElements`: filters document elements by `pageId === selectedPageId`

**History (Undo/Redo)**:
- Array of serialized JSON snapshots
- Max 50 levels
- `saveHistory()` is called before every change
- Complete state serialization at each snapshot

**CRUD Operations**:
- `addElement(type)`: creates element with UUID via `crypto.randomUUID()` and assigns `pageId` of the active page
- `updateElement(id, patch)`: updates partial properties
- `removeElement(id)`: deletes element (supports multi-selection)
- `duplicateElement(id)`: creates copy with 5mm offset (supports multi-selection)
- `moveElementUp/Down(id)`: reorders in the stack

**Multi-selection**:
- `selectElement(id)`: selects a single element
- `toggleSelection(id)`: adds/removes an element from the selection
- `selectAll()`: selects all elements of the current page
- `clearSelection()`: clears the selection
- `moveSelectedElements(dx, dy)`: moves all selected elements
- `resizeSelectedElements(dw, dh)`: resizes all selected elements

**Alignment and Distribution**:
- `alignElements(direction)`: aligns selected elements (left, center, right, top, middle, bottom)
- `distributeElements(axis)`: distributes selected elements (horizontal, vertical)

**Grouping**:
- `groupElements(ids)`: creates a GroupElement containing the selected elements
- `ungroupElement(groupId)`: separates a group into the original elements

**Copy and Paste**:
- `copyElements()`: copies the selected elements to the clipboard
- `pasteElements(targetPageId?)`: pastes elements from the clipboard

**Search and Replace**:
- `findNext()`: finds the next match
- `findPrevious()`: finds the previous match
- `replaceCurrent()`: replaces the current match
- `replaceAll()`: replaces all matches
- `getSearchMatches()`: returns all elements matching the search

**Visibility and Lock**:
- `toggleVisibility(id)`: hides/shows an element
- `toggleLock(id)`: locks/unlocks an element
- `isHidden(id)`: checks if an element is hidden
- `isLocked(id)`: checks if an element is locked

**Page Management**:
- `addPage()`: adds a new page at the end with default settings
- `removePage(pageId)`: deletes a page and all its elements; if it's the last page, deletion is not allowed
- `duplicatePage(pageId)`: duplicates a page with all its elements, assigning new UUIDs
- `selectPage(pageId)`: changes the active page
- `updatePageSettings(pageId, settings)`: updates the settings of a page (dimensions, margins, header/footer)
- `renamePage(pageId, name)`: renames a page
- `copyHeaderFooterFromPage(sourcePageId, targetPageId, zone)`: copies header/footer elements from a source page to a target page

**Backward Compatibility**:
- `migrateLegacyDocument()`: automatically converts the legacy format (single page with `page` + separate `elements`) to the v2 multi-page format (`pages[]` + `elements[]` with `pageId`). Also handles hand-written JSON:
  - Generates UUIDs for pages and elements if missing
  - Assigns `pageId` to elements if missing (to the first page)
  - Applies default settings to pages (A4, standard margins)
  - Fills optional fields with default values

**Overflow Management**:
- `isElementOverflowing(elementId)`: checks if an element exceeds the page boundaries
- `getOverflowingElements()`: returns all elements that exceed the active page boundaries
- `moveElementToNextPage(elementId)`: moves an element to the next page, creating it if necessary

**Import/Export JSON**:
- `exportJson()`: serializes the entire document in JSON format (v2)
- `importJson(json)`: imports a JSON template with automatic migration via `migrateLegacyDocument()`

### Types (`types/index.ts`)

The multi-page system introduces new interfaces and modifies existing ones:

**New `Page` type**:
```typescript
interface Page {
  id: string          // UUID generated by createDefaultPage()
  name: string        // display name (e.g. "Page 1")
  settings: PageSettings  // dimensions, margins, header/footer
}
```

**Updated `PageSettings`**:
```typescript
interface PageSettings {
  width: number
  height: number
  unit: 'mm' | 'pt' | 'px'
  margins: { top: number; right: number; bottom: number; left: number }
  headerHeight: number
  footerHeight: number
  headerSourcePageId?: string  // Source page ID to copy header from
  footerSourcePageId?: string  // Source page ID to copy footer from
}
```

**Updated `Document`**:
```typescript
interface Document {
  version: 2
  pages: Page[]       // Array of pages (replaces page: PageSettings)
  elements: Element[] // All elements, with pageId field
}
```

**Extended `BaseElement`**:
```typescript
interface BaseElement {
  id: string
  type: string
  x: number
  y: number
  width: number
  height: number
  pageId: string      // UUID of the parent page
  // ... other properties
}
```

**New `GroupElement` type**:
```typescript
interface GroupElement extends BaseElement {
  type: 'group'
  children: Element[]  // Child elements of the group (relative coordinates)
}
```

**Updated `ElementType`**:
```typescript
type ElementType = 'text' | 'image' | 'list' | 'rectangle' | 'line' | 'table' | 'group' | 'ellipse' | 'divider' | 'signature' | 'container' | 'pageNumber' | 'date' | 'watermark' | 'qrcode' | 'spacer' | 'stamp' | 'quote' | 'callout' | 'codeBlock' | 'progressBar' | 'icon' | 'barcode' | 'chart' | 'pageBreak'
```

**New component interfaces (batch 2)**:
```typescript
interface SpacerElement extends BaseElement {
  type: 'spacer'
}

interface StampElement extends BaseElement {
  type: 'stamp'
  text: string
  preset: 'approved' | 'confidential' | 'draft' | 'paid' | 'urgent'
  color: RGB
}

interface QuoteElement extends BaseElement {
  type: 'quote'
  text: string
  author: string
  barColor: RGB
  style: TextStyle
}

interface CalloutElement extends BaseElement {
  type: 'callout'
  text: string
  style: 'info' | 'warning' | 'error' | 'success'
  icon: string
  bgColor: RGB
  borderColor: RGB
}

interface CodeBlockElement extends BaseElement {
  type: 'codeBlock'
  text: string
  language: string
}

interface ProgressBarElement extends BaseElement {
  type: 'progressBar'
  value: number
  color: RGB
  bgColor: RGB
  label: string
}

interface IconElement extends BaseElement {
  type: 'icon'
  name: 'check' | 'warning' | 'info' | 'error' | 'star' | 'heart' | 'arrow'
  color: RGB
}

interface BarcodeElement extends BaseElement {
  type: 'barcode'
  text: string
  format: 'code128' | 'code39' | 'ean13'
  showText: boolean
}

interface ChartElement extends BaseElement {
  type: 'chart'
  chartType: 'bar' | 'pie' | 'line'
  data: { label: string; value: number }[]
  colors: RGB[]
}

interface PageBreakElement extends BaseElement {
  type: 'pageBreak'
}
```

**New component interfaces**:
```typescript
interface EllipseElement extends BaseElement {
  type: 'ellipse'
  fill?: RGB
  stroke?: RGB
  strokeWidth?: number
}

interface DividerElement extends BaseElement {
  type: 'divider'
  color: RGB
  lineWidth: number
  lineStyle: 'solid' | 'dashed' | 'dotted'
}

interface SignatureElement extends BaseElement {
  type: 'signature'
  label: string
  color: RGB
}

interface ContainerElement extends BaseElement {
  type: 'container'
  fill?: RGB
  borderColor: RGB
  borderWidth: number
}

interface PageNumberElement extends BaseElement {
  type: 'pageNumber'
  style: TextStyle
}

interface DateElement extends BaseElement {
  type: 'date'
  style: TextStyle
  format: string
}

interface WatermarkElement extends BaseElement {
  type: 'watermark'
  text: string
  fontSize: number
  color: RGB
  rotation: number
  opacity: number
}

interface QrCodeElement extends BaseElement {
  type: 'qrcode'
  text: string
}
```

**Constants and factories**:
- `DEFAULT_PAGE_SETTINGS`: object with default settings for a page (was `DEFAULT_PAGE`)
- `createDefaultPage()`: creates a `Page` object with UUID and incremental name

### ElementWrapper (`ElementWrapper.vue`)

Every element rendered in the canvas is wrapped by `ElementWrapper`:

**Overflow features**:
- When an element exceeds the page boundaries, a visual indicator is shown (dashed red borders)
- A "Move to next page" button is shown that invokes `moveElementToNextPage()`
- The overflow check is performed on every element movement/resize

**Multi-selection**:
- `Shift+Click` adds/removes an element from the selection
- Dragging a selected element moves all selected elements
- Multi-resize: proportional resizing of all selected elements

**Visibility and Lock**:
- Hidden elements are displayed with reduced opacity (opacity: 0.3)
- Locked elements cannot be selected or modified (pointer-events: none)
- Visibility and lock state are managed via Sets in the Pinia state

**Behavior**:
- Drag & drop with snap to grid
- Resize with 8 handles (4 corners + 4 sides)
- Selection with highlight
- Double-click for inline editing (text and lists)
- The cursor changes to "not-allowed" for locked elements

### Rulers (inline in `EditorCanvas.vue`)

The rulers are implemented as inline SVG directly in the `EditorCanvas.vue` template, not as separate components.

**CSS Layout**:
- Structure `.ruler-and-page` → `.page-row` (horizontal flex)
- `.ruler-vertical`: 20px width, `margin-top: 20px` to start from the page beginning (below the horizontal ruler), height `pageHeightPx`
- `.page-column`: contains horizontal ruler (20px height) and `PageArtboard`

**Tick calculation**:
- `horizontalMarks`: iteration from 0 to `page.width` (mm), ticks every 5mm, major ticks every 10mm
- `verticalMarks`: iteration from 0 to `page.height` (mm), ticks every 5mm, major ticks every 10mm
- Each mark: `{ px: number, mm: number, major: boolean }`

**SVG Rendering**:
- Vertical ruler: SVG 20 × pageHeightPx, ticks with variable x1 (0 for major, 12 for minor), x2=19
- Horizontal ruler: SVG pageWidthPx × 20, ticks with variable y1 (0 for major, 12 for minor), y2=19
- Numbers (major only) use `writing-mode: vertical-rl` with `transform: rotate(180deg)` for vertical

**Pan/zoom synchronization**:
- The rulers are children of `.canvas-content` which applies the CSS transformation
- Pan and zoom apply naturally without additional calculations

### Font System

**Frontend Mapping** (`measureContent.ts`):
- `FONT_MAP` associates CSS font names with font families for measurement via Canvas API
- Used for auto-sizing of text and lists

**Aliases** (defined in `FontManager.php`):
```
arial/helv/sans-serif → helvetica
times-new-roman/serif/georgia/bookman → times
monospace/courier-new/mono → courier
```

**Font key in PDF**: `family:weight:style` (e.g. `helvetica:bold:normal`)

**Character encoding** (`PdfPage::encodePdfString()`):
- UTF-8 texts are converted to ISO-8859-1 (Latin-1) for standard Type1 fonts
- Type1 fonts declare `/Encoding /WinAnsiEncoding` in the PDF to correctly map bytes to glyphs
- Supports European accented characters: à è é ì ò ù ñ ü ä ö ß etc.
- Parenthesis characters `(`, `)` and backslash `\` are correctly escaped
- For characters outside the Latin-1 range, a custom TTF font is required

### Placeholder System (`PlaceholderResolver.php`)

Syntax: `{{ variable }}` or `{{ object.property }}`

Available filters:
- `currency`: currency formatting
- `date:"d/m/Y"`: date formatting
- `number:N`: N decimal places
- `uppercase`, `lowercase`, `capitalize`, `trim`, `truncate`, `default`, `if`, `sum`, `len`

**Elements with placeholder support**:
| Type | Resolved Properties | Notes |
|------|---------------------|-------|
| text | `text` | Supported from the initial version |
| list | `items[].text` | Supported from the initial version |
| table | `columns[].header`, `rows[].cells[].text` | Supported from the initial version |
| quote | `text`, `author` | Bug fixed: previously passed `[]` instead of `$data` |
| callout | `text` | Bug fixed: previously passed `[]` instead of `$data` |
| checklist | `items[].text` | Bug fixed: previously passed `[]` instead of `$data` |
| radio | `items[].text` | Bug fixed: previously passed `[]` instead of `$data` |
| watermark | `text` | Placeholder support added |
| stamp | `text` | Placeholder support added |
| barcode | `text` | Placeholder support added |
| qrcode | `text` | Placeholder support added |
| codeBlock | `text` | Placeholder support added |
| signature | `label` | Placeholder support added |
| progressBar | `label` | Placeholder support added |

### Element Factory (`getDefaultElement.ts`)

Every element type has a factory that returns an object with default values. **Important**: the factory does NOT include the `id` field — the store generates it automatically via `crypto.randomUUID()`.

### Table (`TableElement`)

**TypeScript Interface**:
- `name: string` — table name, used as a placeholder for dynamic data (`{{ name }}`)
- `columns: TableColumn[]` — columns with `width` (relative weight), `header` (text), `headerStyle` (complete TextStyle for header)
- `rows: TableRow[]` — template rows (default 1 empty row), each cell is a `TableCell` with `text` and `style` (partial override)
- `repeatHeader: boolean` — repeats the header on each new page
- `headerStyle: TextStyle` — default style for headers
- `cellStyle: TextStyle` — default style for data cells
- `borderColor / borderWidth` — border properties

**PDF Rendering** (`PdfRenderer::renderTable()`):
1. If `name` is set and `$data[$name]` is an array, rows are populated from external data
2. Each header uses its own individual `headerStyle` (font, weight, alignment, color, etc.)
3. Each data cell merges the default style with the per-cell override (`cell.style`)
4. Supports `{{ }}` placeholders in headers and cells
5. Column widths are proportional to the relative weight
6. The table is automatically split across multiple pages when it exceeds the usable area
7. The header is repeated on each new page (if `repeatHeader` is true)

---

## Backend - PHP Engine

### Structure

```
engine/
├── src/
│   ├── PdfWriter/
│   │   ├── PdfDocument.php      # Binary PDF writer (Header→Body→Xref→Trailer)
│   │   └── PdfPage.php          # Drawing primitives (text, rect, line, circle, polygon, image)
│   ├── Font/
│   │   └── FontManager.php      # Type1 resolution + TTF registration + PDF object allocation
│   ├── Renderer/
│   │   └── PdfRenderer.php      # Template→PDF renderer (handles all element types + multi-page)
│   └── Template/
│       ├── PlaceholderResolver.php  # {{ }} syntax with filters
│       └── TemplateLoader.php       # Template file loader (supports v2 and legacy formats)
├── composer.json                # PSR-4 Autoload: EngiPDF\
└── test_*.php                   # Test scripts
```

> **Complete reference**: For a JSON example with **all 28 element types** and **all supported properties**, see the [`template.md`](template.md) file in the project root.

### PDF Generation (`PdfDocument.php`)

The PDF is written byte-by-byte without external libraries:

1. **Header**: `%PDF-1.4`
2. **Body**: PDF objects (pages, fonts, content streams)
3. **Cross-Reference Table**: offset of each object
4. **Trailer**: reference to root and object count

**Multi-Page**:
- `PdfDocument::createPage($width, $height)`: now accepts optional dimensions per page; if not specified, uses default A4
- `PdfDocument::$pageDimensions`: associative array storing dimensions (`[width, height]`) for each created page, necessary for Y coordinate calculation during rendering

### PDF vs Editor Coordinates

| Aspect | Editor | PDF |
|---|---|---|
| Origin | Top-left | Bottom-left |
| Unit | mm | points (1/72 inch) |
| Conversion | `px = mm × 96/25.4` | `pt = mm × 72/25.4` |
| Y flow | increasing downward | increasing upward |

The `PdfRenderer` converts Y coordinates: `y_pdf = pageHeight_pt - y_mm × MM_TO_PT - height_mm × MM_TO_PT`

### Usable Area and Pagination

The `PdfRenderer` calculates a usable area per page based on header and footer:

```php
$contentTop = pageHeight - headerHeight;    // Upper limit of content area
$contentBottom = footerHeight;              // Lower limit of content area
```

- **Header**: fixed area at the top of each page (height specified in `page.headerHeight`)
- **Footer**: fixed area at the bottom of each page (height specified in `page.footerHeight`)
- **Content area**: space between header and footer where elements are positioned
- **Pagination**: when an element (table) exceeds the available area, a new page is created
- **Header repetition**: tables with `repeatHeader: true` repeat the header on each new page

### Font Manager (`FontManager.php`)

Manages 14 built-in Type1 fonts + custom TTF fonts:

**Type1 (embedded)**:
- Helvetica (normal, bold, italic, bolditalic)
- Times (normal, bold, italic, bolditalic)
- Courier (normal, bold, italic, bolditalic)

**TTF (FlateDecode)**:
- Registered via JSON template: `{"fonts": {"MyFont": "path/to/font.ttf"}}`
- Embedding with FlateDecode compression
- Width table generation based on measurement

### Image Rendering

Images are embedded in the PDF as XObject Images:

1. **Data URL parsing**: extracts format and raw data from the base64 data URL (`data:image/TYPE;base64,...`)
2. **Supported formats**:
   - **JPEG/JPG**: binary data embedded directly with `/DCTDecode` filter
   - **PNG**: converted to JPEG via GD library (`imagecreatefrompng` → `imagejpeg` with 90% quality)
3. **Registration**: JPEG data is registered in the `PdfDocument` via `registerImage()`
4. **XObject**: during PDF rendering, an XObject Image object is created with `/Filter /DCTDecode`
5. **Page resource**: the XObject is added to each page's `/XObject` resource
6. **Rendering**: `$page->image($x, $y, $w, $h, $imageName)` draws the image with `cm` matrix + `Do`

**Generated PDF flow**:
```
q
{width} 0 0 {height} {x} {y} cm
/{imageName} Do
Q
```

### Multi-Page Rendering (`PdfRenderer.php`)

The renderer has been extended to support documents with multiple pages:

**`normalizePages()` method**:
- Normalizes the template format, supporting both v2 format (`pages[]`) and legacy format (`page` + separate `elements`)
- For legacy format, creates a single `pages` array with one page and assigns `pageId` to elements
- Ensures backward compatibility with templates created before multi-page support

**`render()` method**:
- Iterates over each page in the `pages` array
- For each page:
  1. Creates a new `PdfPage` with the dimensions specified in `page.settings`
  2. Filters elements belonging to that page (`element.pageId === page.id`)
  3. Renders header and footer if configured
  4. Renders elements in the correct order (z-order)
  5. Renders elements with `repeatOnAllPages: true` on every page
  6. Adds the page to the `PdfDocument`

**Elements repeated on all pages** (`repeatOnAllPages`):
- Elements with `repeatOnAllPages: true` are rendered on every page of the document
- Supported: stamp, watermark, text, image, rectangle, line, ellipse, divider
- Repeated elements maintain the same coordinates (x, y) on every page
- Support `showIf` and `styleIf` like normal elements
- Useful for stamps, watermarks, logos or other elements that must appear on every sheet

**Multi-page coordinate management**:
- Each page has its own dimensions, stored in `PdfDocument::$pageDimensions`
- The renderer uses the current page's dimensions for Y coordinate calculation
- Y conversion: `y_pdf = pageHeight_pt - y_mm × MM_TO_PT - height_mm × MM_TO_PT`

### TemplateLoader (`TemplateLoader.php`)

The template loader supports both formats:

**v2 format (multi-page)**:
```json
{
  "version": 2,
  "pages": [
    { "id": "page-uuid", "name": "Page 1", "settings": { "width": 210, "height": 297 } }
  ],
  "elements": [
    { "id": "...", "pageId": "page-uuid", "type": "text", "x": 10, "y": 10, "width": 100, "height": 20 }
  ]
}
```

**Legacy format (backward compatibility)**:
```json
{
  "page": { "width": 210, "height": 297 },
  "elements": [
    { "id": "...", "type": "text", "x": 10, "y": 10, "width": 100, "height": 20 }
  ]
}
```

- Validation accepts both formats
- Legacy format is automatically converted to v2 by the renderer's `normalizePages()`
- Legacy templates continue to work without changes

---

## Multi-Page System

### Architecture

The system supports documents with multiple pages, each with independent dimensions and settings. Elements are associated with pages via the `pageId` field.

**Data flow**:
```
Template JSON (v2)
       │
       ▼
TemplateLoader.php → normalizes → PdfRenderer::render()
                                        │
                                        ▼
                              Iterates over pages[]
                              For each page:
                                1. Creates PdfPage(width, height)
                                2. Filters elements by pageId
                                3. Renders header/footer
                                4. Renders elements
                                        │
                                        ▼
                              PdfDocument (multi-page)
                                        │
                                        ▼
                              Final PDF file
```

### v2 JSON Format

```json
{
  "version": 2,
  "pages": [
    {
      "id": "550e8400-e29b-41d4-a716-446655440000",
      "name": "Page 1",
      "settings": {
        "width": 210,
        "height": 297,
        "marginTop": 10,
        "marginBottom": 10,
        "marginLeft": 10,
        "marginRight": 10,
        "headerHeight": 20,
        "footerHeight": 20
      }
    },
    {
      "id": "550e8400-e29b-41d4-a716-446655440001",
      "name": "Page 2",
      "settings": {
        "width": 210,
        "height": 297,
        "marginTop": 10,
        "marginBottom": 10,
        "marginLeft": 10,
        "marginRight": 10,
        "headerHeight": 20,
        "footerHeight": 20
      }
    }
  ],
  "elements": [
    {
      "id": "elem-uuid-1",
      "pageId": "550e8400-e29b-41d4-a716-446655440000",
      "type": "text",
      "x": 10,
      "y": 20,
      "width": 100,
      "height": 15,
      "content": "Text on page 1"
    },
    {
      "id": "elem-uuid-2",
      "pageId": "550e8400-e29b-41d4-a716-446655440001",
      "type": "text",
      "x": 10,
      "y": 20,
      "width": 100,
      "height": 15,
      "content": "Text on page 2"
    }
  ]
}
```

### Backward Compatibility

The system is fully backward-compatible:

1. **Legacy format**: the `TemplateLoader` accepts templates with `page` (single object) + `elements` (without `pageId`)
2. **Automatic conversion**: `PdfRenderer::normalizePages()` converts legacy format to v2 during rendering
3. **Editor**: `migrateLegacyDocument()` in the store automatically converts legacy documents to the multi-page format
4. **No breakage**: existing templates continue to work without changes

### Multi-Page Implementation Notes

1. **UUID per page**: each page has a UUID generated by `crypto.randomUUID()` for unique identification
2. **Automatic assignment**: when an element is added, `pageId` is automatically assigned from the active page
3. **Page movement**: `moveElementToNextPage()` creates a new page if the next one doesn't exist
4. **Page deletion**: when a page is deleted, all its elements are removed
5. **Duplication**: page duplication creates deep copies of elements with new UUIDs
6. **Page order**: the order of pages in the `pages` array determines the order in the final PDF
7. **Independent dimensions**: each page can have different dimensions (e.g. A4 and A3 in the same document)

---

## Code Conventions

### TypeScript (Frontend)
- No comments in the code (unless explicitly requested)
- camelCase names for variables/functions
- PascalCase names for components and interfaces
- `RGB` type as `[number, number, number]` (tuple)
- All interfaces in `types/index.ts`

### PHP (Backend)
- PSR-4 namespace: `EngiPDF\`
- Autoload via Composer
- PSR-12 coding standard
- No external library dependencies for PDF generation

### Windows
- Shell: PowerShell (no `mkdir -p`)
- Path: backslash separator
- Development server: Laragon

### CSS Variables (Themes)

The editor supports light/dark themes via CSS variables defined in `editor/src/styles/themes.css`. All UI colors used in components must reference CSS variables, not hardcoded values. Mapping of main variables:

| Variable | Usage |
|---|---|
| `--text-primary` | Primary text |
| `--text-secondary` | Secondary text (e.g. dashed borders `#ccc`) |
| `--text-tertiary` | Muted text (labels, disabled text: `#666`, `#888`, `#999`) |
| `--bg-accent-subtle` | Accent selection background (e.g. `rgba(74,144,217,0.05)`) |
| `--bg-hover` | Hover/child background (e.g. `rgba(255,255,255,0.1)`) |
| `--bg-inset` | Inset background (e.g. table labels `rgba(0,0,0,0.3)`) |
| `--bg-danger` | Danger/error color (e.g. page break `#c00`) |
| `--border-subtle` | Subtle borders (e.g. spacer borders `rgba(128,128,128,0.3)`) |
| `--canvas-grid` | Canvas grid pattern (e.g. `rgba(128,128,128,0.05)`) |
| `--selection-color` | Selection border (e.g. `rgba(74,144,217,0.5)`) |

**Rule**: never use hardcoded colors in Vue components. Always use CSS variables to ensure compatibility with light/dark themes.

---

## Implementation Notes

### Rulers - Design Choices

1. **Inline implementation**: rulers are SVG directly in `EditorCanvas.vue`, not separate components
2. **Vertical alignment**: the vertical ruler has `margin-top: 20px` to start from the page beginning
3. **Page height**: the vertical ruler has height `pageHeightPx`, equal to the page
4. **Computed calculation**: ticks are recalculated only on page dimension changes
5. **SVG rendering**: ticks are SVG lines, not DOM elements, for optimal performance
6. **Synchronization**: pan and zoom apply naturally thanks to the DOM structure

### Auto-sizing of Elements

For text and lists, initial dimensions are calculated via Canvas API (`measureContent.ts`):
1. Creates an offscreen canvas
2. Sets the font with the desired properties
3. Measures the text with `measureText()` or the bounding box of the list
4. Returns width and height in mm

### Snap to Grid

The snap system rounds coordinates to the fixed 1 mm grid:
```typescript
function snapToGrid(value: number, gridSize: number): number {
  return Math.round(value / gridSize) * gridSize
}
```

### TTF Font Embedding

Custom TTF fonts are embedded in the PDF with:
1. Extraction of raw data from the TTF file
2. FlateDecode compression
3. Generation of a PDF font descriptor
4. Glyph mapping via Widths array
5. Addition to the page font dictionary

---

## Useful Commands

### Frontend
```bash
cd editor
npm install            # Install dependencies
npm run dev            # Dev server on http://localhost:5173
npm run build          # Production build
npx vue-tsc --noEmit   # Type check
```

### Backend
```bash
cd engine
composer install               # PSR-4 Autoload
php test_hello.php             # Basic PDF test
php test_template.php          # Invoice template test
php test_fonts.php             # Font rendering test
php test_font_resolve.php      # Font alias resolution test
php test_lista.php             # List rendering test
php test_lista_annidata.php    # Nested list test
```

---

## Programmability and Conditional Logic

### ShowIf — Conditional Visibility

**Frontend**: `evaluateShowIf()` in `conditionHelpers.ts` evaluates `showIf` rules on each element. `ElementWrapper.vue` uses `isVisible` to hide elements that don't satisfy the condition. The result is reactive thanks to `sampleData` in the store.

**Backend**: `PdfRenderer::evaluateCondition()` evaluates the same rules before rendering each element. If `showIf` fails, the element is skipped.

**Data type**:
```typescript
interface ConditionalRule {
  field: string
  op: 'eq' | 'neq' | 'gt' | 'lt' | 'gte' | 'lte' | 'empty' | 'notempty' | 'contains'
  value?: string
}
```

### StyleIf — Conditional Style

**Frontend**: `evaluateStyleIf()` in `conditionHelpers.ts` returns an object with the properties to override. `ElementWrapper.vue` merges `wrapperStyle` with `conditionalStyle`.

**Backend**: `PdfRenderer::applyStyleOverrides()` applies overrides to the `$el` frame before rendering. The `fill` field is mapped to `style.color` for text.

```typescript
interface StyleRule extends ConditionalRule {
  then: Record<string, unknown>
}
```

### DataRepeat — Data Repetition

`DataRepeatElement` is a container that iterates over an array in `sampleData` and replicates its children. In PHP, `PdfRenderer` handles the `dataRepeat` type with similar logic.

**Frontend**: `DataRepeatElement.vue` uses `resolveChild()` to replace `{{ item.field }}` with data from each iteration. `ElementWrapper.vue` includes the component.

**Parameters**:
- `repeatField`: key of the array in the data
- `direction`: `vertical` | `horizontal`
- `spacing`: distance between copies (px)
- `children`: array of template elements

### PlaceholderResolver — Extended Filters

The resolver supports chained filters with `|` and array access with `[index]` syntax:

```
{{ customer.name | uppercase | truncate:20 }}
{{ items[0].name }}
{{ amounts | sum | currency }}
```

**Supported filters**: `currency`, `date`, `number`, `uppercase`, `lowercase`, `capitalize`, `trim`, `truncate`, `default`, `len`, `if`, `sum`, `eq`, `neq`, `gt`, `lt`, `contains`, `empty`, `notempty`.

**Chain**: `resolveRaw()` returns `mixed`, `resolve()` applies `strval()` for text output compatibility.

### sampleData

The `sampleData` field in the JSON document contains sample data used by the editor for preview and condition evaluation:

```json
{
  "sampleData": {
    "customer": { "name": "Mario Rossi" },
    "items": [{ "name": "Laptop", "price": 899 }]
  }
}
```

In PHP, `$data` is passed to the PlaceholderResolver and to conditional evaluation methods.

### Field Options — Smart Dropdown

`fieldOptions.ts` provides `flattenSampleData()` which converts nested sampleData into a flat list of options for the PropertyPanel dropdowns:

```
{ path: "customer.name", label: "customer.name", value: "Mario Rossi" }
{ path: "items[0].name", label: "items[0].name", value: "Laptop" }
```

The function recursively handles nested objects, arrays with indices, and primitives. The PropertyPanel uses it to populate `showIf.field` and `styleIf[].field` selects, with a "Customize..." option for free input.

### Checklist Component

`ChecklistElement` renders a list with checkboxes (□ ☑). Each item has `{ text: string, checked: boolean }`.

**Frontend**: `ChecklistElement.vue` renders items vertically with an SVG box (□ or ☑) + text. The `text-decoration: line-through` and `opacity: 0.5` styles are applied to checked items. The PropertyPanel provides an editor to add/remove items, toggle checkboxes, and adjust size/colors/gap.

**Backend**: `PdfRenderer::renderChecklist()` iterates items and for each calls:
1. `PdfPage::checkbox($x, $y, $size, $checked, $color)` — draws □ (rectangle) or ☑ (rectangle + check with lines)
2. `PdfPage::text(...)` — text next to the checkbox

**Data model**:
```typescript
interface ChecklistElement extends BaseElement {
  type: 'checklist'
  items: { text: string; checked: boolean }[]
  size: number
  color: RGB
  checkedColor: RGB
  gap: number
}
```

### Radio Component

`RadioElement` renders a list with radio buttons (○ ●). Only one option can be selected at a time. Each item has `{ text: string, selected: boolean }`.

**Frontend**: `RadioElement.vue` renders items vertically with an SVG circle (○ or ●) + text. The selected circle has an inner dot. The PropertyPanel provides an editor to add/remove options, and adjust size/colors/gap.

**Backend**: `PdfRenderer::renderRadio()` iterates items and for each calls:
1. `PdfPage::radio($x, $y, $size, $selected, $color)` — draws ○ (empty circle) or ● (circle with dot)
2. `PdfPage::text(...)` — text next to the radio button

**Data model**:
```typescript
interface RadioElement extends BaseElement {
  type: 'radio'
  items: { text: string; selected: boolean }[]
  size: number
  color: RGB
  selectedColor: RGB
  gap: number
}
```

---

## Complete Component Documentation

For detailed documentation of each component (properties, JSON+PHP examples, programmability, categories), see the [`components-guide.md`](./components-guide.md) file.
