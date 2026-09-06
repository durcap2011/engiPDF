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
- `LayersPanel` (sinistra, larghezza fissa 220px)
- `ComponentPalette` (sinistra, larghezza fissa 160px) — palette con accordion per categorie
- `EditorCanvas` (centro, flex: 1) — include i righelli
- `PropertyPanel` (destra, larghezza fissa 260px)
- `SearchReplace` (overlay in alto a destra)

### Struttura del Canvas (`EditorCanvas.vue`)

Il canvas è organizzato in una griglia CSS 2×2:

```
┌──────────────────────────────────┐
│ RulerCorner │ HorizontalRuler    │  ← riga 1 (24px)
├─────────────┼────────────────────┤
│ VerticalR.  │    EditorCanvas    │  ← riga 2 (flex: 1)
└─────────────┴────────────────────┘
```

**Rendering Multi-Pagina**:
- Il canvas rende più `PageArtboard` in colonna con spaziatura `PAGE_GAP = 40px`
- Ogni artboard è indipendente, con le proprie dimensioni e righelli
- Centraggio iniziale sulla prima pagina al mount del componente
- `panY` viene calcolato per posizionare la pagina attiva al centro dello viewport

**Proprietà reattive**:
- `panX`, `panY`: offset di spostamento in pixel
- `zoom`: fattore di scala (0.2 – 5.0)
- `isPanning`: flag per trascinamento attivo

**Trasformazione CSS**: il contenuto del canvas viene trasformato con:
```css
transform: translate(panXpx, panYpx) scale(zoom);
transformOrigin: 0 0;
```

### PageArtboard (`PageArtboard.vue`)

Ogni pagina viene resa come un componente `PageArtboard`:

**Props**:
- `page: Page` — oggetto pagina con id, name e settings
- `isActive: boolean` — indica se è la pagina attualmente selezionata

**Comportamento**:
- Filtra gli elementi dal document per `element.pageId === page.id`
- Emette l'evento `selectPage` quando l'utente clicca sull'artboard
- Mostra un'etichetta con il numero progressivo della pagina in alto a sinistra
- Evidenzia il bordo quando la pagina è attiva

**Layout**:
- Ogni artboard ha dimensioni fisse in base a `page.settings.width` e `page.settings.height`
- Contiene il proprio set di righelli orizzontale e verticale
- Gli elementi vengono renderizzati al suo interno con le stesse logiche di drag, resize e selezione

### Pannello Proprietà (`PropertyPanel.vue`)

Il pannello proprietà è stato aggiornato per il sistema multi-pagina:

**Riferimenti pagina**:
- Tutti i riferimenti a `store.document.page` sono stati cambiati in `store.activePage.settings`
- Il metodo `updatePage()` è stato rinominato in `updatePageSettings(pageId, settings)`
- Le modifiche alle impostazioni pagina si applicano solo alla pagina attiva

**Contenuto**:
- Proprietà elemento (posizione, dimensioni, stile)
- Proprietà pagina (dimensioni, margini, header, footer) — solo per la pagina attiva
- La selezione della pagina nel toolbar aggiorna automaticamente il pannello

### Toolbar Editor (`EditorToolbar.vue`)

La toolbar include controlli specifici per la gestione pagine e le nuove funzionalità:

**Controlli navigazione**:
- Pulsanti ◀/▶ per navigare tra le pagine
- Contatore pagine (es. "2 / 5")
- La navigazione ciclica: dalla prima si va all'ultima, dall'ultima alla prima

**Azioni pagina**:
- Aggiungi pagina: crea una nuova pagina in fondo al documento
- Duplica pagina: duplica la pagina attiva con tutti i suoi elementi
- Elimina pagina: rimuove la pagina attiva (disabilitato se è l'unica pagina)

**Multi-selezione**:
- Pulsanti di allineamento (6 direzioni): visibili con 2+ elementi selezionati
- Pulsanti di distribuzione (2 assi): visibili con 3+ elementi selezionati
- Pulsante raggruppa: visibile con 2+ elementi selezionati
- Pulsante separa: visibile con 1 gruppo selezionato

### Pannello Livelli (`LayersPanel.vue`)

Il pannello livelli mostra tutti gli elementi della pagina corrente in ordine di z-order:

**Funzionalità**:
- **Selezione**: clicca su un elemento nella lista per selezionarlo
- **Visibilità**: icona occhio per nascondere/mostrare un elemento
- **Blocco**: icona lucchetto per bloccare/sbloccare un elemento
- Ordine inverso: gli elementi in primo piano sono in cima alla lista
- Evidenziazione dell'elemento selezionato

**Integrazione con editor**:
- Gli elementi nascosti sono visualizzati con opacità ridotta sull'canvas
- Gli elementi bloccati non possono essere selezionati o modificati
- La visibilità e il blocco sono persistiti nello state Pinia

### Barra Ricerca e Sostituzione (`SearchReplace.vue`)

Componente overlay per ricerca e sostituzione testo:

**Funzionalità**:
- Ricerca case-insensitive in tutti gli elementi di testo e liste
- Navigazione tra i risultati con ◀/▶ o Enter/Shift+Enter
- Sostituzione singola o sostituzione di tutti i risultati
- Chiusura con Esc o pulsante ✕
- Contatore risultati (es. "3/15")

**Integrazione**:
- `Ctrl+F` apre/chiude la barra
- Gli elementi trovati vengono selezionati automaticamente
- La navigazione cambia la pagina corrente se necessario

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
  selectedIds: string[]        // Array di ID selezionati (multi-selezione)
  selectedPageId: string | null   // ID della pagina attiva
  gridSize: 1            // Costante fisso a 1 mm (non modificabile)
  zoom: number
  clipboard: Element[]   // Elementi copiati per incolla
  searchText: string     // Testo di ricerca
  replaceText: string    // Testo di sostituzione
  searchVisible: boolean // Visibilità barra ricerca
  searchMatchIndex: number // Indice del match corrente
  hiddenIds: Set<string> // ID degli elementi nascosti
  lockedIds: Set<string> // ID degli elementi bloccati
}
```

**Computed properties**:
- `activePage`: ritorna l'oggetto `Page` corrente in base a `selectedPageId`
- `currentPageElements`: filtra gli elementi del document per `pageId === selectedPageId`

**History (Undo/Redo)**:
- Array di snapshot JSON serializzati
- Max 50 livelli
- `saveHistory()` viene chiamata prima di ogni modifica
- Serializzazione completa dello stato ad ogni snapshot

**CRUD Operations**:
- `addElement(type)`: crea elemento con UUID via `crypto.randomUUID()` e assegna `pageId` della pagina attiva
- `updateElement(id, patch)`: aggiorna proprietà parziali
- `removeElement(id)`: elimina elemento (supporta multi-selezione)
- `duplicateElement(id)`: crea copia con offset di 5mm (supporta multi-selezione)
- `moveElementUp/Down(id)`: riordina nello stack

**Multi-selezione**:
- `selectElement(id)`: seleziona un solo elemento
- `toggleSelection(id)`: aggiunge/rimuove un elemento dalla selezione
- `selectAll()`: seleziona tutti gli elementi della pagina corrente
- `clearSelection()`: svuota la selezione
- `moveSelectedElements(dx, dy)`: sposta tutti gli elementi selezionati
- `resizeSelectedElements(dw, dh)`: ridimensiona tutti gli elementi selezionati

**Allineamento e Distribuzione**:
- `alignElements(direction)`: allinea gli elementi selezionati (left, center, right, top, middle, bottom)
- `distributeElements(axis)`: distribuisce gli elementi selezionati (horizontal, vertical)

**Raggruppamento**:
- `groupElements(ids)`: crea un GroupElement contenente gli elementi selezionati
- `ungroupElement(groupId)`: separa un gruppo negli elementi originali

**Copia e Incolla**:
- `copyElements()`: copia gli elementi selezionati nel clipboard
- `pasteElements(targetPageId?)`: incolla gli elementi dal clipboard

**Ricerca e Sostituzione**:
- `findNext()`: cerca il prossimo match
- `findPrevious()`: cerca il match precedente
- `replaceCurrent()`: sostituisce il match corrente
- `replaceAll()`: sostituisce tutti i match
- `getSearchMatches()`: ritorna tutti gli elementi che corrispondono alla ricerca

**Visibilità e Blocco**:
- `toggleVisibility(id)`: nasconde/mostra un elemento
- `toggleLock(id)`: blocca/sblocca un elemento
- `isHidden(id)`: verifica se un elemento è nascosto
- `isLocked(id)`: verifica se un elemento è bloccato

**Gestione Pagine**:
- `addPage()`: aggiunge una nuova pagina in coda con impostazioni di default
- `removePage(pageId)`: elimina una pagina e tutti i suoi elementi; se è l'ultima pagina, non permette l'eliminazione
- `duplicatePage(pageId)`: duplica una pagina con tutti i suoi elementi, assegnando nuovi UUID
- `selectPage(pageId)`: cambia la pagina attiva
- `updatePageSettings(pageId, settings)`: aggiorna le impostazioni di una pagina (dimensioni, margini, header/footer)
- `renamePage(pageId, name)`: rinomina una pagina
- `copyHeaderFooterFromPage(sourcePageId, targetPageId, zone)`: copia gli elementi header/footer da una pagina sorgente a una pagina target

**Backward Compatibility**:
- `migrateLegacyDocument()`: converte automaticamente il formato legacy (singola pagina con `page` + `elements` separati) nel formato multipagina v2 (`pages[]` + `elements[]` con `pageId`)

**Gestione Overflow**:
- `isElementOverflowing(elementId)`: verifica se un elemento eccede i confini della pagina
- `getOverflowingElements()`: ritorna tutti gli elementi che eccedono i confini della pagina attiva
- `moveElementToNextPage(elementId)`: sposta un elemento alla pagina successiva, creandola se necessario

### Types (`types/index.ts`)

Il sistema multi-pagina introduce nuove interfacce e modifica quelle esistenti:

**Nuovo tipo `Page`**:
```typescript
interface Page {
  id: string          // UUID generato da createDefaultPage()
  name: string        // nome visualizzato (es. "Pagina 1")
  settings: PageSettings  // dimensioni, margini, header/footer
}
```

**`PageSettings` aggiornato**:
```typescript
interface PageSettings {
  width: number
  height: number
  unit: 'mm' | 'pt' | 'px'
  margins: { top: number; right: number; bottom: number; left: number }
  headerHeight: number
  footerHeight: number
  headerSourcePageId?: string  // ID pagina sorgente per copiare header
  footerSourcePageId?: string  // ID pagina sorgente per copiare footer
}
```

**`Document` aggiornato**:
```typescript
interface Document {
  version: 2
  pages: Page[]       // Array di pagine (sostituisce page: PageSettings)
  elements: Element[] // Tutti gli elementi, con campo pageId
}
```

**`BaseElement` esteso**:
```typescript
interface BaseElement {
  id: string
  type: string
  x: number
  y: number
  width: number
  height: number
  pageId: string      // UUID della pagina di appartenenza
  // ... altre proprietà
}
```

**Nuovo tipo `GroupElement`**:
```typescript
interface GroupElement extends BaseElement {
  type: 'group'
  children: Element[]  // Elementi figli del gruppo (coordinate relative)
}
```

**`ElementType` aggiornato**:
```typescript
type ElementType = 'text' | 'image' | 'list' | 'rectangle' | 'line' | 'table' | 'group' | 'ellipse' | 'divider' | 'signature' | 'container' | 'pageNumber' | 'date' | 'watermark' | 'qrcode' | 'spacer' | 'stamp' | 'quote' | 'callout' | 'codeBlock' | 'progressBar' | 'icon' | 'barcode' | 'chart' | 'pageBreak'
```

**Nuove interfacce per i componenti (batch 2)**:
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

**Nuove interfacce per i componenti**:
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

**Costanti e factory**:
- `DEFAULT_PAGE_SETTINGS`: oggetto con le impostazioni di default per una pagina (era `DEFAULT_PAGE`)
- `createDefaultPage()`: crea un oggetto `Page` con UUID e nome incrementale

### ElementWrapper (`ElementWrapper.vue`)

Ogni elemento reso nel canvas è wrappato da `ElementWrapper`:

**Funzionalità overflow**:
- Quando un elemento eccede i confini della pagina, viene mostrato un indicatore visivo (bordi rossi tratteggiati)
- Viene mostrato un pulsante "Sposta alla pagina successiva" che invoca `moveElementToNextPage()`
- Il controllo overflow viene eseguito a ogni spostamento/redimensionamento dell'elemento

**Multi-selezione**:
- `Shift+Clicca` aggiunge/rimuove un elemento dalla selezione
- Trascinando un elemento selezionato, si spostano tutti gli elementi selezionati
- Resize multipla: ridimensionamento proporzionale di tutti gli elementi selezionati

**Visibilità e Blocco**:
- Gli elementi nascosti sono visualizzati con opacità ridotta (opacity: 0.3)
- Gli elementi bloccati non possono essere selezionati o modificati (pointer-events: none)
- Lo stato di visibilità e blocco è gestito tramite Set nello state Pinia

**Comportamento**:
- Drag & drop con snap to grid
- Resize con 8 handle (4 angoli + 4 lati)
- Selezione con highlight
- Doppio click per editing inline (testo e liste)
- Il cursore cambia in "not-allowed" per gli elementi bloccati

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
- `uppercase`, `lowercase`, `capitalize`, `trim`, `truncate`, `default`, `if`, `sum`, `len`

**Elementi con supporto placeholder**:
| Tipo | Proprietà risolte | Note |
|------|-------------------|------|
| text | `text` | Supportato dalla versione iniziale |
| list | `items[].text` | Supportato dalla versione iniziale |
| table | `columns[].header`, `rows[].cells[].text` | Supportato dalla versione iniziale |
| quote | `text`, `author` | Corretto bug: prima passava `[]` invece di `$data` |
| callout | `text` | Corretto bug: prima passava `[]` invece di `$data` |
| checklist | `items[].text` | Corretto bug: prima passava `[]` invece di `$data` |
| radio | `items[].text` | Corretto bug: prima passava `[]` invece di `$data` |
| watermark | `text` | Aggiunto supporto placeholder |
| stamp | `text` | Aggiunto supporto placeholder |
| barcode | `text` | Aggiunto supporto placeholder |
| qrcode | `text` | Aggiunto supporto placeholder |
| codeBlock | `text` | Aggiunto supporto placeholder |
| signature | `label` | Aggiunto supporto placeholder |
| progressBar | `label` | Aggiunto supporto placeholder |

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
│   │   └── PdfRenderer.php      # Renderer template→PDF (gestisce tutti i tipi di elemento + multi-pagina)
│   └── Template/
│       ├── PlaceholderResolver.php  # Sintassi {{ }} con filtri
│       └── TemplateLoader.php       # Caricatore file template (supporta formato v2 e legacy)
├── composer.json                # Autoload PSR-4: EngiPDF\
└── test_*.php                   # Script di test
```

### Generazione PDF (`PdfDocument.php`)

Il PDF viene scritto byte-per-byte senza librerie esterne:

1. **Header**: `%PDF-1.4`
2. **Body**: oggetti PDF (pagine, font, stream di contenuto)
3. **Cross-Reference Table**: offset di ogni oggetto
4. **Trailer**: riferimento alla root e al conteggio oggetti

**Multi-Pagina**:
- `PdfDocument::createPage($width, $height)`: ora accetta dimensioni opzionali per pagina; se non specificate, usa A4 default
- `PdfDocument::$pageDimensions`: array associativo che memorizza le dimensioni (`[width, height]`) per ogni pagina creata, necessario per il calcolo delle coordinate Y during il rendering

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

### Rendering Multi-Pagina (`PdfRenderer.php`)

Il renderer è stato esteso per supportare documenti con più pagine:

**Metodo `normalizePages()`**:
- Normalizza il formato del template, supportando sia il formato v2 (`pages[]`) che il formato legacy (`page` + `elements` separati)
- Per il formato legacy, crea un singolo array `pages` con una sola pagina e assegna `pageId` agli elementi
- Garantisce backward compatibility con template creati prima del supporto multi-pagina

**Metodo `render()`**:
- Itera su ogni pagina dell'array `pages`
- Per ogni pagina:
  1. Crea un nuovo `PdfPage` con le dimensioni specificate in `page.settings`
  2. Filtra gli elementi che appartengono a quella pagina (`element.pageId === page.id`)
  3. Renderizza header e footer se configurati
  4. Renderizza gli elementi nell'ordine corretto (z-order)
  5. Renderizza gli elementi con `repeatOnAllPages: true` su ogni pagina
  6. Aggiunge la pagina al `PdfDocument`

**Elementi ripetuti su tutte le pagine** (`repeatOnAllPages`):
- Gli elementi con `repeatOnAllPages: true` vengono renderizzati su ogni pagina del documento
- Supportati: stamp, watermark, text, image, rectangle, line, ellipse, divider
- Gli elementi ripetuti mantengono le stesse coordinate (x, y) su ogni pagina
- Supportano `showIf` e `styleIf` come gli elementi normali
- Utile per timbri, filigrane, loghi o altri elementi che devono apparire su ogni foglio

**Gestione coordinate multi-pagina**:
- Ogni pagina ha le proprie dimensioni, memorizzate in `PdfDocument::$pageDimensions`
- Il renderer utilizza le dimensioni della pagina corrente per il calcolo delle coordinate Y
- La conversione Y: `y_pdf = pageHeight_pt - y_mm × MM_TO_PT - height_mm × MM_TO_PT`

### TemplateLoader (`TemplateLoader.php`)

Il caricatore template supporta entrambi i formati:

**Formato v2 (multi-pagina)**:
```json
{
  "version": 2,
  "pages": [
    { "id": "page-uuid", "name": "Pagina 1", "settings": { "width": 210, "height": 297 } }
  ],
  "elements": [
    { "id": "...", "pageId": "page-uuid", "type": "text", "x": 10, "y": 10, "width": 100, "height": 20 }
  ]
}
```

**Formato legacy (backward compatibility)**:
```json
{
  "page": { "width": 210, "height": 297 },
  "elements": [
    { "id": "...", "type": "text", "x": 10, "y": 10, "width": 100, "height": 20 }
  ]
}
```

- La validazione accetta entrambi i formati
- Il formato legacy viene convertito automaticamente in v2 dal `normalizePages()` del renderer
- I template legacy continuano a funzionare senza modifiche

---

## Sistema Multi-Pagina

### Architettura

Il sistema supporta documenti con multiple pagine, ciascuna con dimensioni e impostazioni indipendenti. Gli elementi sono associati alle pagine tramite il campo `pageId`.

**Flusso dati**:
```
Template JSON (v2)
       │
       ▼
TemplateLoader.php → normalizza → PdfRenderer::render()
                                        │
                                        ▼
                              Itera su pages[]
                              Per ogni pagina:
                                1. Crea PdfPage(width, height)
                                2. Filtra elementi per pageId
                                3. Renderizza header/footer
                                4. Renderizza elementi
                                        │
                                        ▼
                              PdfDocument (multi-pagina)
                                        │
                                        ▼
                              File PDF finale
```

### Formato JSON v2

```json
{
  "version": 2,
  "pages": [
    {
      "id": "550e8400-e29b-41d4-a716-446655440000",
      "name": "Pagina 1",
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
      "name": "Pagina 2",
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
      "content": "Testo sulla pagina 1"
    },
    {
      "id": "elem-uuid-2",
      "pageId": "550e8400-e29b-41d4-a716-446655440001",
      "type": "text",
      "x": 10,
      "y": 20,
      "width": 100,
      "height": 15,
      "content": "Testo sulla pagina 2"
    }
  ]
}
```

### Backward Compatibility

Il sistema è completamente backward-compatible:

1. **Formato legacy**: il `TemplateLoader` accetta template con `page` (singolo oggetto) + `elements` (senza `pageId`)
2. **Conversione automatica**: `PdfRenderer::normalizePages()` converte il formato legacy in v2 durante il rendering
3. **Editor**: `migrateLegacyDocument()` nel store converte automaticamente i documenti legacy al formato multipagina
4. **Nessuna rottura**: i template esistenti continuano a funzionare senza modifiche

### Note di Implementazione Multi-Pagina

1. **UUID per pagina**: ogni pagina ha un UUID generato da `crypto.randomUUID()` per identificazione univoca
2. **Assegnazione automatica**: quando si aggiunge un elemento, il `pageId` viene assegnato automaticamente dalla pagina attiva
3. **Spostamento pagina**: `moveElementToNextPage()` crea una nuova pagina se non esiste la successiva
4. **Eliminazione pagina**: quando una pagina viene eliminata, tutti i suoi elementi vengono rimossi
5. **Duplicazione**: duplica pagina crea copie profonde degli elementi con nuovi UUID
6. **Ordine pagine**: l'ordine delle pagine nell'array `pages` determina l'ordine nel PDF finale
7. **Dimensioni indipendenti**: ogni pagina può avere dimensioni diverse (es. A4 e A3 nello stesso documento)

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

Il sistema di snap arrotonda le coordinate alla griglia fissa di 1 mm:
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

---

## Programmabilità e Logica Condizionale

### ShowIf — Visibilità Condizionale

**Frontend**: `evaluateShowIf()` in `conditionHelpers.ts` valuta le regole `showIf` su ogni elemento. `ElementWrapper.vue` utilizza `isVisible` per nascondere gli elementi che non soddisfano la condizione. Il risultato è reactive grazie a `sampleData` nello store.

**Backend**: `PdfRenderer::evaluateCondition()` valuta le stesse regole prima di renderizzare ogni elemento. Se `showIf` fallisce, l'elemento viene saltato.

**Tipologia dati**:
```typescript
interface ConditionalRule {
  field: string
  op: 'eq' | 'neq' | 'gt' | 'lt' | 'gte' | 'lte' | 'empty' | 'notempty' | 'contains'
  value?: string
}
```

### StyleIf — Stile Condizionale

**Frontend**: `evaluateStyleIf()` in `conditionHelpers.ts` restituisce un oggetto con le proprietà da sovrascrivere. `ElementWrapper.vue` unisce `wrapperStyle` con `conditionalStyle`.

**Backend**: `PdfRenderer::applyStyleOverrides()` applica le sovrascritture al frame `$el` prima del rendering. Il campo `fill` viene mappato a `style.color` per il testo.

```typescript
interface StyleRule extends ConditionalRule {
  then: Record<string, unknown>
}
```

### DataRepeat — Ripetizione Dati

`DataRepeatElement` è un container che itera su un array nei `sampleData` e replica i suoi figli. In PHP, `PdfRenderer` gestisce il tipo `dataRepeat` con logica simile.

**Frontend**: `DataRepeatElement.vue` usa `resolveChild()` per sostituire `{{ item.campo }}` con i dati di ogni iterazione. `ElementWrapper.vue` include il componente.

**Parametri**:
- `repeatField`: chiave dell'array nei dati
- `direction`: `vertical` | `horizontal`
- `spacing`: distanza tra le copie (px)
- `children`: array di elementi template

### PlaceholderResolver — Filtri Espansi

Il resolver supporta filtri concatenati con `|` e accesso array con sintassi `[index]`:

```
{{ cliente.nome | uppercase | truncate:20 }}
{{ articoli[0].nome }}
{{ importi | sum | currency }}
```

**Filtri supportati**: `currency`, `date`, `number`, `uppercase`, `lowercase`, `capitalize`, `trim`, `truncate`, `default`, `len`, `if`, `sum`, `eq`, `neq`, `gt`, `lt`, `contains`, `empty`, `notempty`.

**Catena**: `resolveRaw()` restituisce `mixed`, `resolve()` applica `strval()` per compatibilità con output testo.

### sampleData

Il campo `sampleData` nel documento JSON contiene dati di esempio utilizzati dall'editor per preview e valutazione condizioni:

```json
{
  "sampleData": {
    "cliente": { "nome": "Mario Rossi" },
    "articoli": [{ "nome": "Laptop", "prezzo": 899 }]
  }
}
```

In PHP, `$data` viene passato al PlaceholderResolver e ai metodi di valutazione condizionale.

### Field Options — Dropdown intelligente

`fieldOptions.ts` fornisce `flattenSampleData()` che converte il sampleData annidato in una lista piatta di opzioni per i dropdown del PropertyPanel:

```
{ path: "cliente.nome", label: "cliente.nome", value: "Mario Rossi" }
{ path: "articoli[0].nome", label: "articoli[0].nome", value: "Laptop" }
```

La funzione gestisce ricorsivamente oggetti annidati, array con indici e primitività. Il PropertyPanel la usa per popolare i select di `showIf.field` e `styleIf[].field`, con opzione "Personalizza..." per input libero.

### Checklist Component

`ChecklistElement` rende una lista con checkbox (□ ☑). Ogni voce ha `{ text: string, checked: boolean }`.

**Frontend**: `ChecklistElement.vue` renderizza verticalmente le voci con un box SVG (□ o ☑) + testo. Lo stile `text-decoration: line-through` e `opacity: 0.5` si applicano alle voci barrate. Il PropertyPanel fornisce editor per aggiungere/rimuovere voci, toggle checkbox, regolare size/colori/gap.

**Backend**: `PdfRenderer::renderChecklist()` itera le voci e per ciascuna chiama:
1. `PdfPage::checkbox($x, $y, $size, $checked, $color)` — disegna □ (rettangolo) o ☑ (rettangolo + check con linee)
2. `PdfPage::text(...)` — testo accanto alla checkbox

**Modello dati**:
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

`RadioElement` rende una lista con bottoni radio (○ ●). Solo un'opzione può essere selezionata alla volta. Ogni voce ha `{ text: string, selected: boolean }`.

**Frontend**: `RadioElement.vue` renderizza verticalmente le voci con un cerchio SVG (○ o ●) + testo. Il cerchio selezionato ha un pallino interno. Il PropertyPanel fornisce editor per aggiungere/rimuovere opzioni, regolare size/colori/gap.

**Backend**: `PdfRenderer::renderRadio()` itera le voci e per ciascuna chiama:
1. `PdfPage::radio($x, $y, $size, $selected, $color)` — disegna ○ (cerchio vuoto) o ● (cerchio con pallino)
2. `PdfPage::text(...)` — testo accanto al radio button

**Modello dati**:
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

## Documentazione Completa dei Componenti

Per una documentazione dettagliata di ogni componente (proprietà, esempi JSON+PHP, programmabilità, categorie), consulta il file [`components-guide.md`](./components-guide.md).
