# Guida ai Componenti

engiPDF dispone di 28 componenti che possono essere trascinati dalla palette laterale sul canvas per costruire template PDF. Ogni componente ha proprietà specifiche, supporta placeholder `{{ }}` per dati dinamici, e può essere condizionato con regole `showIf`/`styleIf`.

## Categorie componenti

* **Testo e Dati** - Componenti per testo libero, data, numero pagina
* **Strutture Dati** - Tabelle, liste, checklist, radio button
* **Grafica** - Rettangoli, linee, ellissi, divisorii, icone
* **Immagini e Codici** - Immagini, QR code, barcode
* **Avvisi e Citazioni** - Callout, citazioni, blocchi codice
* **Timbri e Filigrane** - Timbri (stamp), filigrane (watermark)
* **Progresso e Firma** - Barre di progresso, aree firma
* **Grafici** - Chart (bar, pie, line)
* **Layout** - Contenitori, gruppi, spaziatori, taglio pagina, ripetizione dati

---

## Componenti Testo e Dati

---

## Testo (Text)

Breve testo stilizzato con font, colore, dimensione e allineamento personalizzabili. Supporta modifica inline con doppio-click.

### Categoria di appartenenza

Testo e Dati

### Proprietà

* **text** - Il testo da visualizzare. Supporta placeholder `{{ }}` per dati dinamici
* **style.font** - Font: `helvetica`, `times`, `courier` (o TTF personalizzate registrate)
* **style.weight** - Spessore: `normal` o `bold`
* **style.style** - Stile: `normal`, `italic`, `oblique`
* **style.underline** - Se `true`, sottolinea il testo
* **style.size** - Dimensione font in punti
* **style.color** - Colore RGB `[R, G, B]` con valori da 0 a 1
* **style.align** - Allineamento: `left`, `center`, `right`, `justify`
* **style.lineHeight** - Altezza riga opzionale
* **showIf** - Condizione di visibilità opzionale
* **styleIf** - Regole di stile condizionale opzionali
* **repeatOnAllPages** - Se `true`, il testo appare su ogni pagina

### Programmabilità

Supporta `showIf` per nascondere/mostrare in base ai dati, `styleIf` per cambiare colore in base al valore, e `repeatOnAllPages` per ripetere su tutte le pagine. I placeholder `{{ }}` vengono risolti con i dati passati al motore.

### Come utilizzarlo

1. Trascina "Testo" dalla palette sul canvas
2. Fai doppio-click per modificare il testo inline
3. Nel Pannello Proprietà configura font, colore, dimensione
4. Usa `{{ nome_campo }}` nel testo per dati dinamici
5. Opzionalmente aggiungi condizioni in "Programmabilità"

### Esempi

**Testo statico:**
```json
{
  "type": "text",
  "x": 20,
  "y": 50,
  "width": 80,
  "height": 10,
  "text": "Documento di Valutazione dei Rischi",
  "style": {
    "font": "helvetica",
    "weight": "bold",
    "style": "normal",
    "size": 16,
    "color": [0, 0, 0],
    "align": "center"
  }
}
```

**Testo con placeholder:**
```json
{
  "type": "text",
  "x": 20,
  "y": 80,
  "width": 100,
  "height": 10,
  "text": "Cliente: {{ cliente.nome }}",
  "style": {
    "font": "helvetica",
    "weight": "normal",
    "style": "normal",
    "size": 12,
    "color": [0, 0, 0],
    "align": "left"
  }
}
```

**Testo con styleIf e filtri:**
```json
{
  "type": "text",
  "x": 20,
  "y": 100,
  "width": 60,
  "height": 10,
  "text": "Importo: {{ importo | currency }}",
  "style": {
    "font": "helvetica",
    "weight": "bold",
    "style": "normal",
    "size": 14,
    "color": [0, 0, 0],
    "align": "right"
  },
  "styleIf": [
    { "field": "importo", "op": "gt", "value": "1000", "then": { "fill": [0.8, 0, 0] } }
  ]
}
```

**Rendering PHP:**
```php
$renderer = new PdfRenderer();
$template = [
  "pages" => [["id" => "p1", "settings" => ["width" => 210, "height" => 297]]],
  "elements" => [
    [
      "id" => "txt1", "pageId" => "p1", "type" => "text",
      "x" => 20, "y" => 50, "width" => 80, "height" => 10,
      "text" => "Cliente: {{ cliente.nome }}",
      "style" => ["font" => "helvetica", "weight" => "bold", "size" => 12, "color" => [0,0,0], "align" => "left"]
    ]
  ]
];
$data = ["cliente" => ["nome" => "Acme S.r.l."]];
$pdf = $renderer->render($template, $data);
```

---

## Data (Date)

Mostra la data corrente nel formato specificato. Il formato usa la sintassi PHP date.

### Categoria di appartenenza

Testo e Dati

### Proprietà

* **format** - Formato data PHP (es. `d/m/Y`, `Y-m-d`, `d/m/Y H:i`)
* **style** - Stile testo (font, peso, stile, dimensione, colore, allineamento)
* **showIf** - Condizione di visibilità opzionale
* **styleIf** - Regole di stile condizionale opzionali

### Programmabilità

Supporta `showIf` e `styleIf`. Non usa placeholder `{{ }}` — la data viene generata automaticamente dal motore con `date($format)`.

### Come utilizzarlo

1. Trascina "Data" dalla palette sul canvas
2. Nel Pannello Proprietà imposta il formato desiderato
3. Configura lo stile del testo

### Esempi

**Data nel formato italiano:**
```json
{
  "type": "date",
  "x": 150,
  "y": 10,
  "width": 40,
  "height": 8,
  "format": "d/m/Y",
  "style": {
    "font": "helvetica",
    "weight": "normal",
    "style": "normal",
    "size": 10,
    "color": [0.3, 0.3, 0.3],
    "align": "right"
  }
}
```

**Data e ora:**
```json
{
  "type": "date",
  "x": 20,
  "y": 10,
  "width": 60,
  "height": 8,
  "format": "d/m/Y H:i",
  "style": {
    "font": "courier",
    "weight": "normal",
    "style": "normal",
    "size": 9,
    "color": [0, 0, 0],
    "align": "left"
  }
}
```

**Rendering PHP:**
```php
$template["elements"][] = [
  "id" => "date1", "pageId" => "p1", "type" => "date",
  "x" => 150, "y" => 10, "width" => 40, "height" => 8,
  "format" => "d/m/Y",
  "style" => ["font" => "helvetica", "weight" => "normal", "style" => "normal", "size" => 10, "color" => [0.3,0.3,0.3], "align" => "right"]
];
```

---

## Numero Pagina (Page Number)

Mostra il numero della pagina corrente. L'allineamento determina la posizione orizzontale.

### Categoria di appartenenza

Testo e Dati

### Proprietà

* **style** - Stile testo (font, peso, stile, dimensione, colore, allineamento)

### Programmabilità

Supporta `showIf` e `styleIf`. Non usa placeholder `{{ }}` — il numero pagina viene generato automaticamente dal motore.

### Come utilizzarlo

1. Trascina "Numero Pagina" dalla palette sul canvas
2. Posizionalo tipicamente in header o footer
3. Configura allineamento (left, center, right) per posizionare il numero

### Esempi

**Numero pagina centrato in footer:**
```json
{
  "type": "pageNumber",
  "x": 90,
  "y": 280,
  "width": 30,
  "height": 8,
  "style": {
    "font": "helvetica",
    "weight": "normal",
    "style": "normal",
    "size": 10,
    "color": [0, 0, 0],
    "align": "center"
  }
}
```

**Rendering PHP:**
```php
$template["elements"][] = [
  "id" => "pg1", "pageId" => "p1", "type" => "pageNumber",
  "x" => 90, "y" => 280, "width" => 30, "height" => 8,
  "style" => ["font" => "helvetica", "weight" => "normal", "style" => "normal", "size" => 10, "color" => [0,0,0], "align" => "center"]
];
```

---

## Componenti Strutture Dati

---

## Tabella (Table)

Tabella con header, righe e colonne. Supporta paginazione automatica e dati dinamici.

### Categoria di appartenenza

Strutture Dati

### Proprietà

* **name** - Nome tabella (usato come chiave per dati dinamici)
* **columns** - Array di colonne, ognuna con `width` (peso relativo), `header` (testo intestazione), `headerStyle`
* **rows** - Array di righe, ognuna con `cells[]` (ogni cella ha `text` e `style` opzionale)
* **repeatHeader** - Se `true`, ripete l'intestazione su ogni nuova pagina
* **headerStyle** - Stile di default per intestazioni
* **cellStyle** - Stile di default per celle dati
* **borderColor** - Colore bordi tabella
* **borderWidth** - Spessore bordi
* **columns[].header** - Testo intestazione, supporta placeholder `{{ }}`
* **rows[].cells[].text** - Testo cella, supporta placeholder `{{ }}`

### Programmabilità

Supporta `showIf` e `styleIf`. La tabella gestisce la paginazione automatica: se non cabe in una pagina, crea nuove pagine e ripete l'header. In modalità dinamica, i dati vengono presi da un array nel sampleData usando il campo `name`.

### Come utilizzarlo

1. Trascina "Tabella" dalla palette sul canvas
2. Configura colonne (peso larghezza, intestazioni)
3. Aggiungi righe e compila le celle
4. Usa `{{ }}` nelle intestazioni e nelle celle per dati dinamici
5. Per dati dinamici, imposta `name` e passa un array nei dati

### Esempi

**Tabella statica con placeholder:**
```json
{
  "type": "table",
  "x": 20,
  "y": 60,
  "width": 170,
  "height": 100,
  "name": "tabella",
  "columns": [
    { "width": 60, "header": "Prodotto", "headerStyle": { "font": "helvetica", "weight": "bold", "size": 10, "color": [0,0,0], "align": "center" } },
    { "width": 30, "header": "Qtà", "headerStyle": { "font": "helvetica", "weight": "bold", "size": 10, "color": [0,0,0], "align": "center" } },
    { "width": 40, "header": "Prezzo", "headerStyle": { "font": "helvetica", "weight": "bold", "size": 10, "color": [0,0,0], "align": "center" } },
    { "width": 40, "header": "Totale", "headerStyle": { "font": "helvetica", "weight": "bold", "size": 10, "color": [0,0,0], "align": "center" } }
  ],
  "rows": [
    { "cells": [
      { "text": "{{ item.prodotto }}", "style": {} },
      { "text": "{{ item.quantita }}", "style": { "align": "center" } },
      { "text": "{{ item.prezzo | currency }}", "style": { "align": "right" } },
      { "text": "{{ item.totale | currency }}", "style": { "align": "right" } }
    ]}
  ],
  "repeatHeader": true,
  "headerStyle": { "font": "helvetica", "weight": "bold", "style": "normal", "size": 10, "color": [0,0,0], "align": "center" },
  "cellStyle": { "font": "helvetica", "weight": "normal", "style": "normal", "size": 10, "color": [0,0,0], "align": "left" },
  "borderColor": [0, 0, 0],
  "borderWidth": 0.5
}
```

**Rendering PHP con dati dinamici:**
```php
$template["elements"][] = [
  "id" => "tbl1", "pageId" => "p1", "type" => "table",
  "x" => 20, "y" => 60, "width" => 170, "height" => 100,
  "name" => "tabella",
  "columns" => [
    ["width" => 60, "header" => "Prodotto", "headerStyle" => ["font" => "helvetica", "weight" => "bold", "size" => 10, "color" => [0,0,0], "align" => "center"]],
    ["width" => 30, "header" => "Qtà", "headerStyle" => ["font" => "helvetica", "weight" => "bold", "size" => 10, "color" => [0,0,0], "align" => "center"]],
    ["width" => 40, "header" => "Prezzo", "headerStyle" => ["font" => "helvetica", "weight" => "bold", "size" => 10, "color" => [0,0,0], "align" => "center"]],
    ["width" => 40, "header" => "Totale", "headerStyle" => ["font" => "helvetica", "weight" => "bold", "size" => 10, "color" => [0,0,0], "align" => "center"]]
  ],
  "rows" => [
    ["cells" => ["{{ item.prodotto }}", "{{ item.quantita }}", "{{ item.prezzo | currency }}", "{{ item.totale | currency }}"]]
  ],
  "repeatHeader" => true,
  "headerStyle" => ["font" => "helvetica", "weight" => "bold", "size" => 10, "color" => [0,0,0], "align" => "center"],
  "cellStyle" => ["font" => "helvetica", "weight" => "normal", "size" => 10, "color" => [0,0,0], "align" => "left"],
  "borderColor" => [0, 0, 0], "borderWidth" => 0.5
];
$data = [
  "tabella" => [
    ["Laptop ASUS", "2", "899.00", "1798.00"],
    ["Mouse Logitech", "5", "29.90", "149.50"]
  ]
];
$pdf = $renderer->render($template, $data);
```

---

## Lista (List)

Lista puntata/numerata con supporto per bullet personalizzati e annidamento.

### Categoria di appartenenza

Strutture Dati

### Proprietà

* **mode** - `static` (elementi fissi) o `dynamic` (dati da array)
* **items** - Array di voci, ognuna con `text` e opzionale `items` per annidamento
* **items[].text** - Testo della voce, supporta placeholder `{{ }}`
* **style.font** - Font
* **style.weight** - Spessore
* **style.style** - Stile
* **style.size** - Dimensione
* **style.color** - Colore
* **style.align** - Allineamento
* **style.bullet** - Tipo bullet: `circle`, `square`, `dash`, `diamond`, `arrow`, `number`
* **style.bullets** - Array di bullet per livelli annidati
* **style.bulletIndent** - Indentazione bullet in mm
* **style.textIndent** - Indentazione testo in mm

### Programmabilità

Supporta `showIf` e `styleIf`. I placeholder vengono risolti in ogni voce della lista. In modalità dinamica, gli elementi possono essere presi da un array nei dati.

### Come utilizzarlo

1. Trascina "Lista" dalla palette sul canvas
2. Fai doppio-click per modificare le voci inline
3. Usa Tab per indentare (annidare), Shift+Tab per de-indentare
4. Configura tipo bullet e stile nel Pannello Proprietà
5. Usa `{{ }}` nel testo per dati dinamici

### Esempi

**Lista con bullet personalizzati:**
```json
{
  "type": "list",
  "x": 20,
  "y": 60,
  "width": 80,
  "height": 40,
  "mode": "static",
  "style": {
    "font": "helvetica",
    "weight": "normal",
    "style": "normal",
    "size": 11,
    "color": [0, 0, 0],
    "align": "left",
    "bullet": "circle",
    "bulletIndent": 5,
    "textIndent": 15
  },
  "items": [
    { "text": "Primo elemento" },
    { "text": "Secondo elemento" },
    { "text": "Terzo elemento", "items": [
      { "text": "Sotto-elemento 1" },
      { "text": "Sotto-elemento 2" }
    ]}
  ]
}
```

**Lista con placeholder:**
```json
{
  "type": "list",
  "x": 20,
  "y": 60,
  "width": 80,
  "height": 40,
  "mode": "static",
  "style": { "font": "helvetica", "weight": "normal", "size": 11, "color": [0,0,0], "bullet": "square", "bulletIndent": 5, "textIndent": 15 },
  "items": [
    { "text": "Cliente: {{ cliente.nome }}" },
    { "text": "Partita IVA: {{ cliente.piva }}" },
    { "text": "Indirizzo: {{ cliente.indirizzo }}" }
  ]
}
```

**Rendering PHP:**
```php
$template["elements"][] = [
  "id" => "list1", "pageId" => "p1", "type" => "list",
  "x" => 20, "y" => 60, "width" => 80, "height" => 40,
  "mode" => "static",
  "style" => ["font" => "helvetica", "weight" => "normal", "size" => 11, "color" => [0,0,0], "bullet" => "circle", "bulletIndent" => 5, "textIndent" => 15],
  "items" => [
    ["text" => "Cliente: {{ cliente.nome }}"],
    ["text" => "Partita IVA: {{ cliente.piva }}"]
  ]
];
$data = ["cliente" => ["nome" => "Acme S.r.l.", "piva" => "IT12345678901"]];
$pdf = $renderer->render($template, $data);
```

---

## Checklist

Lista con checkbox (spuntate o meno). Utile per elenchi di controllo.

### Categoria di appartenenza

Strutture Dati

### Proprietà

* **items** - Array di voci con `text` (testo) e `checked` (booleano)
* **items[].text** - Testo della voce, supporta placeholder `{{ }}`
* **size** - Dimensione testo in punti
* **color** - Colore testo e bordo non checked
* **checkedColor** - Colore quando checked
* **gap** - Spazio tra le voci in mm

### Programmabilità

Supporta `showIf` e `styleIf`. I placeholder vengono risolti in ogni voce.

### Come utilizzarlo

1. Trascina "Checklist" dalla palette sul canvas
2. Nel Pannello Proprietà aggiungi/rimuovi voci
3. Clicca le checkbox per anteprima visiva
4. Configura colori e dimensioni
5. Usa `{{ }}` nel testo per dati dinamici

### Esempi

**Checklist con stati misti:**
```json
{
  "type": "checklist",
  "x": 20,
  "y": 60,
  "width": 80,
  "height": 30,
  "items": [
    { "text": "Documenti firmati", "checked": true },
    { "text": "Pagamento ricevuto", "checked": false },
    { "text": "Spedizione completata", "checked": false }
  ],
  "size": 11,
  "color": [0, 0, 0],
  "checkedColor": [0, 0.6, 0],
  "gap": 3
}
```

**Rendering PHP:**
```php
$template["elements"][] = [
  "id" => "chk1", "pageId" => "p1", "type" => "checklist",
  "x" => 20, "y" => 60, "width" => 80, "height" => 30,
  "items" => [
    ["text" => "Documenti firmati", "checked" => true],
    ["text" => "Pagamento ricevuto", "checked" => false]
  ],
  "size" => 11, "color" => [0,0,0], "checkedColor" => [0,0.6,0], "gap" => 3
];
```

---

## Radio

Lista di bottoni radio (una sola selezione possibile).

### Categoria di appartenenza

Strutture Dati

### Proprietà

* **items** - Array di opzioni con `text` (testo) e `selected` (booleano)
* **items[].text** - Testo dell'opzione, supporta placeholder `{{ }}`
* **size** - Dimensione testo in punti
* **color** - Colore testo e bordo non selezionato
* **selectedColor** - Colore quando selezionato
* **gap** - Spazio tra le voci in mm

### Programmabilità

Supporta `showIf` e `styleIf`. I placeholder vengono risolti in ogni voce.

### Come utilizzarlo

1. Trascina "Radio" dalla palette sul canvas
2. Nel Pannello Proprietà aggiungi/rimuovi opzioni
3. Clicca i radio button per selezionare l'opzione attiva
4. Configura colori e dimensioni

### Esempi

**Radio con opzioni:**
```json
{
  "type": "radio",
  "x": 20,
  "y": 60,
  "width": 80,
  "height": 30,
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

**Rendering PHP:**
```php
$template["elements"][] = [
  "id" => "radio1", "pageId" => "p1", "type" => "radio",
  "x" => 20, "y" => 60, "width" => 80, "height" => 30,
  "items" => [
    ["text" => "Opzione A", "selected" => true],
    ["text" => "Opzione B", "selected" => false],
    ["text" => "Opzione C", "selected" => false]
  ],
  "size" => 11, "color" => [0,0,0], "selectedColor" => [0,0.5,1], "gap" => 3
];
```

---

## Componenti Grafica

---

## Rettangolo (Rectangle)

Rettangolo con colore di riempimento e bordo opzionale.

### Categoria di appartenenza

Grafica

### Proprietà

* **fill** - Colore riempimento RGB (opzionale)
* **stroke** - Colore bordo RGB (opzionale)
* **strokeWidth** - Spessore bordo in mm

### Programmabilità

Supporta `showIf`, `styleIf` e `repeatOnAllPages`.

### Come utilizzarlo

1. Trascina "Rettangolo" dalla palette sul canvas
2. Ridimensiona con le maniglie
3. Configura colori nel Pannello Proprietà

### Esempi

**Rettangolo grigio con bordo nero:**
```json
{
  "type": "rectangle",
  "x": 20,
  "y": 20,
  "width": 40,
  "height": 20,
  "fill": [0.9, 0.9, 0.9],
  "stroke": [0, 0, 0],
  "strokeWidth": 0.5
}
```

**Rettangolo trasparente:**
```json
{
  "type": "rectangle",
  "x": 20,
  "y": 50,
  "width": 100,
  "height": 5,
  "fill": [0.2, 0.4, 0.8],
  "stroke": [],
  "strokeWidth": 0
}
```

**Rendering PHP:**
```php
$template["elements"][] = [
  "id" => "rect1", "pageId" => "p1", "type" => "rectangle",
  "x" => 20, "y" => 20, "width" => 40, "height" => 20,
  "fill" => [0.9, 0.9, 0.9], "stroke" => [0, 0, 0], "strokeWidth" => 0.5
];
```

---

## Linea (Line)

Linea retta tra due punti.

### Categoria di appartenenza

Grafica

### Proprietà

* **x2** - Coordinata fine X in mm
* **y2** - Coordinata fine Y in mm
* **color** - Colore RGB
* **lineWidth** - Spessore in mm

### Programmabilità

Supporta `showIf`, `styleIf` e `repeatOnAllPages`.

### Come utilizzarlo

1. Trascina "Linea" dalla palette sul canvas
2. Ridimensiona con le maniglie (modifica lunghezza e direzione)
3. Configura colore e spessore

### Esempi

**Linea orizzontale:**
```json
{
  "type": "line",
  "x": 20,
  "y": 100,
  "width": 170,
  "height": 0,
  "x2": 190,
  "y2": 100,
  "color": [0, 0, 0],
  "lineWidth": 0.5
}
```

**Rendering PHP:**
```php
$template["elements"][] = [
  "id" => "line1", "pageId" => "p1", "type" => "line",
  "x" => 20, "y" => 100, "width" => 170, "height" => 0,
  "x2" => 190, "y2" => 100, "color" => [0,0,0], "lineWidth" => 0.5
];
```

---

## Ellisse (Ellipse)

Ellisse o cerchio con riempimento e bordo.

### Categoria di appartenenza

Grafica

### Proprietà

* **fill** - Colore riempimento RGB
* **stroke** - Colore bordo RGB
* **strokeWidth** - Spessore bordo in mm

### Programmabilità

Supporta `showIf`, `styleIf` e `repeatOnAllPages`.

### Come utilizzarlo

1. Trascina "Ellisse" dalla palette sul canvas
2. Ridimensiona per ottenere cerchio o ellisse
3. Configura colori

### Esempi

**Cerchio grigio:**
```json
{
  "type": "ellipse",
  "x": 20,
  "y": 20,
  "width": 40,
  "height": 40,
  "fill": [0.9, 0.9, 0.9],
  "stroke": [0, 0, 0],
  "strokeWidth": 0.5
}
```

**Rendering PHP:**
```php
$template["elements"][] = [
  "id" => "ell1", "pageId" => "p1", "type" => "ellipse",
  "x" => 20, "y" => 20, "width" => 40, "height" => 40,
  "fill" => [0.9, 0.9, 0.9], "stroke" => [0, 0, 0], "strokeWidth" => 0.5
];
```

---

## Divisore (Divider)

Linea orizzontale divisoria con stili personalizzabili.

### Categoria di appartenenza

Grafica

### Proprietà

* **color** - Colore RGB
* **lineWidth** - Spessore in mm
* **lineStyle** - Stile: `solid`, `dashed`, `dotted`

### Programmabilità

Supporta `showIf`, `styleIf` e `repeatOnAllPages`.

### Come utilizzarlo

1. Trascina "Divisore" dalla palette sul canvas
2. Configura stile e colore

### Esempi

**Divisore tratteggiato:**
```json
{
  "type": "divider",
  "x": 20,
  "y": 100,
  "width": 170,
  "height": 1,
  "color": [0.5, 0.5, 0.5],
  "lineWidth": 0.5,
  "lineStyle": "dashed"
}
```

**Rendering PHP:**
```php
$template["elements"][] = [
  "id" => "div1", "pageId" => "p1", "type" => "divider",
  "x" => 20, "y" => 100, "width" => 170, "height" => 1,
  "color" => [0.5, 0.5, 0.5], "lineWidth" => 0.5, "lineStyle" => "dashed"
];
```

---

## Icona (Icon)

Icona SVG tra diverse opzioni predefinite.

### Categoria di appartenenza

Grafica

### Proprietà

* **name** - Nome icona: `check`, `warning`, `info`, `error`, `star`, `heart`, `arrow`
* **color** - Colore RGB

### Programmabilità

Supporta `showIf` e `styleIf`. Non supporta `repeatOnAllPages`.

### Come utilizzarlo

1. Trascina "Icona" dalla palette sul canvas
2. Seleziona il tipo di icona nel Pannello Proprietà
3. Configura colore e dimensione

### Esempi

**Icona di spunta verde:**
```json
{
  "type": "icon",
  "x": 20,
  "y": 20,
  "width": 10,
  "height": 10,
  "name": "check",
  "color": [0, 0.6, 0]
}
```

**Rendering PHP:**
```php
$template["elements"][] = [
  "id" => "ico1", "pageId" => "p1", "type" => "icon",
  "x" => 20, "y" => 20, "width" => 10, "height" => 10,
  "name" => "check", "color" => [0, 0.6, 0]
];
```

---

## Componenti Immagini e Codici

---

## Immagine (Image)

Area per immagine supporta JPEG e PNG. Le immagini vengono salvate come data URL base64.

### Categoria di appartenenza

Immagini e Codici

### Proprietà

* **src** - Data URL base64 dell'immagine (es. `data:image/png;base64,...`)
* **fit** - Modalità adattamento: `contain` (proporzionale, whole), `cover` (proporzionale, fill), `stretch` (deforma)

### Programmabilità

Supporta `showIf`, `styleIf` e `repeatOnAllPages`. Non usa placeholder `{{ }}`.

### Come utilizzarlo

1. Trascina "Immagine" dalla palette sul canvas
2. Fai doppio-click per aprire il selettore file
3. Seleziona un'immagine JPEG o PNG
4. Configura la modalità di adattamento

### Esempi

**Immagine con fit contain:**
```json
{
  "type": "image",
  "x": 20,
  "y": 20,
  "width": 50,
  "height": 30,
  "src": "data:image/png;base64,iVBORw0KGgoAAAANS...",
  "fit": "contain"
}
```

**Rendering PHP:**
```php
$template["elements"][] = [
  "id" => "img1", "pageId" => "p1", "type" => "image",
  "x" => 20, "y" => 20, "width" => 50, "height" => 30,
  "src" => "data:image/png;base64,iVBORw0KGgoAAAANS...",
  "fit" => "contain"
];
```

---

## QR Code

Genera un codice QR dal testo specificato.

### Categoria di appartenenza

Immagini e Codici

### Proprietà

* **text** - Contenuto del QR code (URL, testo, dati), supporta placeholder `{{ }}`

### Programmabilità

Supporta `showIf` e `styleIf`. Il testo viene risolto con placeholder.

### Come utilizzarlo

1. Trascina "QR Code" dalla palette sul canvas
2. Nel Pannello Proprietà imposta il contenuto
3. Usa `{{ }}` per dati dinamici

### Esempi

**QR code con URL dinamico:**
```json
{
  "type": "qrcode",
  "x": 150,
  "y": 240,
  "width": 30,
  "height": 30,
  "text": "{{ link_registrazione }}"
}
```

**Rendering PHP:**
```php
$template["elements"][] = [
  "id" => "qr1", "pageId" => "p1", "type" => "qrcode",
  "x" => 150, "y" => 240, "width" => 30, "height" => 30,
  "text" => "{{ link_registrazione }}"
];
$data = ["link_registrazione" => "https://example.com/registrazione/abc123"];
$pdf = $renderer->render($template, $data);
```

---

## Barcode

Genera un codice a barre dal testo specificato.

### Categoria di appartenenza

Immagini e Codici

### Proprietà

* **text** - Codice da barre, supporta placeholder `{{ }}`
* **format** - Formato: `code128`, `code39`, `ean13`
* **showText** - Se `true`, mostra il testo sotto il barcode

### Programmabilità

Supporta `showIf` e `styleIf`. Il testo viene risolto con placeholder.

### Come utilizzarlo

1. Trascina "Barcode" dalla palette sul canvas
2. Configura il formato e il testo
3. Usa `{{ }}` per dati dinamici

### Esempi

**Barcode Code128:**
```json
{
  "type": "barcode",
  "x": 20,
  "y": 240,
  "width": 60,
  "height": 30,
  "text": "{{ codice_prodotto }}",
  "format": "code128",
  "showText": true
}
```

**Rendering PHP:**
```php
$template["elements"][] = [
  "id" => "bc1", "pageId" => "p1", "type" => "barcode",
  "x" => 20, "y" => 240, "width" => 60, "height" => 30,
  "text" => "{{ codice_prodotto }}", "format" => "code128", "showText" => true
];
$data = ["codice_prodotto" => "123456789"];
$pdf = $renderer->render($template, $data);
```

---

## Componenti Avvisi e Citazioni

---

## Citazione (Quote)

Blocco citazione con barra laterale colorata, testo in corsivo e autore.

### Categoria di appartenenza

Avvisi e Citazioni

### Proprietà

* **text** - Testo della citazione, supporta placeholder `{{ }}`
* **author** - Nome dell'autore, supporta placeholder `{{ }}`
* **barColor** - Colore della barra laterale sinistra
* **style** - Stile testo (tipicamente `times`, italic)

### Programmabilità

Supporta `showIf` e `styleIf`. Testo e autore vengono risolti con placeholder.

### Come utilizzarlo

1. Trascina "Citazione" dalla palette sul canvas
2. Modifica testo e autore
3. Configura colore barra e stile

### Esempi

**Citazione con placeholder:**
```json
{
  "type": "quote",
  "x": 20,
  "y": 60,
  "width": 100,
  "height": 30,
  "text": "{{ citazione.testo }}",
  "author": "{{ citazione.autore }}",
  "barColor": [0.5, 0.5, 0.5],
  "style": {
    "font": "times",
    "weight": "normal",
    "style": "italic",
    "size": 12,
    "color": [0.2, 0.2, 0.2],
    "align": "left"
  }
}
```

**Rendering PHP:**
```php
$template["elements"][] = [
  "id" => "qt1", "pageId" => "p1", "type" => "quote",
  "x" => 20, "y" => 60, "width" => 100, "height" => 30,
  "text" => "{{ citazione.testo }}", "author" => "{{ citazione.autore }}",
  "barColor" => [0.5, 0.5, 0.5],
  "style" => ["font" => "times", "weight" => "normal", "style" => "italic", "size" => 12, "color" => [0.2,0.2,0.2], "align" => "left"]
];
$data = ["citazione" => ["testo" => "La semplicità è la raffinatezza suprema.", "autore" => "Leonardo da Vinci"]];
$pdf = $renderer->render($template, $data);
```

---

## Callout

Box informativo con bordo laterale colorato e icona.

### Categoria di appartenenza

Avvisi e Citazioni

### Proprietà

* **text** - Testo del messaggio, supporta placeholder `{{ }}`
* **style** - Tipo stile: `info`, `warning`, `error`, `success`
* **icon** - Carattere icona personalizzato
* **bgColor** - Colore sfondo RGB
* **borderColor** - Colore bordo laterale RGB

### Programmabilità

Supporta `showIf` e `styleIf`. Il testo viene risolto con placeholder.

### Come utilizzarlo

1. Trascina "Callout" dalla palette sul canvas
2. Seleziona lo stile (info, warning, error, success)
3. Modifica il testo del messaggio
4. Usa `{{ }}` per dati dinamici

### Esempi

**Callout informativo:**
```json
{
  "type": "callout",
  "x": 20,
  "y": 60,
  "width": 120,
  "height": 25,
  "text": "Attenzione: la scadenza è il {{ scadenza | date:\"d/m/Y\" }}",
  "style": "warning",
  "icon": "⚠",
  "bgColor": [1, 0.95, 0.8],
  "borderColor": [0.8, 0.6, 0]
}
```

**Rendering PHP:**
```php
$template["elements"][] = [
  "id" => "co1", "pageId" => "p1", "type" => "callout",
  "x" => 20, "y" => 60, "width" => 120, "height" => 25,
  "text" => "Scadenza: {{ scadenza | date:\"d/m/Y\" }}",
  "style" => "warning", "icon" => "⚠",
  "bgColor" => [1, 0.95, 0.8], "borderColor" => [0.8, 0.6, 0]
];
$data = ["scadenza" => "2025-12-31"];
$pdf = $renderer->render($template, $data);
```

---

## Blocco Codice (Code Block)

Blocco di codice su sfondo scuro con font monospace.

### Categoria di appartenenza

Avvisi e Citazioni

### Proprietà

* **text** - Codice sorgente, supporta placeholder `{{ }}`
* **language** - Linguaggio (es. `javascript`, `python`, `php`)

### Programmabilità

Supporta `showIf` e `styleIf`. Il testo viene risolto con placeholder.

### Come utilizzarlo

1. Trascina "Blocco Codice" dalla palette sul canvas
2. Inserisci il codice e specifica la linguaggio
3. Usa `{{ }}` per codice generato da dati

### Esempi

**Blocco codice PHP:**
```json
{
  "type": "codeBlock",
  "x": 20,
  "y": 60,
  "width": 120,
  "height": 40,
  "text": "<?php\n$cliente = '{{ cliente.nome }}';\necho $cliente;\n?>",
  "language": "php"
}
```

**Rendering PHP:**
```php
$template["elements"][] = [
  "id" => "cb1", "pageId" => "p1", "type" => "codeBlock",
  "x" => 20, "y" => 60, "width" => 120, "height" => 40,
  "text" => "<?php\n$nome = '{{ cliente.nome }}';\necho $nome;\n?>",
  "language" => "php"
];
$data = ["cliente" => ["nome" => "Acme S.r.l."]];
$pdf = $renderer->render($template, $data);
```

---

## Componenti Timbri e Filigrane

---

## Timbro (Stamp)

Timbro/bollo con bordo arrotondato, rotazione -15° e sfondo semitrasparente. Cinque preset colorati.

### Categoria di appartenenza

Timbri e Filigrane

### Proprietà

* **text** - Testo del timbro, supporta placeholder `{{ }}`
* **preset** - Preset: `approved` (verde), `confidential` (rosso), `draft` (grigio), `paid` (blu), `urgent` (viola)
* **color** - Colore RGB personalizzato (sovrascrive il preset)
* **repeatOnAllPages** - Se `true`, il timbro appare su ogni pagina

### Programmabilità

Supporta `showIf`, `styleIf` e `repeatOnAllPages`. Il testo viene risolto con placeholder. Lo styleIf può cambiare colore in base ai dati.

### Come utilizzarlo

1. Trascina "Timbro" dalla palette sul canvas
2. Seleziona il preset o personalizza il colore
3. Modifica il testo (opzionale)
4. Usa `{{ }}` per testo dinamico
5. Attiva "Ripeti su tutte le pagine" se necessario

### Esempi

**Timbro APPROVATO verde:**
```json
{
  "type": "stamp",
  "x": 120,
  "y": 200,
  "width": 60,
  "height": 25,
  "text": "APPROVATO",
  "preset": "approved",
  "color": [0, 0.6, 0]
}
```

**Timbro con placeholder e styleIf:**
```json
{
  "type": "stamp",
  "x": 120,
  "y": 200,
  "width": 60,
  "height": 25,
  "text": "{{ timbro }}",
  "preset": "approved",
  "color": [0, 0.6, 0],
  "repeatOnAllPages": true,
  "styleIf": [
    { "field": "timbro", "op": "eq", "value": "APPROVATO", "then": { "fill": [0, 0.6, 0] } },
    { "field": "timbro", "op": "eq", "value": "NP", "then": { "fill": [0.8, 0, 0] } }
  ]
}
```

**Rendering PHP:**
```php
$template["elements"][] = [
  "id" => "st1", "pageId" => "p1", "type" => "stamp",
  "x" => 120, "y" => 200, "width" => 60, "height" => 25,
  "text" => "{{ timbro }}", "preset" => "approved", "color" => [0, 0.6, 0],
  "repeatOnAllPages" => true,
  "styleIf" => [
    ["field" => "timbro", "op" => "eq", "value" => "APPROVATO", "then" => ["fill" => [0, 0.6, 0]]],
    ["field" => "timbro", "op" => "eq", "value" => "NP", "then" => ["fill" => [0.8, 0, 0]]]
  ]
];
$data = ["timbro" => "APPROVATO"];
$pdf = $renderer->render($template, $data);
```

---

## Filigrana (Watermark)

Testo ruotato semitrasparente come filigrana su ogni pagina.

### Categoria di appartenenza

Timbri e Filigrane

### Proprietà

* **text** - Testo della filigrana, supporta placeholder `{{ }}`
* **fontSize** - Dimensione font in punti
* **color** - Colore RGB
* **rotation** - Angolo rotazione in gradi (tipicamente -45)
* **opacity** - Opacità da 0 a 1

### Programmabilità

Supporta `showIf`, `styleIf` e `repeatOnAllPages`. Il testo viene risolto con placeholder.

### Come utilizzarlo

1. Trascina "Filigrana" dalla palette sul canvas
2. Configura testo, dimensione, colore e opacità
3. La filigrana appare automaticamente su ogni pagina

### Esempi

**Filigrana "BOZZA":**
```json
{
  "type": "watermark",
  "x": 30,
  "y": 100,
  "width": 150,
  "height": 40,
  "text": "{{ company }} - BOZZA",
  "fontSize": 48,
  "color": [0.8, 0.8, 0.8],
  "rotation": -45,
  "opacity": 0.3
}
```

**Rendering PHP:**
```php
$template["elements"][] = [
  "id" => "wm1", "pageId" => "p1", "type" => "watermark",
  "x" => 30, "y" => 100, "width" => 150, "height" => 40,
  "text" => "{{ company }} - BOZZA",
  "fontSize" => 48, "color" => [0.8, 0.8, 0.8], "rotation" => -45, "opacity" => 0.3
];
$data = ["company" => "Acme S.r.l."];
$pdf = $renderer->render($template, $data);
```

---

## Componenti Progresso e Firma

---

## Barra di Progresso (Progress Bar)

Barra di progresso orizzontale con etichetta.

### Categoria di appartenenza

Progresso e Firma

### Proprietà

* **value** - Valore da 0 a 100
* **color** - Colore barra riempita
* **bgColor** - Colore sfondo barra
* **label** - Etichetta sotto la barra, supporta placeholder `{{ }}`

### Programmabilità

Supporta `showIf` e `styleIf`. L'etichetta viene risolta con placeholder.

### Come utilizzarlo

1. Trascina "Barra di Progresso" dalla palette sul canvas
2. Imposta il valore percentuale
3. Configura colori e etichetta
4. Usa `{{ }}` nell'etichetta per dati dinamici

### Esempi

**Barra al 65%:**
```json
{
  "type": "progressBar",
  "x": 20,
  "y": 60,
  "width": 100,
  "height": 10,
  "value": 65,
  "color": [0.2, 0.6, 0.9],
  "bgColor": [0.9, 0.9, 0.9],
  "label": "65%"
}
```

**Barra con placeholder:**
```json
{
  "type": "progressBar",
  "x": 20,
  "y": 60,
  "width": 100,
  "height": 10,
  "value": 80,
  "color": [0, 0.6, 0],
  "bgColor": [0.9, 0.9, 0.9],
  "label": "{{ percentuale }}% completato"
}
```

**Rendering PHP:**
```php
$template["elements"][] = [
  "id" => "pb1", "pageId" => "p1", "type" => "progressBar",
  "x" => 20, "y" => 60, "width" => 100, "height" => 10,
  "value" => 80, "color" => [0, 0.6, 0], "bgColor" => [0.9, 0.9, 0.9],
  "label" => "{{ percentuale }}% completato"
];
$data = ["percentuale" => "80"];
$pdf = $renderer->render($template, $data);
```

---

## Firma (Signature)

Linea tratteggiata con etichetta centrata sotto, per aree di firma.

### Categoria di appartenenza

Progresso e Firma

### Proprietà

* **label** - Etichetta sotto la linea, supporta placeholder `{{ }}`
* **color** - Colore RGB

### Programmabilità

Supporta `showIf` e `styleIf`. L'etichetta viene risolta con placeholder.

### Come utilizzarlo

1. Trascina "Firma" dalla palette sul canvas
2. Configura etichetta e colore
3. Usa `{{ }}` nell'etichetta per dati dinamici

### Esempi

**Firma con nome dinamico:**
```json
{
  "type": "signature",
  "x": 20,
  "y": 250,
  "width": 60,
  "height": 20,
  "label": "{{ firmatario }}",
  "color": [0, 0, 0]
}
```

**Rendering PHP:**
```php
$template["elements"][] = [
  "id" => "sig1", "pageId" => "p1", "type" => "signature",
  "x" => 20, "y" => 250, "width" => 60, "height" => 20,
  "label" => "{{ firmatario }}", "color" => [0, 0, 0]
];
$data = ["firmatario" => "Dott. Mario Rossi"];
$pdf = $renderer->render($template, $data);
```

---

## Componenti Grafici

---

## Grafico (Chart)

Grafico con tre tipi: barre, torta, linea.

### Categoria di appartenenza

Grafici

### Proprietà

* **chartType** - Tipo: `bar`, `pie`, `line`
* **data** - Array di dati con `label` (stringa) e `value` (numero)
* **colors** - Array di colori RGB per le serie

### Programmabilità

Supporta `showIf` e `styleIf`. Non usa placeholder `{{ }}`.

### Come utilizzarlo

1. Trascina "Grafico" dalla palette sul canvas
2. Seleziona il tipo di grafico
3. Configura i dati e i colori

### Esempi

**Grafico a barre:**
```json
{
  "type": "chart",
  "x": 20,
  "y": 60,
  "width": 100,
  "height": 60,
  "chartType": "bar",
  "data": [
    { "label": "Q1", "value": 30 },
    { "label": "Q2", "value": 50 },
    { "label": "Q3", "value": 20 },
    { "label": "Q4", "value": 40 }
  ],
  "colors": [[0.2, 0.4, 0.8], [0.8, 0.4, 0.2], [0.2, 0.8, 0.4], [0.6, 0.2, 0.8]]
}
```

**Rendering PHP:**
```php
$template["elements"][] = [
  "id" => "ch1", "pageId" => "p1", "type" => "chart",
  "x" => 20, "y" => 60, "width" => 100, "height" => 60,
  "chartType" => "bar",
  "data" => [
    ["label" => "Q1", "value" => 30],
    ["label" => "Q2", "value" => 50],
    ["label" => "Q3", "value" => 20]
  ],
  "colors" => [[0.2, 0.4, 0.8], [0.8, 0.4, 0.2], [0.2, 0.8, 0.4]]
];
```

---

## Componenti Layout

---

## Contenitore (Container)

Rettangolo con bordo usato come contenitore per raggruppare elementi.

### Categoria di appartenenza

Layout

### Proprietà

* **fill** - Colore sfondo RGB (opzionale, trasparente di default)
* **borderColor** - Colore bordo RGB
* **borderWidth** - Spessore bordo in mm

### Programmabilità

Supporta `showIf` e `styleIf`. Non supporta `repeatOnAllPages`.

### Come utilizzarlo

1. Trascina "Contenitore" dalla palette sul canvas
2. Posiziona altri elementi al suo interno
3. Configura colori

### Esempi

**Contenitore con bordo:**
```json
{
  "type": "container",
  "x": 20,
  "y": 20,
  "width": 80,
  "height": 60,
  "borderColor": [0.5, 0.5, 0.5],
  "borderWidth": 0.5
}
```

**Rendering PHP:**
```php
$template["elements"][] = [
  "id" => "cnt1", "pageId" => "p1", "type" => "container",
  "x" => 20, "y" => 20, "width" => 80, "height" => 60,
  "borderColor" => [0.5, 0.5, 0.5], "borderWidth" => 0.5
];
```

---

## Gruppo (Group)

Contenitore per raggruppare più elementi con posizionamento relativo.

### Categoria di appartenenza

Layout

### Proprietà

* **children** - Array di elementi figli (qualsiasi tipo)

### Programmabilità

Supporta `showIf` e `styleIf`. Non supporta `repeatOnAllPages`.

### Come utilizzarlo

1. Trascina "Gruppo" dalla palette sul canvas
2. Trascina altri elementi all'interno del gruppo
3. Gli elementi figli si posizionano relativamente al gruppo

### Esempi

**Gruppo con testo e rettangolo:**
```json
{
  "type": "group",
  "x": 20,
  "y": 20,
  "width": 60,
  "height": 40,
  "children": [
    { "type": "text", "x": 5, "y": 5, "width": 50, "height": 10, "text": "Titolo", "style": { "font": "helvetica", "weight": "bold", "size": 14, "color": [0,0,0], "align": "left" } },
    { "type": "rectangle", "x": 0, "y": 20, "width": 60, "height": 15, "fill": [0.9, 0.9, 0.9] }
  ]
}
```

---

## Spaziatore (Spacer)

Spazio vuoto usato come separatore tra elementi. Non renderizza nulla nel PDF.

### Categoria di appartenenza

Layout

### Proprietà

Nessuna proprietà aggiuntiva oltre a `width` e `height`.

### Programmabilità

Supporta `showIf` e `styleIf`. Non produce rendering nel PDF.

### Come utilizzarlo

1. Trascina "Spaziatore" dalla palette sul canvas
2. Imposta la dimensione per controllare lo spazio

### Esempi

**Spaziatore verticale:**
```json
{
  "type": "spacer",
  "x": 20,
  "y": 100,
  "width": 10,
  "height": 20
}
```

---

## Taglio Pagina (Page Break)

Indica dove inizia una nuova pagina nell'editor. Non produce rendering nel PDF.

### Categoria di appartenenza

Layout

### Proprietà

Nessuna proprietà aggiuntiva oltre a `width` e `height`.

### Programmabilità

Supporta `showIf` e `styleIf`. Non produce rendering nel PDF.

### Come utilizzarlo

1. Trascina "Taglio Pagina" dalla palette sul canvas
2. Posizionalo dove vuoi iniziare una nuova pagina

### Esempi

**Taglio pagina:**
```json
{
  "type": "pageBreak",
  "x": 20,
  "y": 280,
  "width": 170,
  "height": 5
}
```

---

## Ripetizione Dati (Data Repeat)

Contenitore che ripete i suoi figli per ogni elemento di un array nei dati.

### Categoria di appartenenza

Layout

### Proprietà

* **repeatField** - Campo da sampleData da ripetere (es. `articoli`)
* **children** - Array di elementi figli da ripetere
* **direction** - Direzione: `vertical` o `horizontal`
* **spacing** - Spazio tra le ripetizioni in pixel

### Programmabilità

Supporta `showIf` e `styleIf`. I placeholder `{{ item.campo }}` nei figli vengono risolti per ogni elemento dell'array.

### Come utilizzarlo

1. Trascina "Ripeti Dati" dalla palette sul canvas
2. Imposta il "Campo dati" (es. `articoli`)
3. Trascina gli elementi figli all'interno
4. Nei testi figli usa `{{ item.nome }}`, `{{ item.prezzo }}` per i campi

### Esempi

**Ripetizione verticale:**
```json
{
  "type": "dataRepeat",
  "x": 20,
  "y": 60,
  "width": 100,
  "height": 60,
  "repeatField": "articoli",
  "direction": "vertical",
  "spacing": 5,
  "children": [
    { "type": "text", "x": 0, "y": 0, "width": 80, "height": 8, "text": "{{ item.nome }} - {{ item.prezzo | currency }}", "style": { "font": "helvetica", "size": 11, "color": [0,0,0] } }
  ]
}
```

**Rendering PHP:**
```php
$template["elements"][] = [
  "id" => "dr1", "pageId" => "p1", "type" => "dataRepeat",
  "x" => 20, "y" => 60, "width" => 100, "height" => 60,
  "repeatField" => "articoli", "direction" => "vertical", "spacing" => 5,
  "children" => [
    ["id" => "dr1c1", "pageId" => "p1", "type" => "text", "x" => 0, "y" => 0, "width" => 80, "height" => 8,
     "text" => "{{ item.nome }} - {{ item.prezzo | currency }}",
     "style" => ["font" => "helvetica", "size" => 11, "color" => [0,0,0], "align" => "left"]]
  ]
];
$data = [
  "articoli" => [
    ["nome" => "Laptop", "prezzo" => "899.00"],
    ["nome" => "Mouse", "prezzo" => "29.90"]
  ]
];
$pdf = $renderer->render($template, $data);
```

---

## Tabella Riepilogativa

| # | Componente | Tipo | Placeholder | showIf | styleIf | repeatOnAllPages |
|---|-----------|------|:-----------:|:------:|:-------:|:----------------:|
| 1 | Testo | `text` | Si | Si | Si | Si |
| 2 | Data | `date` | No | Si | Si | No |
| 3 | Numero Pagina | `pageNumber` | No | Si | Si | No |
| 4 | Tabella | `table` | Si | Si | Si | No |
| 5 | Lista | `list` | Si | Si | Si | No |
| 6 | Checklist | `checklist` | Si | Si | Si | No |
| 7 | Radio | `radio` | Si | Si | Si | No |
| 8 | Rettangolo | `rectangle` | No | Si | Si | Si |
| 9 | Linea | `line` | No | Si | Si | Si |
| 10 | Ellisse | `ellipse` | No | Si | Si | Si |
| 11 | Divisore | `divider` | No | Si | Si | Si |
| 12 | Icona | `icon` | No | Si | Si | No |
| 13 | Immagine | `image` | No | Si | Si | Si |
| 14 | QR Code | `qrcode` | Si | Si | Si | No |
| 15 | Barcode | `barcode` | Si | Si | Si | No |
| 16 | Citazione | `quote` | Si | Si | Si | No |
| 17 | Callout | `callout` | Si | Si | Si | No |
| 18 | Blocco Codice | `codeBlock` | Si | Si | Si | No |
| 19 | Timbro | `stamp` | Si | Si | Si | Si |
| 20 | Filigrana | `watermark` | Si | Si | Si | Si |
| 21 | Barra di Progresso | `progressBar` | Si | Si | Si | No |
| 22 | Firma | `signature` | Si | Si | Si | No |
| 23 | Grafico | `chart` | No | Si | Si | No |
| 24 | Contenitore | `container` | No | Si | Si | No |
| 25 | Gruppo | `group` | No | Si | Si | No |
| 26 | Spaziatore | `spacer` | No | Si | Si | No |
| 27 | Taglio Pagina | `pageBreak` | No | Si | Si | No |
| 28 | Ripetizione Dati | `dataRepeat` | No | Si | Si | No |
