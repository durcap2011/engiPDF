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

### Righelli (`HorizontalRuler.vue`, `VerticalRuler.vue`)

I righelli sono componenti Vue che renderizzano SVG con tacche di scala sincronizzate con pan/zoom del canvas.

**Architettura**:
- Ogni righello è un componente indipendente con props `panX`/`panY` e `zoom`
- Usa `ResizeObserver` per rilevare automaticamente le dimensioni del container
- Il calcolo delle tacche avviene in un `computed` che determina la finestra visibile in mm

**Algoritmo di rendering tacche**:
1. Calcola il range di mm visibili: `visibleStartMm = -pan / (MM_TO_PX * zoom)` e `visibleEndMm = (containerSize - pan) / (MM_TO_PX * zoom)`
2. Arrotonda ai 10mm multipli più vicini per i limiti
3. Per ogni mm nel range, genera una tacca con:
   - **Tacca maggiore** (numerata): ogni 10mm (1cm)
   - **Tacca media**: ogni 5mm
   - **Tacca minore**: ogni 1mm (esclusa se non multipla di 5)

**Posizionamento SVG**:
- Le tacche maggiore si estendono dall'alto (orizzontale) o dal sinistro (verticale)
- Le tacche minori partono dal bordo opposto
- I numeri (solo per tacche maggiori) usano `writing-mode: vertical-rl` con `transform: rotate(180deg)` per il righello verticale

**Performance**:
- I mark vengono ricalcolati solo quando cambiano `panX`/`panY`, `zoom` o `containerSize`
- SVG rendering è hardware-accelerato dal browser
- Nessuna reattività eccessiva grazie all'uso di `computed`

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

### Placeholder System (`PlaceholderResolver.php`)

Sintassi: `{{ variabile }}` o `{{ oggetto.proprieta }}`

Filtri disponibili:
- `currency`: formattazione valuta
- `date:"d/m/Y"`: formattazione data
- `number:N`: N cifre decimali

### Element Factory (`getDefaultElement.ts`)

Ogni tipo di elemento ha una factory che restituisce un oggetto con valori di default. **Importante**: la factory NON include il campo `id` — lo store lo genera automaticamente via `crypto.randomUUID()`.

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

Le immagini vengono embeddate nel PDF come:
- Decodifica Base64 dal data URL
- Determinazione automatica del tipo (JPEG, PNG)
- Flusso binario raw (JPEG) o con filter per PNG
- Calcolo proporzioni per `fit` (contain/cover/stretch)

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

1. **Componenti separati**: `HorizontalRuler` e `VerticalRuler` sono indipendenti, posizionati nella griglia CSS del canvas wrapper
2. **ResizeObserver**: i righelli si adattano automaticamente alle dimensioni del container, non richiedono props di dimensione
3. **Calcolo computed**: le tacche vengono ricalcolate solo al cambio di pan/zoom/dimensione, non ad ogni frame
4. **SVG rendering**: le tacche sono linee SVG, non elementi DOM, per performance ottimali
5. **Sincronizzazione**: pan e zoom sono reactive refs condivisi tra canvas e righelli
6. **Angolo di intersezione**: un div vuoto nello spazio dove i due righelli si incontrano, con sfondo coerente

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
