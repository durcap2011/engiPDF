# engiPDF - Guida Utente

## Introduzione

engiPDF è un editor web per la creazione di template PDF. Permette di disporre elementi testuali, immagini, liste, rettangoli e linee su un foglio virtuale, e di generare un file PDF tramite un motore PHP nativo.

---

## Interfaccia dell'Editor

L'editor si compone di quattro aree principali:

```
┌─────────────────────────────────────────────────────────┐
│                    Barra degli Strumenti                 │
├────────┬──────────────────────────────┬─────────────────┤
│        │  ┌─────────────────────────┐ │                 │
│Palette │  │ Righello Orizzontale    │ │   Pannello      │
│Compon- │  ├─────┬───────────────────┤ │   Proprietà     │
│enti    │  │Righ.│                   │ │                 │
│        │  │Ver. │   Area Canvas     │ │                 │
│        │  │     │                   │ │                 │
│        │  └─────┴───────────────────┘ │                 │
└────────┴──────────────────────────────┴─────────────────┘
```

### 1. Barra degli Strumenti (in alto)

La barra degli strumenti contiene:
- **Logo e nome documento**: visualizza il nome corrente del progetto
- **Undo/Redo**: torna all'azione precedente o ripristina quella successiva (fino a 50 livelli)
- **Movimento layer**: sposta l'elemento selezionato avanti/indietro nello stack
- **Duplica**: crea una copia dell'elemento selezionato
- **Elimina**: rimuove l'elemento selezionato
- **Configurazione griglia**: imposta la dimensione della griglia di snap (in mm)
- **Importa/Esporta**: salva o carica il template in formato JSON

### 2. Palette Componenti (a sinistra)

Elenco dei componenti disponibili per il trascinamento sull'area di editing:

| Componente | Descrizione |
|---|---|
| **Testo** | Campo di testo con formattazione personalizzabile (font, colore, allineamento, ecc.) |
| **Rettangolo** | Forma rettangolare con colore di riempimento e bordo opzionali |
| **Linea** | Linea orizzontale con colore e spessore personalizzabili |
| **Lista** | Lista puntata o numerata con supporto all'annidamento |
| **Immagine** | Area per immagini (supporta formati JPG, PNG, SVG tramite upload) |

**Come inserire un componente**: trascina il componente desiderato dalla palette e rilascialo sull'area di editing. L'elemento verrà posizionato con snap alla griglia più vicina.

### 3. Area Canvas e Righelli

L'area centrale è l'area di editing principale, dotata di:

- **Righello orizzontale** (in alto): mostra la scala in centimetri con tacche per ogni millimetro. Si sincronizza automaticamente con pan e zoom dell'area canvas.
- **Righello verticale** (a sinistra): mostra la scala in centimetri con tacche per ogni millimetro. Si sincronizza automaticamente con pan e zoom dell'area canvas.
- **Pagina**: rappresenta il foglio bianco con dimensioni configurabili (default A4: 210×297 mm)
- **Griglia di sfondo**: griglia leggermente visibile per orientamento

**Navigazione**:
- **Pan** (spostamento): trascina il mouse sullo sfondo dell'area canvas (cursore assumes forma di "mano")
- **Zoom**: tieni premuto `Ctrl` e usa la rotella del mouse per ingrandire/rimpicciolire (range: 0.2x – 5x)
- **Centraggio**: al primo caricamento, la pagina viene centrata automaticamente nell'area disponibile

### 4. Pannello Proprietà (a destra)

Mostra e permette di modificare le proprietà dell'elemento selezionato o della pagina:

**Proprietà della pagina**:
- Formato carta (A4, A3, Letter, personalizzato)
- Dimensioni (larghezza × altezza in mm)
- Margini (top, right, bottom, left)

**Proprietà dell'elemento** (variano per tipo):
- Posizione (x, y in mm)
- Dimensioni (width, height in mm)
- Stile (font, colore, allineamento, ecc.)

---

## Funzionalità di Editing

### Selezione e Spostamento

- **Clicca** su un elemento per selezionarlo. Appariranno 8 maniglie di ridimensionamento (4 angoli + 4 lati).
- **Trascina** un elemento selezionato per spostarlo. Il posizionamento segue la griglia di snap.
- **Clicca** sullo sfondo per deselezionare tutti gli elementi.

### Ridimensionamento

- Trascina una delle 8 maniglie per ridimensionare l'elemento.
- Dimensione minima: 5mm per lato.
- **Doppio clic** su una maniglia per l'**auto-dimensionamento**: l'elemento si adatta automaticamente al suo contenuto (utile per testo e liste).

### Editing Inline

- **Doppio clic** su un elemento di testo per modificare il contenuto direttamente sull'area di editing.
- **Doppio clic** su un elemento lista per modificare gli elementi della lista.
- Per le liste: `Tab` aumenta l'indentazione, `Ctrl+Tab` la diminuisce.
- Premi `Escape` o clicca fuori per uscire dall'editing.

### Scorciatoie da Tasto

| Combinazione | Azione |
|---|---|
| `Ctrl+Z` | Annulla |
| `Ctrl+Shift+Z` / `Ctrl+Y` | Ripristina |
| `Ctrl+D` | Duplica elemento selezionato |
| `Delete` | Elimina elemento selezionato |

---

## Tipi di Elemento

### Testo
Elemento di testo libero con stili personalizzabili:
- **Font**: Helvetica, Times, Courier e varianti (Bold, Italic, BoldItalic)
- **Font personalizzati**:支持 TTF tramite upload nel pannello proprietà
- **Dimensione**: in punti
- **Colore**: RGB
- **Allineamento**: sinistra, centro, destra, giustificato
- **Stile**: grassetto, corsivo, sottolineato
- **Interlinea**: rapporto tra altezza di riga

### Rettangolo
Forma rettangolare con:
- **Colore di riempimento** (fill): RGB opzionale
- **Colore del bordo** (stroke): RGB opzionale
- **Spessore del bordo**: in punti

### Linea
Linea con:
- **Colore**: RGB
- **Spessore**: in punti
- Punti di estremità definiti da coordinate (x, y) e (x2, y2)

### Lista
Lista puntata o numerata con:
- **Tipo bullet**: cerchio, quadrato, trattino, diamante, freccia, numero
- **Livelli di annidamento**: supporto ricorsivo con `Tab` / `Ctrl+Tab`
- **Stile testo**: stesso delle stringhe di testo
- **Indentazione**: spaziatura bullet e testo configurabile

### Immagine
Area per immagini:
- **Supporto formati**: JPG, PNG, SVG (via upload)
- **Modalità adattamento**: contain, cover, stretch
- **Doppio clic** per cambiare immagine

---

## Righelli

L'editor include due righelli sincronizzati con l'area di editing, simili a quelli di Microsoft Word:

### Righello Orizzontale (in alto)
- Posizionato nella parte superiore dell'area canvas, a destra della palette componenti
- Mostra la scala in centimetri con tacche per ogni millimetro
- Tacche maggiore (numerarie) ogni centimetro
- Tacche medie ogni 5mm
- Tacche minori ogni mm
- Si aggiorna automaticamente durante pan e zoom

### Righello Verticale (a sinistra)
- Posizionato nella parte sinistra dell'area canvas, sotto la barra degli strumenti
- Mostra la scala in centimetri con tacche per ogni millimetro
- Numeri ruotati verticalmente per leggibilità
- Si aggiorna automaticamente durante pan e zoom

### Angolo di Intersezione
- Area grigia nell'angolo superiore-sinistro dove i due righelli si incontrano
- Funge da area di riferimento visivo

---

## Esportazione

### Esporta JSON
Salva l'intero template in formato JSON, includendo:
- Impostazioni pagina
- Tutti gli elementi con le loro proprietà
- Immagini incorporate (base64)
- Font personalizzati (riferimenti)

### Genera PDF
Utilizza il template JSON per generare un file PDF tramite il motore PHP nativo:
- Apri il terminale nella cartella `engine/`
- Esegui: `php generate.php template.json output.pdf`

---

## Formato Documento (JSON)

Il template è un file JSON con la seguente struttura:

```json
{
  "version": 1,
  "name": "Nome Documento",
  "page": {
    "width": 210,
    "height": 297,
    "unit": "mm",
    "margins": { "top": 20, "right": 20, "bottom": 20, "left": 20 }
  },
  "defaultFont": "helvetica",
  "elements": [
    {
      "type": "text",
      "x": 20,
      "y": 20,
      "width": 170,
      "height": 15,
      "text": "Titolo",
      "style": {
        "font": "helvetica",
        "weight": "bold",
        "size": 24,
        "color": [0, 0, 0],
        "align": "left"
      }
    }
  ]
}
```
