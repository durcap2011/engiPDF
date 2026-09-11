# engiPDF - Guida Utente

## Introduzione

engiPDF è un editor web per la creazione di template PDF. Permette di disporre elementi testuali, immagini, liste, rettangoli e linee su uno o più fogli virtuali, e di generare un file PDF tramite un motore PHP nativo. Supporta documenti multi-pagina con pagine indipendenti. L'interfaccia è disponibile in 5 lingue (italiano, inglese, spagnolo, tedesco, francese) selezionabili dalla barra degli strumenti.

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
- **Importa/Esporta**: salva o carica il template in formato JSON
- **Controlli pagine**: gestione pagine multiple del documento
- **Tema chiaro/scuro**: alterna tra tema chiaro e scuro dell'interfaccia

I controlli per la gestione delle pagine sono posizionati nella barra degli strumenti e consentono di:

| Controllo | Descrizione |
|---|---|
| **◀ / ▶** | Navigazione tra le pagine precedente e successiva |
| **Contatore pagine** | Indica la pagina corrente e il numero totale (es. "2 / 5") |
| **Aggiungi pagina** | Inserisce una nuova pagina vuota dopo quella corrente |
| **Duplica pagina** | Crea una copia della pagina corrente con tutti i suoi elementi |
| **Elimina pagina** | Rimuove la pagina corrente (non disponibile se è l'unica pagina) |

### 2. Palette Componenti (a sinistra)

Elenco dei componenti disponibili per il trascinamento sull'area di editing, organizzati in categorie con accordion:

| Categoria | Componenti |
|---|---|
| **Forme base** | Rettangolo, Cerchio, Linea, Divisore |
| **Testo** | Testo, Lista, Citazione, Blocco codice |
| **Dati** | Tabella, Barcode, QR Code, Grafico |
| **Media** | Immagine, Icona |
| **Layout** | Contenitore, Gruppo, Spaziatore, Taglio pagina |
| **Dinamici** | N. Pagina, Data, Barra avanzamento |
| **Stile** | Watermark, Timbro, Callout, Firma |

**Come inserire un componente**: trascina il componente desiderato dalla palette e rilascialo sull'area di editing. L'elemento verrà posizionato con snap alla griglia più vicina.

### 3. Area Canvas e Righelli

L'area centrale è l'area di editing principale, dotata di:

- **Righello orizzontale** (in alto): mostra la scala in centimetri con tacche per ogni millimetro. Si sincronizza automaticamente con pan e zoom dell'area canvas.
- **Righello verticale** (a sinistra): mostra la scala in centimetri con tacche per ogni millimetro. Si sincronizza automaticamente con pan e zoom dell'area canvas.
- **Pagine**: rappresenta i fogli bianchi con dimensioni configurabili (default A4: 210×297 mm). Le pagine multiple si dispongono verticalmente nell'area di editing, separate da un divisore visivo. Ogni pagina ha le proprie impostazioni indipendenti (dimensioni, header, footer)
- **Griglia di sfondo**: griglia leggermente visibile per orientamento

**Navigazione**:
- **Pan** (spostamento): trascina il mouse sullo sfondo dell'area canvas (cursore assume forma di "mano")
- **Zoom**: tieni premuto `Ctrl` e usa la rotella del mouse per ingrandire/rimpicciolire (range: 0.2x – 5x)
- **Centraggio**: al primo caricamento, la pagina viene centrata automaticamente nell'area disponibile
- **Pagina corrente**: la pagina attualmente in modifica è evidenziata; gli elementi di altre pagine sono visualizzati con minore opacità

### 4. Pannello Proprietà (a destra)

Mostra e permette di modificare le proprietà dell'elemento selezionato o della pagina. Tutte le etichette e le stringhe del pannello sono tradotte tramite vue-i18n nelle 5 lingue supportate.

**Proprietà della pagina** (indipendenti per ogni pagina):
- Formato carta (A4, A3, Letter, personalizzato)
- Dimensioni (icona ↔ larghezza, icona ↕ altezza in mm)
- Margini (icona Y↑ top, X→ right, Y↓ bottom, ←X left)
- Header/Footer (icona ⊤ altezza header, icona ⊥ altezza footer in mm)
- **Copia Header da**: seleziona una pagina sorgente da cui copiare gli elementi header
- **Copia Footer da**: seleziona una pagina sorgente da cui copiare gli elementi footer

**Proprietà dell'elemento** (variano per tipo):
- Posizione (x, y in mm)
- Dimensioni (width, height in mm)
- Stile (font, colore, allineamento, ecc.)

---

## Funzionalità Avanzate

### Tema Chiaro/Scuro

L'editor supporta due temi visivi: chiaro e scuro. Per alternare tra i temi, cliccare il pulsante della luna/sole nella barra degli strumenti. La preferenza viene salvata automaticamente nel browser.

### Selezione Multipla

Per selezionare più elementi contemporaneamente:
- **Shift+Clicca** su ogni elemento da aggiungere alla selezione
- Gli elementi selezionati sono evidenziati con bordo blu
- Trascinando un elemento selezionato, si spostano tutti gli elementi selezionati
- Il pannello proprietà mostra "N elementi selezionati"

### Allineamento e Distribuzione

Quando due o più elementi sono selezionati, la barra degli strumenti mostra i pulsanti di allineamento:

| Pulsante | Descrizione |
|---|---|
| **Allinea a sinistra** | Allinea tutti gli elementi al bordo sinistro |
| **Allinea al centro** | Allinea tutti gli elementi al centro orizzontale |
| **Allinea a destra** | Allinea tutti gli elementi al bordo destro |
| **Allinea in alto** | Allinea tutti gli elementi al bordo superiore |
| **Allinea al centro verticale** | Allinea tutti gli elementi al centro verticale |
| **Allinea in basso** | Allinea tutti gli elementi al bordo inferiore |

Quando tre o più elementi sono selezionati, sono disponibili anche i pulsanti di distribuzione:

| Pulsante | Descrizione |
|---|---|
| **Distribuisci orizzontalmente** | Distribuisce gli elementi con spaziatura uniforme orizzontale |
| **Distribuisci verticalmente** | Distribuisce gli elementi con spaziatura uniforme verticale |

### Raggruppamento

Per raggruppare più elementi in un unico blocco:
1. Seleziona gli elementi da raggruppare (Shift+Clicca)
2. Clicca il pulsante "Raggruppa" nella barra degli strumenti (o `Ctrl+G`)
3. Gli elementi vengono combinati in un gruppo
4. Il gruppo può essere spostato e ridimensionato come un singolo elemento
5. Per separare il gruppo, selezionalo e clicca "Separa" (o `Ctrl+Shift+G`)

### Copia e Incolla tra Pagine

Per copiare elementi da una pagina a un'altra:
1. Seleziona gli elementi da copiare
2. Premi `Ctrl+C` per copiare
3. Naviga alla pagina di destinazione
4. Premi `Ctrl+V` per incollare
5. Gli elementi vengono incollati con un offset di 5mm

### Ricerca e Sostituzione

Per cercare e sostituire testo nel documento:
1. Premi `Ctrl+F` per aprire la barra di ricerca
2. Inserisci il testo da cercare
3. Usa i pulsanti ◀/▶ o premi Enter/Shift+Enter per navigare tra i risultati
4. Inserisci il testo di sostituzione nel secondo campo
5. Clicca "S" per sostituire il risultato corrente o "SA" per sostituire tutti
6. Premi Esc per chiudere la barra

### Pannello Livelli

Il pannello livelli (a sinistra) mostra tutti gli elementi della pagina corrente:
- **Selezione**: clicca su un elemento nella lista per selezionarlo
- **Visibilità**: clicca sull'icona occhio per nascondere/mostrare un elemento
- **Blocco**: clicca sull'icona lucchetto per bloccare/sbloccare un elemento
- Gli elementi nascosti sono visualizzati con opacità ridotta
- Gli elementi bloccati non possono essere selezionati o modificati sull'canvas

### Selezione e Spostamento

- **Clicca** su un elemento per selezionarlo. Appariranno 8 maniglie di ridimensionamento (4 angoli + 4 lati).
- **Shift+Clicca** su un elemento per aggiungerlo alla selezione (selezione multipla).
- **Trascina** un elemento selezionato per spostarlo. Il posizionamento segue la griglia di snap.
- Quando più elementi sono selezionati, trascinando un elemento si spostano tutti quelli selezionati.
- **Clicca** sullo sfondo per deselezionare tutti gli elementi.
- **Selezione multi-pagina**: gli elementi delle altre pagine sono visualizzati con minore opacità e non sono selezionabili; solo gli elementi della pagina corrente possono essere selezionati e modificati.

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
| `Ctrl+C` | Copia elementi selezionati |
| `Ctrl+V` | Incolla elementi copiati |
| `Ctrl+G` | Raggruppa elementi selezionati |
| `Ctrl+Shift+G` | Separa gruppo selezionato |
| `Ctrl+F` | Apri/chiudi barra di ricerca |

---

## Gestione Pagine Multiple

engiPDF supporta documenti con pagine multiple. Ogni pagina è un'unità indipendente con le proprie impostazioni e i propri elementi.

### Creazione e Gestione delle Pagine

- **Pagina predefinita**: ogni nuovo documento inizia con una singola pagina
- **Aggiungi pagina**: clicca il pulsante "+" nella barra degli strumenti per inserire una nuova pagina vuota dopo quella corrente
- **Duplica pagina**: copia tutti gli elementi della pagina corrente in una nuova pagina
- **Elimina pagina**: rimuove la pagina corrente (non disponibile con una sola pagina)
- **Navigazione**: usa le frecce ◀/▶ o il contatore pagine per navigare tra le pagine

### Organizzazione delle Pagine

Le pagine si dispongono verticalmente nell'area di editing:
- Ogni pagina è separata da un divisore visivo con il numero della pagina
- La pagina corrente è evidenziata e gli elementi delle altre pagine sono visualizzati con minore opacità
- Lo scroll verticale dell'area canvas mostra tutte le pagine in sequenza

### Proprietà Indipendenti per Pagina

Ogni pagina ha le proprie impostazioni configurabili nel pannello proprietà:
- **Formato carta**: A4, A3, Letter, personalizzato
- **Dimensioni**: icona ↔ larghezza, icona ↕ altezza in mm
- **Margini**: icona Y↑ top, X→ right, Y↓ bottom, ←X left
- **Header/Footer**: icona ⊤ altezza header, icona ⊥ altezza footer in mm

### Associazione Elementi alle Pagine

Ogni elemento è associato a una specifica pagina tramite il campo `pageId`:
- Gli elementi vengono posizionati solo sulla pagina a cui appartengono
- Quando un elemento viene trascinato oltre i confini della sua pagina, viene mostrato un indicatore "Fuori pagina"
- L'indicatore include un pulsante per spostare automaticamente l'elemento alla pagina successiva
- Gli elementi non possono essere spostati manualmente tra le pagine; il spostamento avviene solo tramite il pulsante "Sposta alla pagina successiva"

### Indicatori di Sovrapposizione

Quando un elemento eccede i confini della pagina:
- Viene visualizzato un banner "Fuori pagina" con un pulsante "Sposta alla pagina successiva"
- Cliccando il pulsante, l'elemento viene automaticamente spostato alla pagina successiva mantenendo le sue coordinate relative
- Se l'elemento viene ridimensionato entro i confini, l'indicatore scompare automaticamente

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

### Tabella
Tabella con intestazioni e righe dati:
- **Nome**: identificativo della tabella, usato come placeholder per i dati (`{{ nome }}`)
- **Colonne**: numero configurabile, con larghezze proporzionali
- **Intestazioni**: testo e stile individuali per colonna (font, dimensione, grassetto, corsivo, colore, allineamento)
- **Stile celle dati**: impostazioni di default applicabili a tutte le celle, con sfondo personalizzabile
- **Righe dati**: ogni cella può avere uno stile diverso (allineamento, grassetto, colore sfondo, etc.)
- **Ripeti intestazione**: checkbox per ripetere la prima riga su ogni nuova pagina
- **Dati dinamici**: passare un array PHP con lo stesso nome della tabella per popolare le righe
- **Paginazione automatica**: quando la tabella supera l'area disponibile tra header e footer, viene automaticamente spezzata su più pagine con ripetizione dell'intestazione

### Gruppo
Contenitore per raggruppare più elementi:
- **Raggruppamento**: combina più elementi in un unico blocco
- **Spostamento**: il gruppo può essere spostato come un singolo elemento
- **Ridimensionamento**: il gruppo può essere ridimensionato
- **Separazione**: il gruppo può essere separato per ripristinare gli elementi originali

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
- Le coordinate si riferiscono alla pagina corrente

### Righello Verticale (a sinistra)
- Posizionato nella parte sinistra dell'area canvas, sotto la barra degli strumenti
- Mostra la scala in centimetri con tacche per ogni millimetro
- Numeri ruotati verticalmente per leggibilità
- Si aggiorna automaticamente durante pan e zoom
- Le coordinate si riferiscono alla pagina corrente

### Angolo di Intersezione
- Area grigia nell'angolo superiore-sinistro dove i due righelli si incontrano
- Funge da area di riferimento visivo

---

## Esportazione e Importazione

### Esporta JSON
Salva l'intero template in formato JSON, includendo:
- Impostazioni pagine (multiple)
- Tutti gli elementi con le loro proprietà
- Immagini incorporate (base64)
- Font personalizzati (riferimenti)

### Importa JSON
Carica un template JSON precedentemente salvato dall'editor.

### Incolla JSON
Incolla direttamente un JSON scritto a mano nell'editor. Il sistema gestisce automaticamente:
- **ID mancanti**: genera UUID per pagine e elementi
- **pageId mancanti**: assegna gli elementi alla prima pagina
- **Impostazioni mancanti**: applica i valori predefiniti (formato A4, margini standard)
- **Campi opzionali**: completa i campi mancanti con valori di default

Questo permette di importare template JSON creati manualmente seguendo il formato descritto in [`template.md`](../template.md).

### Genera PDF
Utilizza il template JSON per generare un file PDF tramite il motore PHP nativo:
- Apri il terminale nella cartella `engine/`
- Esegui: `php generate.php template.json output.pdf`
- Il PDF conterrà tutte le pagine definite nel template nell'ordine especificato

---

## Formato Documento (JSON)

Il template è un file JSON. La versione attuale è la versione 2, che supporta pagine multiple.

> **Riferimento completo**: Per un esempio JSON con **tutti i componenti** e **tutte le proprietà** supportate, consulta il file [`template.md`](../template.md) nella root del progetto.

### Formato Versione 2 (attuale)

```json
{
  "version": 2,
  "name": "Nome Documento",
  "pages": [
    {
      "id": "page-1",
      "width": 210,
      "height": 297,
      "unit": "mm",
      "margins": { "top": 30, "right": 20, "bottom": 30, "left": 20 },
      "headerHeight": 15,
      "footerHeight": 10
    },
    {
      "id": "page-2",
      "width": 210,
      "height": 297,
      "unit": "mm",
      "margins": { "top": 30, "right": 20, "bottom": 30, "left": 20 },
      "headerHeight": 20,
      "footerHeight": 20
    }
  ],
  "defaultFont": "helvetica",
  "elements": [
    {
      "type": "text",
      "pageId": "page-1",
      "x": 20,
      "y": 20,
      "width": 170,
      "height": 15,
      "text": "Titolo Pagina 1",
      "style": {
        "font": "helvetica",
        "weight": "bold",
        "size": 24,
        "color": [0, 0, 0],
        "align": "left"
      }
    },
    {
      "type": "text",
      "pageId": "page-2",
      "x": 20,
      "y": 20,
      "width": 170,
      "height": 15,
      "text": "Contenuto Pagina 2",
      "style": {
        "font": "times",
        "weight": "normal",
        "size": 12,
        "color": [0, 0, 0],
        "align": "left"
      }
    }
  ]
}
```

### Struttura Versione 2

| Campo | Tipo | Descrizione |
|---|---|---|
| `version` | number | Versione del formato (deve essere 2) |
| `name` | string | Nome del documento |
| `pages` | array | Array di oggetti pagina, ognuno con le proprie impostazioni |
| `pages[].id` | string | Identificativo univoco della pagina |
| `pages[].width` | number | Larghezza pagina in mm |
| `pages[].height` | number | Altezza pagina in mm |
| `pages[].margins` | object | Margini della pagina (top, right, bottom, left) |
| `pages[].headerHeight` | number | Altezza area header in mm |
| `pages[].footerHeight` | number | Altezza area footer in mm |
| `pages[].headerSourcePageId` | string | ID della pagina sorgente per copiare l'header (opzionale) |
| `pages[].footerSourcePageId` | string | ID della pagina sorgente per copiare il footer (opzionale) |
| `defaultFont` | string | Font predefinito per il documento |
| `elements` | array | Array di tutti gli elementi di tutte le pagine |
| `elements[].pageId` | string | ID della pagina a cui appartiene l'elemento |

### Backward Compatibility

I template nella versione 1 vengono migrati automaticamente all'apertura:
- Il campo `page` singolo viene convertito in un array `pages[]` con un singolo elemento
- Gli esistenti `elements` vengono associati automaticamente alla prima pagina
- La versione del formato viene aggiornata da 1 a 2
- La migrazione è trasparente e non modifica il layout esistente

### Formato Versione 1 (obsoleto)

```json
{
  "version": 1,
  "name": "Nome Documento",
  "page": {
    "width": 210,
    "height": 297,
    "unit": "mm",
    "margins": { "top": 20, "right": 20, "bottom": 20, "left": 20 },
    "headerHeight": 15,
    "footerHeight": 10
  },
  "defaultFont": "helvetica",
  "elements": [...]
}
```

**Nota**: il formato versione 1 è obsoleto. Tutti i nuovi template dovrebbero utilizzare la versione 2.

---

## Programmabilità

engiPDF supporta la logica condizionale e la generazione dinamica di contenuti direttamente nei template JSON.

### Visibilità Condizionale (showIf)

Ogni elemento può avere un campo `showIf` che ne controlla la visibilità in base ai dati:

```json
{
  "type": "text",
  "text": "Sconto applicato",
  "showIf": { "field": "ordine.stato", "op": "eq", "value": "scontato" }
}
```

**Operatori disponibili**: `eq`, `neq`, `gt`, `lt`, `gte`, `lte`, `empty`, `notempty`, `contains`

Se la condizione non è soddisfatta, l'elemento non viene renderizzato nel PDF né visualizzato nell'editor.

**Come impostare una condizione nell'editor**:
1. Seleziona l'elemento nel canvas
2. Nel Pannello Proprietà (destra), scorri fino a "Programmabilità"
3. Attiva il checkbox "Condizione visibilità"
4. Nel campo "Campo", seleziona dal dropdown il percorso del dato (es. `ordine.stato → "pagato"`)
5. Scegli l'operatore (es. "Uguale a")
6. Inserisci il valore da confrontare
7. Nell'editor vedrai subito l'anteprima: se `ordine.stato` nel sampleData è "pagato", l'elemento è visibile

Se il campo che cerchi non è nel dropdown, seleziona "Personalizza..." per digitare il percorso manualmente.

### Stile Condizionale (styleIf)

Ogni elemento può avere un campo `styleIf` che modifica lo stile in base ai dati:

```json
{
  "type": "text",
  "text": "Stato: {{ ordine.stato }}",
  "styleIf": [
    { "field": "ordine.stato", "op": "eq", "value": "pagato", "then": { "fill": [0, 0.6, 0] } },
    { "field": "ordine.stato", "op": "eq", "value": "scaduto", "then": { "fill": [0.8, 0, 0] } }
  ]
}
```

La prima regola che restituisce true applica lo stile definito in `then`. Il campo `fill` imposta il colore del testo.

### Ripetizione su Tutte le Pagine (repeatOnAllPages)

Alcuni elementi possono essere ripetuti automaticamente su ogni pagina del documento:

1. Seleziona l'elemento nel canvas
2. Nel Pannello Proprietà (destra), scorri fino a "Programmabilità"
3. Attiva il checkbox "Ripeti su tutte le pagine"

**Elementi supportati**: Timbro, Filigrana, Testo, Immagine, Rettangolo, Linea, Ellisse, Divisore

**Esempio d'uso**: Un timbro "BOZZA" o "RISERVATO" che deve apparire su ogni pagina del documento.

### Ripetizione Dati (DataRepeat)

Il componente "Ripeti dati" genera automaticamente copie dei suoi elementi figli per ogni elemento di un array nei dati:

1. Trascina "Ripeti dati" dalla palette
2. Imposta il "Campo dati" (es. `articoli`)
3. Aggiungi gli elementi figli come contenitore
4. Nei testi figli usa `{{ item.nome }}`, `{{ item.prezzo }}` per i campi dell'elemento corrente

### Filtri Placeholder

I placeholder `{{ }}` supportano filtri per formattare i dati:

| Filtro | Esempio | Descrizione |
|--------|---------|-------------|
| `currency` | `{{ importo \| currency }}` | Formato valuta (es. €1.234,56) |
| `date:"format"` | `{{ data \| date:"d/m/Y" }}` | Formato data PHP |
| `number:N` | `{{ valore \| number:2 }}` | N cifre decimali |
| `uppercase` | `{{ testo \| uppercase }}` | Maiuscolo |
| `lowercase` | `{{ testo \| lowercase }}` | Minuscolo |
| `capitalize` | `{{ testo \| capitalize }}` | Prima lettera maiuscola |
| `trim` | `{{ testo \| trim }}` | Rimuove spazi |
| `truncate:N` | `{{ testo \| truncate:20 }}` | Taglia a N caratteri |
| `default:"val"` | `{{ testo \| default:"N/A" }}` | Valore di default |
| `len` | `{{ lista \| len }}` | Lunghezza array |
| `if:"match":"then":"else"` | `{{ val \| if:"si":"Ok":"No" }}` | Condizionale inline |

**Accesso array**: `{{ articoli[0].nome }}` accede al primo elemento.

**Catena filtri**: `{{ testo \| uppercase \| truncate:10 }}` applica prima uppercase poi tronca.

### Componenti con Supporto Placeholder

I seguenti componenti supportano la sintassi `{{ variabile }}` nei loro campi testuali:

| Componente | Campo/i supportati | Esempio |
|------------|-------------------|---------|
| **Testo** | `text` | `{{ nome_cliente }}` |
| **Lista** | `items[].text` | `{{ item.descrizione }}` |
| **Tabella** | `columns[].header`, `rows[].cells[].text` | `{{ colonna.nome }}` |
| **Citazione** | `text`, `author` | `{{ citazione.testo }}`, `{{ citazione.autore }}` |
| **Callout** | `text` | `{{ messaggio }}` |
| **Checklist** | `items[].text` | `{{ voce.descrizione }}` |
| **Radio** | `items[].text` | `{{ opzione.etichetta }}` |
| **Filigrana** | `text` | `{{ company }} - BOZZA` |
| **Timbro** | `text` | `{{ stato }}` |
| **Barcode** | `text` | `{{ codice_prodotto }}` |
| **QR Code** | `text` | `{{ link_registrazione }}` |
| **Blocco Codice** | `text` | `{{ codice_generato }}` |
| **Firma** | `label` | `{{ firmatario }}` |
| **Barra Progresso** | `label` | `{{ percentuale }}%` |

### Checklist

Il componente "Checklist" mostra una lista con checkbox (□ ☑):

```json
{
  "type": "checklist",
  "items": [
    { "text": "Documenti firmati", "checked": true },
    { "text": "Pagamento ricevuto", "checked": false }
  ],
  "size": 11,
  "color": [0, 0, 0],
  "checkedColor": [0, 0.6, 0],
  "gap": 3
}
```

**Proprietà**:
- `items`: array di oggetti `{ text, checked }`
- `size`: dimensione font in punti
- `color`: colore testo [R, G, B]
- `checkedColor`: colore checkbox marcata [R, G, B]
- `gap`: spaziatura verticale tra voci in mm

Nell'editor puoi cliccare le checkbox per anteprima, aggiungere/rimuovere voci dal Pannello Proprietà.

### Radio

Il componente "Radio" mostra una lista con bottoni radio (○ ●):

```json
{
  "type": "radio",
  "items": [
    { "text": "Opzione A", "selected": true },
    { "text": "Opzione B", "selected": false },
    { "text": "Opzione C", "selected": false }
  ],
  "size": 11,
  "color": [0, 0, 0],
  "selectedColor": [0, 0.5, 1],
  "gap": 3
}
```

**Proprietà**:
- `items`: array di oggetti `{ text, selected }`
- `size`: dimensione font in punti
- `color`: colore testo [R, G, B]
- `selectedColor`: colore cerchio selezionato [R, G, B]
- `gap`: spaziatura verticale tra voci in mm

A differenza della checklist, solo un'opzione può essere selezionata alla volta.

---

## Documentazione Completa dei Componenti

Per una documentazione dettagliata di ogni componente (proprietà, esempi JSON+PHP, programmabilità), consulta il file [`components-guide.md`](./components-guide.md).
