# AGENTS.md — engiPDF

## What this is

PHP library + Vue 3 web editor for template-based PDF generation.
- `src/` — pure PHP PDF engine (no external PDF libs)
- `editor/` — Vue 3 + TypeScript + Vite web editor
- `templates/` — example JSON templates
- `docs/` — user/component/technical guides

## Quick commands

```bash
# PHP (from repo root)
composer install
./vendor/bin/phpunit           # tests (tests/ dir may be empty)
php examples/test_template.php # generate a test PDF

# Editor
cd editor && npm install && npm run dev    # dev server → localhost:5173
cd editor && npm run build                 # production build → editor/dist/
```

No lint, formatter, or typecheck scripts are configured for PHP. No CI workflows exist.

## Architecture

- Namespace `EngiPDF\` → `src/` (PSR-4 autoload)
- `PdfRenderer::render(array $template, array $data): string` is the main entry point — returns raw PDF bytes
- `TemplateLoader::loadFromFile()` / `loadFromString()` — parses JSON templates
- `PlaceholderResolver` — resolves `{{ field.subfield }}` syntax in element text
- Coordinates: templates use **mm** (bottom-left origin for PDF, top-left for editor canvas). Conversion factor: `72 / 25.4` (pt per mm)
- Default page: A4 (595.28 × 841.89 pt = 210 × 297 mm)
- Template JSON requires either `version`+`page`+`elements` (legacy) or `pages`+`elements` (multi-page)

## Editor specifics

- Vue 3 + Pinia + vue-i18n, built with Vite
- Path alias: `@/` → `editor/src/`
- Type check: `cd editor && npx vue-tsc --noEmit`
- `editor/dist/` is committed; after rebuild, manually update asset hashes in `editor/dist/index.html` lines 10-11
- 28 components in `editor/src/elements/` — each mirrors a PHP renderer method in `PdfRenderer.php`

## Key gotchas

- **No tests/ directory yet** — PHPUnit is a dev dep but tests haven't been written
- **PNG images auto-converted to JPEG** (quality 90) during PDF generation — no raw PNG support in PDF output
- **`Layout/` directory is empty** — reserved but unused
- **PDF text alignment** uses `center`/`right`/`left` strings, not enums
- **Font weight normalization**: frontend values `"600"`–`"900"` are mapped to `"bold"` in the renderer
- **Table auto-pagination**: tables break across pages automatically; other content does not paginate
- **`repeatOnAllPages`** flag repeats an element on every page (stamps, watermarks only supported in repeat rendering)
