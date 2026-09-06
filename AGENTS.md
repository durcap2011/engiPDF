# AGENTS.md

## General Rules

If they do not already exist, create both functional documentation (guide.md) and technical documentation (technical_documentation.md).
I want both documents to be extremely detailed. The functional documentation must avoid technical details and explain the features in simple terms, focusing on how to use them. The technical documentation, on the other hand, should go into technical depth, also explaining the reasoning behind certain design and implementation choices.
At the end of every operation you perform, you must always update these two documentation files whenever necessary.

## Project Structure

Two-part system: Vue 3 web editor + pure PHP PDF generator. No external PDF libraries.

```
engiPDF/
├── editor/    # Vue 3 + TypeScript + Vite + Pinia
├── engine/    # PHP 8.2+ native PDF writer (PSR-4 autoload: EngiPDF\)
└── templates/ # JSON template files
```

## Commands

### Editor (Frontend)
```bash
cd editor
npm install
npm run dev          # http://localhost:5173
npm run build        # vue-tsc && vite build
npx vue-tsc --noEmit # Type check only
```

### Engine (PHP)
```bash
cd engine
composer install
php test_template.php   # Invoice template test
```

## Architecture

- **Contract**: JSON template defines elements (text, rectangle, line, list, image, table, ellipse, divider, signature, container, pageNumber, date, watermark, qrcode, spacer, stamp, quote, callout, codeBlock, progressBar, icon, barcode, chart, pageBreak, dataRepeat, checklist, radio) with mm coordinates
- **Multi-page**: Document contains `pages[]` array; each element has a `pageId` linking it to a page
- **Conversion**: Editor uses mm, engine converts to PDF points (`mm * 72 / 25.4`)
- **Coordinate origin**: Editor top-left, PDF bottom-left (flipped in renderer)
- **Fonts**: 14 Type1 fonts (Helvetica, Times, Courier + variants) + TTF embedding via FontManager
- **Font aliases**: arial/helv/sans-serif→helvetica, times-new-roman/serif/georgia/bookman→times, monospace/courier-new/mono→courier
- **Font key format**: PDF font objects use `family:weight:style` (e.g., `helvetica:bold:normal`)
- **Placeholder syntax**: `{{ var }}`, `{{ oggetto.proprieta }}`, filters: `currency`, `date:"d/m/Y"`, `number:N`, `uppercase`, `lowercase`, `capitalize`, `trim`, `truncate`, `default`, `if`, `sum`, `len`
- **Placeholder support**: text, list, table, quote, callout, checklist, radio, watermark, stamp, barcode, qrcode, codeBlock, signature, progressBar
- **Repeat on all pages**: `repeatOnAllPages: true` on BaseElement renders element on every page (stamp, watermark, text, image, rectangle, line, ellipse, divider)
- **List bullets**: circle, square, dash, diamond, arrow, number (rendered as PDF drawing primitives; number renders as circle)
- **Image support**: base64 data URL stored in template JSON, embedded in PDF

## Key Files

**Editor (TypeScript)**
- `editor/src/types/index.ts` — All TypeScript interfaces (Document, Page, Element, TextStyle, etc.)
- `editor/src/stores/editorStore.ts` — Pinia store with undo/redo (50 levels), element CRUD, multi-page state
- `editor/src/utils/measureContent.ts` — Canvas API text/list measurement for auto-sizing
- `editor/src/utils/getDefaultElement.ts` — Element factory (no `id` field—store generates UUID, takes `pageId` param)
- `editor/src/utils/bulletTypes.ts` — Bullet type definitions (circle, square, dash, diamond, arrow, number)
- `editor/src/utils/conditionHelpers.ts` — `evaluateShowIf()` / `evaluateStyleIf()` for conditional visibility and style
- `editor/src/utils/fieldOptions.ts` — `flattenSampleData()` for generating field dropdown options from sampleData

**Engine (PHP)**
- `engine/src/PdfWriter/PdfDocument.php` — PDF binary writer (Header→Body→Xref→Trailer)
- `engine/src/PdfWriter/PdfPage.php` — Drawing primitives (text, rectangle, line, circle, polygon, image)
- `engine/src/Font/FontManager.php` — Type1 resolution + TTF registration + PDF object allocation
- `engine/src/Renderer/PdfRenderer.php` — Template→PDF renderer (handles all element types)
- `engine/src/Template/PlaceholderResolver.php` — `{{ }}` syntax with filters
- `engine/src/Template/TemplateLoader.php` — Template file loader

**Documentation**
- `components-guide.md` — Complete guide to all 28 components (properties, examples, programmability)
- `template-component-guide.md` — Template format for creating component guides
- `guide.md` — User guide for the editor
- `technical_documentation.md` — Technical documentation for developers

## Conventions

- **Windows**: PowerShell (no `mkdir -p`), Laragon at `C:\laragon\www\engiPDF`
- **No comments** in code unless explicitly requested
- **Language**: Italian for user communication
- **Drag & drop**: Components inserted via drag from palette to canvas
- **Auto-size**: Text/lists measure content via Canvas API for initial dimensions
- **Double-click**: Inline editing for text and list elements
- **Resize handles**: 8 handles (4 corners + 4 edges) on selected elements
- **Grid snap**: Default 1mm, configurable
- **Undo/redo**: 50 levels, JSON serialization

## Gotchas

- `getDefaultElement()` must NOT include `id` field (store generates UUID)
- TextElement/ListElement emit `update` event for inline editing
- PDF font objects use `family:weight:style` key format
- List items support nested children via `items` array
- Image stored as base64 data URL in template JSON
- TTF fonts: register via `$template['fonts']` in JSON, engine embeds with FlateDecode
- Weight normalization: frontend "600"/"700"/"800"/"900" → engine "bold"
- `measureContent.ts` uses a FONT_MAP for Canvas API measurement; if adding new fonts, update both FontManager.php and this map
- `engine/src/Layout/` directory exists but is empty (placeholder for future layout features)
- `showIf`/`styleIf` on BaseElement are optional; evaluateShowIf/evaluateStyleIf in conditionHelpers.ts handle undefined gracefully
- `sampleData` lives in `document.sampleData`, not as a separate store ref
- List bullets: circle, square, dash, diamond, arrow, number (rendered as PDF drawing primitives; number renders as circle)
