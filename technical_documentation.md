# engiPDF - Documentazione Tecnica

## Architettura Generale

engiPDF è un sistema bifasico composto da un editor web (Vue 3 + TypeScript + Vite + Pinia) e un generatore PDF PHP 8.2+ nativo (senza librerie esterne). I due sistemi comunicano tramite file JSON che rappresentano il template del documento.

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

## Frontend - Editor Vue 3

### Stack Tecnologico
- **Vue 3** (Composition API, `<script setup>`)
- **TypeScript** (type checking con `vue-tsc`)
- **Vite** (bundler, dev server su porta 5173)
- **Pinia** (state management con undo/redo)

### Layout della UI (`App.vue`)

Layout a griglia CSS a 3 pannelli:

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

Componenti del layout:
- `EditorToolbar` (in alto, altezza fissa ~48px)
- `ComponentPalette` (sinistra, larghezza fissa 200px)
- `EditorCanvas` (centro, flex: 1) — include i righelli
- `PropertyPanel` (destra, larghezza fissa 260px)

### Struttura del Canvas (`EditorCanvas.vue`)

Il canvas è organizzato in una griglia CSS 2×2:

```
┌──────────────────────────────────┐
│ RulerCorner │ HorizontalRuler    │  ← riga 1 (24px)
├─────────────┼────────────────────┤
│ VerticalR.  │    EditorCanvas    │  ← riga 2 (flex: 1)
└─────────────┴────────────────────┘
```

**Proprietà reattive**:
- `panX`, `panY`: offset di spostamento in pixel
- `zoom`: fattore di scala (0.2 – 5.0)
- `isPanning`: flag per trascinamento attivo

**Trasformazione CSS**: il contenuto del canvas viene trasformato con:
```css
transform: translate(panXpx, panYpx) scale(zoom);
transformOrigin: 0 0;
```

### Sistema di Coordinate

L'editor opera in millimetri (`MM_TO_PX = 96 / 25.4 ≈ 3.7795`):
- Tutte le coordinate degli elementi sono in mm
- La conversione in pixel avviene al momento del rendering CSS
- Il PDF usa punti (`MM_TO_PT = 72 / 25.4 ≈ 2.8346`)
- L'origine delle coordinate è l'angolo in alto a sinistra del foglio (consistente tra editor e renderizzazione, ma invertita rispetto al PDF che ha origine in basso a sinistra)

### Store Pinia (`editorStore.ts`)

**State principale**:
```typescript
interface EditorState {
  document: Document
  selectedId: string | null
  gridSize: number       // default: 1 mm
  zoom: number
}
```

**History (Undo/Redo)**:
- Array di snapshot JSON serializzati
- Max 50 livelli
- `saveHistory()` viene chiamata prima di ogni modifica
- Serializzazione completa dello stato ad ogni snapshot

**CRUD Operations**:
- `addElement(type)`: crea elemento con UUID via `crypto.randomUUID()`
- `updateElement(id, patch)`: aggiorna proprietà parziali
- `removeElement(id)`: elimina elemento
- `duplicateElement(id)`: crea copia con offset di 5mm
- `moveElementUp/Down(id)`: riordina nello stack

### Righelli (inline in `EditorCanvas.vue`)

I righelli sono implementati come SVG inline direttamente nel template di `EditorCanvas.vue`, non come componenti separati.

**Layout CSS**:
- Struttura `.ruler-and-page` → `.page-row` (flex orizzontale)
- `.ruler-vertical`: 20px larghezza, `margin-top: 20px` per iniziare dall'inizio della pagina (sotto il righello orizzontale), altezza `pageHeightPx`
- `.page-column`: contiene righello orizzontale (20px altezza) e `PageArtboard`

**Calcolo tacche**:
- `horizontalMarks`: iterazione da 0 a `page.width` (mm), tacche ogni 5mm, maggiori ogni 10mm
- `verticalMarks`: iterazione da 0 a `page.height` (mm), tacche ogni 5mm, maggiori ogni 10mm
- Ogni mark: `{ px: number, mm: number, major: boolean }`

**Rendering SVG**:
- Righello verticale: SVG 20 × pageHeightPx, tacche con x1 variabile (0 maggiori, 12 minori), x2=19
- Righello orizzontale: SVG pageWidthPx × 20, tacche con y1 variabile (0 maggiori, 12 minori), y2=19
- I numeri (solo maggiori) usano `writing-mode: vertical-rl` con `transform: rotate(180deg)` per il verticale

**Sincronizzazione pan/zoom**:
- I righelli sono figli di `.canvas-content` che applica la trasformazione CSS
- Pan e zoom si applicano naturalmente senza calcoli aggiuntivi

### Sistema di Font

**Mappatura Frontend** (`measureContent.ts`):
- `FONT_MAP` associare i nomi CSS ai font family per la misurazione via Canvas API
- Utilizzato per auto-sizing di testo e liste

**Alias** (definiti in `FontManager.php`):
```
arial/helv/sans-serif → helvetica
times-new-roman/serif/georgia/bookman → times
monospace/courier-new/mono → courier
```

**Chiave font nel PDF**: `family:weight:style` (es. `helvetica:bold:normal`)

**Encoding caratteri** (`PdfPage::encodePdfString()`):
- I testi UTF-8 vengono convertiti in ISO-8859-1 (Latin-1) per le font Type1 standard
- Le font Type1 dichiarano `/Encoding /WinAnsiEncoding` nel PDF per mappare correttamente i byte ai glifi
- Supporta caratteri accentati europei: à è é ì ò ù ñ ü ä ö ß etc.
- I caratteri parentesi `(`, `)` e backslash `\` vengono escaped correttamente
- Per caratteri fuori dal range Latin-1 serve una font TTF personalizzata

### Placeholder System (`PlaceholderResolver.php`)

Sintassi: `{{ variabile }}` o `{{ oggetto.proprieta }}`

Filtri disponibili:
- `currency`: formattazione valuta
- `date:"d/m/Y"`: formattazione data
- `number:N`: N cifre decimali

### Element Factory (`getDefaultElement.ts`)

Ogni tipo di elemento ha una factory che restituisce un oggetto con valori di default. **Importante**: la factory NON include il campo `id` — lo store lo genera automaticamente via `crypto.randomUUID()`.

### Tabella (`TableElement`)

**Interfaccia TypeScript**:
- `name: string` — nome tabella, usato come placeholder per i dati dinamici (`{{ nome }}`)
- `columns: TableColumn[]` — colonne con `width` (peso relativo), `header` (testo), `headerStyle` (TextStyle completo per intestazione)
- `rows: TableRow[]` — righe template (di default 1 riga vuota), ogni cella è un `TableCell` con `text` e `style` (override parziale)
- `repeatHeader: boolean` — ripete l'intestazione su ogni nuova pagina
- `headerStyle: TextStyle` — stile di default per intestazioni
- `cellStyle: TextStyle` — stile di default per celle dati
- `borderColor / borderWidth` — proprietà bordi

**Rendering PDF** (`PdfRenderer::renderTable()`):
1. Se `name` è impostato e `$data[$name]` è un array, le righe vengono popolate dai dati esterni
2. Ogni header usa il suo `headerStyle` individuale (font, peso, allineamento, colore, etc.)
3. Ogni cella dati merge lo stile di default con l'override per-cella (`cell.style`)
4. Supporta placeholder `{{ }}` in intestazioni e celle
5. Le larghezze colonne sono proporzionali al peso relativo
6. La tabella viene automaticamente spezzata su più pagine quando supera l'area utilizzabile
7. L'intestazione viene ripetuta su ogni nuova pagina (se `repeatHeader` è true)

---

## Backend - Engine PHP

### Struttura

```
engine/
├── src/
│   ├── PdfWriter/
│   │   ├── PdfDocument.php      # Scrittore binario PDF (Header→Body→Xref→Trailer)
│   │   └── PdfPage.php          # Primitive di disegno (text, rect, line, circle, polygon, image)
│   ├── Font/
│   │   └── FontManager.php      # Risoluzione Type1 + registrazione TTF + allocazione oggetti PDF
│   ├── Renderer/
│   │   └── PdfRenderer.php      # Renderer template→PDF (gestisce tutti i tipi di elemento)
│   └── Template/
│       ├── PlaceholderResolver.php  # Sintassi {{ }} con filtri
│       └── TemplateLoader.php       # Caricatore file template
├── composer.json                # Autoload PSR-4: EngiPDF\
└── test_*.php                   # Script di test
```

### Generazione PDF (`PdfDocument.php`)

Il PDF viene scritto byte-per-byte senza librerie esterne:

1. **Header**: `%PDF-1.4`
2. **Body**: oggetti PDF (pagine, font, stream di contenuto)
3. **Cross-Reference Table**: offset di ogni oggetto
4. **Trailer**: riferimento alla root e al conteggio oggetti

### Coordinate PDF vs Editor

| Aspetto | Editor | PDF |
|---|---|---|
| Origine | Alto-sinistra | Basso-sinistra |
| Unità | mm | punti (1/72 pollici) |
| Conversione | `px = mm × 96/25.4` | `pt = mm × 72/25.4` |
| Flusso Y | crescente verso il basso | crescente verso l'alto |

Il `PdfRenderer` converte le coordinate Y: `y_pdf = pageHeight_pt - y_mm × MM_TO_PT - height_mm × MM_TO_PT`

### Area Utilizzabile e Paginazione

Il `PdfRenderer` calcola un'area utilizzabile per pagina basata su header e footer:

```php
$contentTop = pageHeight - headerHeight;    // Limite superiore area contenuto
$contentBottom = footerHeight;              // Limite inferiore area contenuto
```

- **Header**: area fissa in cima a ogni pagina (altezza specificata in `page.headerHeight`)
- **Footer**: area fissa in fondo a ogni pagina (altezza specificata in `page.footerHeight`)
- **Area contenuto**: spazio tra header e footer dove vengono posizionati gli elementi
- **Paginazione**: quando un elemento (tabella) supera l'area disponibile, viene creata una nuova pagina
- **Ripetizione intestazione**: le tabelle con `repeatHeader: true` ripetono l'intestazione su ogni nuova pagina

### Font Manager (`FontManager.php`)

Gestisce 14 font Type1 integrati + font TTF personalizzati:

**Type1 (embedded)**:
- Helvetica (normal, bold, italic, bolditalic)
- Times (normal, bold, italic, bolditalic)
- Courier (normal, bold, italic, bolditalic)

**TTF (FlateDecode)**:
- Registrati via template JSON: `{"fonts": {"MyFont": "path/to/font.ttf"}}`
- Embedding con compressione FlateDecode
- Generazione tabella width basata su misurazione

### Rendering Immagini

Le immagini vengono embeddate nel PDF come XObject Image:

1. **Data URL parsing**: estrazione formato e dati grezzi dal data URL base64 (`data:image/TYPE;base64,...`)
2. **Formato supportato**:
   - **JPEG/JPG**: dati binari direttamente embeddati con filtro `/DCTDecode`
   - **PNG**: conversione in JPEG tramite GD library (`imagecreatefrompng` → `imagejpeg` con qualità 90%)
3. **Registrazione**: i dati JPEG vengono registrati nel `PdfDocument` tramite `registerImage()`
4. **XObject**: durante il render del PDF, viene creato un oggetto XObject Image con `/Filter /DCTDecode`
5. **Risorsa pagina**: l'XObject viene aggiunto alla risorsa `/XObject` di ogni pagina
6. **Rendering**: `$page->image($x, $y, $w, $h, $imageName)` disegna l'immagine con `cm` matrix + `Do`

**Flusso PDF generato**:
```
q
{width} 0 0 {height} {x} {y} cm
/{imageName} Do
Q
```

---

## Convenzioni di Codice

### TypeScript (Frontend)
- Nessun commento nel codice (salvo esplicita richiesta)
- Nomi in camelCase per variabili/funzioni
- Nomi in PascalCase per componenti e interfacce
- Tipo `RGB` come `[number, number, number]` (tuple)
- Tutte le interfacce in `types/index.ts`

### PHP (Backend)
- Namespace PSR-4: `EngiPDF\`
- Autoload via Composer
- Coding standard PSR-12
- Nessuna dipendenza da librerie esterne per la generazione PDF

### Windows
- Shell: PowerShell (no `mkdir -p`)
- Path: separatore backslash
- Server di sviluppo: Laragon

---

## Note di Implementazione

### Righelli - Scelte Progettuali

1. **Implementazione inline**: i righelli sono SVG direttamente in `EditorCanvas.vue`, non componenti separati
2. **Allineamento verticale**: il righello verticale ha `margin-top: 20px` per iniziare dall'inizio della pagina
3. **Altezza pagina**: il righello verticale ha altezza `pageHeightPx`, uguale alla pagina
4. **Calcolo computed**: le tacche vengono ricalcolate solo al cambio di dimensioni pagina
5. **SVG rendering**: le tacche sono linee SVG, non elementi DOM, per performance ottimali
6. **Sincronizzazione**: pan e zoom si applicano naturalmente grazie alla struttura DOM

### Auto-sizing degli Elementi

Per testo e liste, le dimensioni iniziali vengono calcolate tramite Canvas API (`measureContent.ts`):
1. Crea un canvas offscreen
2. Imposta il font con le proprietà desiderate
3. Misura il testo con `measureText()` o il bounding box della lista
4. Restituisce width eheight in mm

### Snap to Grid

Il sistema di snap arrotonda le coordinate alla griglia più vicina:
```typescript
function snapToGrid(value: number, gridSize: number): number {
  return Math.round(value / gridSize) * gridSize
}
```

### TTF Font Embedding

I font TTF personalizzati vengono incorporati nel PDF con:
1. Estrazione dei dati grezzi dal file TTF
2.Compressione FlateDecode
3. Generazione di un font descriptor PDF
4. Mappatura dei glifi tramite Widths array
5. Aggiunta alla-font dictionary della pagina

---

## Comandi Utili

### Frontend
```bash
cd editor
npm install            # Installa dipendenze
npm run dev            # Dev server su http://localhost:5173
npm run build          # Build di produzione
npx vue-tsc --noEmit   # Type check
```

### Backend
```bash
cd engine
composer install               # Autoload PSR-4
php test_hello.php             # Test base PDF
php test_template.php          # Test template fattura
php test_fonts.php             # Test rendering font
php test_font_resolve.php      # Test risoluzione alias font
php test_lista.php             # Test rendering liste
php test_lista_annidata.php    # Test liste annidate
```
