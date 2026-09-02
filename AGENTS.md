# AGENTS.md

## Project Structure

Two-part system: Vue 3 editor + pure PHP PDF generator. No external PDF libraries.

```
engiPDF/
├── editor/    # Vue 3 + TypeScript + Vite + Pinia
├── engine/    # PHP 8.2+ native PDF writer
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
php test_hello.php    # Basic PDF test
php test_template.php # Invoice template test
php test_fonts.php    # Font rendering test
php test_lista.php    # List rendering test
```

## Architecture

- **Contract**: JSON template defines elements (text, rectangle, line, list, image) with mm coordinates
- **Conversion**: Editor uses mm, engine converts to PDF points (`mm * 72 / 25.4`)
- **Fonts**: 14 Type1 fonts (Helvetica, Times, Courier + variants) + TTF embedding
- **Font aliases**: arial→helvetica, sans-serif→helvetica, times-new-roman→times, monospace→courier
- **Coordinate origin**: Editor top-left, PDF bottom-left (flipped in renderer)

## Key Files

- `editor/src/types/index.ts` — All TypeScript interfaces
- `editor/src/stores/editorStore.ts` — Pinia store with undo/redo
- `editor/src/utils/measureContent.ts` — Auto-size text/lists
- `editor/src/utils/getDefaultElement.ts` — Element factory
- `engine/src/PdfWriter/PdfDocument.php` — PDF binary writer
- `engine/src/PdfWriter/PdfPage.php` — Drawing primitives
- `engine/src/Font/FontManager.php` — Font resolution + TTF
- `engine/src/Renderer/PdfRenderer.php` — Template→PDF renderer
- `engine/src/Template/PlaceholderResolver.php` — `{{ var }}` syntax

## Conventions

- **Windows**: PowerShell (no `mkdir -p`), Laragon at `C:\laragon\www\engiPDF`
- **No comments** in code unless explicitly requested
- **Language**: Italian for user communication
- **Drag & drop**: Components inserted via drag from palette to canvas
- **Auto-size**: Text/lists measure content via Canvas API for initial dimensions
- **Double-click**: Inline editing for text and list elements
- **Resize handles**: 8 handles (4 corners + 4 edges) on selected elements
- **Grid snap**: Default 1mm, configurable

## Gotchas

- `getDefaultElement()` must NOT include `id` field (store generates UUID)
- TextElement/ListElement emit `update` event for inline editing
- PDF font objects use `family:weight:style` key format
- List items support nested children via `items` array
- Image stored as base64 data URL in template JSON
