# engiPDF — Riferimento Template JSON

Questo file mostra un template JSON completo con **tutti i componenti disponibili** e tutte le proprietà supportate.
Può essere usato come riferimento per creare template a mano o per comprendere la struttura del formato.

---

## Struttura Generale

Un template JSON è composto da:

```
{
  "version": 1,
  "name": "Nome documento",
  "defaultFont": "helvetica",
  "fonts": { ... },          // Facoltativo: font TTF personalizzati
  "sampleData": { ... },     // Facoltativo: dati di esempio per l'editor
  "pages": [ ... ],          // Pagine del documento
  "elements": [ ... ]        // Tutti gli elementi (ognuno ha un pageId)
}
```

---

## Font Personalizzati (opzionale)

Per usare font TTF al posto dei 14 Type1 predefiniti:

```json
"fonts": {
  "my-font": {
    "file": "C:/fonts/MyFont-Regular.ttf",
    "weight": "normal",
    "style": "normal"
  },
  "my-font-bold": {
    "file": "C:/fonts/MyFont-Bold.ttf",
    "weight": "bold",
    "style": "normal"
  }
}
```

**Alias supportati:**
- `arial`, `helv`, `sans-serif` → `helvetica`
- `times-new-roman`, `serif`, `georgia`, `bookman` → `times`
- `monospace`, `courier-new`, `mono` → `courier`

**Font predefiniti:** helvetica, helvetica-bold, helvetica-oblique, helvetica-boldoblique, times, times-bold, times-italic, times-bolditalic, courier, courier-bold, courier-oblique, courier-boldoblique, symbol, zapfdingbats

---

## Sample Data (opzionale)

Dati di esempio usati dall'editor per generare i menu a tendina dei campi.
Non vengono usati direttamente dal renderer PHP — servono solo all'editor.

```json
"sampleData": {
  "cliente": {
    "nome": "Mario Rossi",
    "email": "mario.rossi@example.com",
    "telefono": "+39 02 1234567",
    "indirizzo": "Via Roma 1, 20100 Milano",
    "partitaIva": "IT12345678901"
  },
  "fattura": {
    "numero": "FAT-2024-001",
    "data": "2024-01-15",
    "scadenza": "2024-02-15",
    "totale": 1250.00,
    "iva": 275.00,
    "stato": "emessa"
  },
  "righe": [
    { "descrizione": "Consulenza", "quantita": 10, "prezzo": 100, "totale": 1000 },
    { "descrizione": "Spese trasferta", "quantita": 1, "prezzo": 250, "totale": 250 }
  ],
  "azienda": {
    "nome": "engiPDF S.r.l.",
    "logo": "data:image/png;base64,..."
  }
}
```

---

## Sintassi Placeholder

Gli elementi testo supportano la sintassi `{{ percorso }}` per inserire dati dinamici:

| Sintassi | Descrizione |
|---|---|
| `{{ cliente.nome }}` | Accesso a proprietà annidata |
| `{{ fattura.totale \| currency }}` | Formattato come valuta (1.250,00) |
| `{{ fattura.data \| date:"d/m/Y" }}` | Data formattata |
| `{{ righe \| sum:"totale" }}` | Somma una proprietà di un array |
| `{{ cliente.nome \| uppercase }}` | Testo in maiuscolo |
| `{{ cliente.nome \| lowercase }}` | Testo in minuscolo |
| `{{ cliente.nome \| capitalize }}` | Prima lettera maiuscola |
| `{{ testo \| truncate:30 }}` | Tronca a 30 caratteri + ... |
| `{{ valore \| default:"N/D" }}` | Valore di default se vuoto |
| `{{ stato \| if:"emessa":"Emessa":"Annullata" }}` | Condizionale |
| `{{ righe \| len }}` | Lunghezza array |
| `{{ prezzo \| number:2 }}` | Numero con 2 decimali |
| `{{ testo \| trim }}` | Rimuove spazi |

I filtri si possono concatenare: `{{ nome | uppercase | truncate:20 }}`

---

## Formati Pagina Predefiniti

| Formato | Larghezza (mm) | Altezza (mm) |
|---|---|---|
| A3 | 297 | 420 |
| A4 | 210 | 297 |
| A5 | 148 | 210 |
| A6 | 105 | 148 |
| B5 | 176 | 250 |
| Letter | 215.9 | 279.4 |
| Legal | 215.9 | 355.6 |

---

## Template JSON Completo

```json
{
  "version": 1,
  "name": "Template Riferimento — Tutti i Componenti",
  "defaultFont": "helvetica",

  "fonts": {
    "my-custom-font": {
      "file": "C:/fonts/CustomFont-Regular.ttf",
      "weight": "normal",
      "style": "normal"
    }
  },

  "sampleData": {
    "cliente": {
      "nome": "Mario Rossi",
      "email": "mario.rossi@example.com",
      "telefono": "+39 02 1234567",
      "indirizzo": "Via Roma 1, 20100 Milano",
      "partitaIva": "IT12345678901"
    },
    "fattura": {
      "numero": "FAT-2024-001",
      "data": "2024-01-15",
      "scadenza": "2024-02-15",
      "totale": 1250.00,
      "iva": 275.00,
      "stato": "emessa"
    },
    "righe": [
      { "descrizione": "Consulenza", "quantita": 10, "prezzo": 100, "totale": 1000 },
      { "descrizione": "Spese trasferta", "quantita": 1, "prezzo": 250, "totale": 250 }
    ],
    "azienda": {
      "nome": "engiPDF S.r.l."
    }
  },

  "pages": [
    {
      "id": "page-1",
      "name": "Pagina Principale",
      "settings": {
        "width": 210,
        "height": 297,
        "unit": "mm",
        "margins": { "top": 30, "right": 20, "bottom": 30, "left": 20 },
        "headerHeight": 20,
        "footerHeight": 20
      }
    },
    {
      "id": "page-2",
      "name": "Pagina Secondaria",
      "settings": {
        "width": 210,
        "height": 297,
        "unit": "mm",
        "margins": { "top": 25, "right": 20, "bottom": 25, "left": 20 },
        "headerHeight": 0,
        "footerHeight": 15
      }
    }
  ],

  "elements": [

    {
      "id": "el-watermark",
      "pageId": "page-1",
      "type": "watermark",
      "x": 30,
      "y": 80,
      "width": 150,
      "height": 60,
      "text": "BOZZA",
      "fontSize": 72,
      "color": [0.85, 0.85, 0.85],
      "rotation": -45,
      "opacity": 0.15,
      "repeatOnAllPages": true
    },

    {
      "id": "el-stamp",
      "pageId": "page-1",
      "type": "stamp",
      "x": 130,
      "y": 20,
      "width": 60,
      "height": 30,
      "text": "APPROVATO",
      "preset": "approved",
      "color": [0, 0.6, 0]
    },

    {
      "id": "el-rectangle-header",
      "pageId": "page-1",
      "type": "rectangle",
      "x": 0,
      "y": 0,
      "width": 210,
      "height": 25,
      "fill": [0.1, 0.2, 0.5],
      "stroke": [],
      "strokeWidth": 0
    },

    {
      "id": "el-text-header",
      "pageId": "page-1",
      "type": "text",
      "x": 15,
      "y": 5,
      "width": 180,
      "height": 15,
      "text": "{{ azienda.nome }}",
      "style": {
        "font": "helvetica",
        "weight": "bold",
        "style": "normal",
        "size": 20,
        "color": [1, 1, 1],
        "align": "left"
      }
    },

    {
      "id": "el-text-titolo",
      "pageId": "page-1",
      "type": "text",
      "x": 20,
      "y": 35,
      "width": 170,
      "height": 12,
      "text": "FATTURA {{ fattura.numero }}",
      "style": {
        "font": "helvetica",
        "weight": "bold",
        "style": "normal",
        "size": 18,
        "color": [0.1, 0.2, 0.5],
        "align": "left"
      }
    },

    {
      "id": "el-text-dati",
      "pageId": "page-1",
      "type": "text",
      "x": 20,
      "y": 50,
      "width": 85,
      "height": 40,
      "text": "Cliente: {{ cliente.nome }}\nIndirizzo: {{ cliente.indirizzo }}\nEmail: {{ cliente.email }}\nTelefono: {{ cliente.telefono }}\nP.IVA: {{ cliente.partitaIva }}",
      "style": {
        "font": "helvetica",
        "weight": "normal",
        "style": "normal",
        "size": 10,
        "color": [0.2, 0.2, 0.2],
        "align": "left",
        "lineHeight": 1.5
      }
    },

    {
      "id": "el-text-fattura-info",
      "pageId": "page-1",
      "type": "text",
      "x": 115,
      "y": 50,
      "width": 75,
      "height": 30,
      "text": "Numero: {{ fattura.numero }}\nData: {{ fattura.data | date:\"d/m/Y\" }}\nScadenza: {{ fattura.scadenza | date:\"d/m/Y\" }}\nStato: {{ fattura.stato | uppercase }}",
      "style": {
        "font": "helvetica",
        "weight": "normal",
        "style": "normal",
        "size": 10,
        "color": [0.2, 0.2, 0.2],
        "align": "right",
        "lineHeight": 1.5
      }
    },

    {
      "id": "el-divider-1",
      "pageId": "page-1",
      "type": "divider",
      "x": 20,
      "y": 95,
      "width": 170,
      "height": 1,
      "color": [0.7, 0.7, 0.7],
      "lineWidth": 0.5,
      "lineStyle": "solid"
    },

    {
      "id": "el-table-righe",
      "pageId": "page-1",
      "type": "table",
      "x": 20,
      "y": 100,
      "width": 170,
      "height": 60,
      "name": "righe",
      "columns": [
        {
          "header": "Descrizione",
          "width": 40,
          "headerStyle": { "font": "helvetica", "weight": "bold", "size": 9, "color": [1, 1, 1], "background": [0.1, 0.2, 0.5] }
        },
        {
          "header": "Q.tà",
          "width": 15,
          "headerStyle": { "font": "helvetica", "weight": "bold", "size": 9, "color": [1, 1, 1], "background": [0.1, 0.2, 0.5], "align": "center" }
        },
        {
          "header": "Prezzo",
          "width": 20,
          "headerStyle": { "font": "helvetica", "weight": "bold", "size": 9, "color": [1, 1, 1], "background": [0.1, 0.2, 0.5], "align": "right" }
        },
        {
          "header": "Totale",
          "width": 20,
          "headerStyle": { "font": "helvetica", "weight": "bold", "size": 9, "color": [1, 1, 1], "background": [0.1, 0.2, 0.5], "align": "right" }
        }
      ],
      "rows": [],
      "repeatHeader": true,
      "headerStyle": { "font": "helvetica", "weight": "bold", "size": 9, "color": [1, 1, 1] },
      "cellStyle": { "font": "helvetica", "weight": "normal", "size": 9, "color": [0.2, 0.2, 0.2] },
      "borderColor": [0.8, 0.8, 0.8],
      "borderWidth": 0.5,
      "mode": "dynamic",
      "dynamicConfig": {
        "repeatField": "righe",
        "columns": [
          { "field": "descrizione", "header": "Descrizione" },
          { "field": "quantita", "header": "Q.tà" },
          { "field": "prezzo", "header": "Prezzo" },
          { "field": "totale", "header": "Totale" }
        ]
      }
    },

    {
      "id": "el-divider-2",
      "pageId": "page-1",
      "type": "divider",
      "x": 20,
      "y": 170,
      "width": 170,
      "height": 1,
      "color": [0.7, 0.7, 0.7],
      "lineWidth": 0.5,
      "lineStyle": "solid"
    },

    {
      "id": "el-text-totale",
      "pageId": "page-1",
      "type": "text",
      "x": 120,
      "y": 175,
      "width": 70,
      "height": 20,
      "text": "Totale: {{ fattura.totale | currency }}\nIVA: {{ fattura.iva | currency }}",
      "style": {
        "font": "helvetica",
        "weight": "bold",
        "style": "normal",
        "size": 12,
        "color": [0.1, 0.2, 0.5],
        "align": "right",
        "lineHeight": 1.5
      }
    },

    {
      "id": "el-rectangle-note",
      "pageId": "page-1",
      "type": "rectangle",
      "x": 20,
      "y": 200,
      "width": 170,
      "height": 25,
      "fill": [0.95, 0.95, 1],
      "stroke": [0.2, 0.4, 0.8],
      "strokeWidth": 0.5
    },

    {
      "id": "el-text-note",
      "pageId": "page-1",
      "type": "text",
      "x": 25,
      "y": 205,
      "width": 160,
      "height": 15,
      "text": "Note: Pagamento entro 30 giorni dalla data di emissione. Bonifico bancario al IBAN IT60 X054 2811 1010 0000 0123 456.",
      "style": {
        "font": "helvetica",
        "weight": "normal",
        "style": "italic",
        "size": 9,
        "color": [0.3, 0.3, 0.3],
        "align": "left"
      }
    },

    {
      "id": "el-signature",
      "pageId": "page-1",
      "type": "signature",
      "x": 20,
      "y": 240,
      "width": 80,
      "height": 20,
      "label": "Firma del cliente",
      "color": [0, 0, 0]
    },

    {
      "id": "el-date",
      "pageId": "page-1",
      "type": "date",
      "x": 140,
      "y": 240,
      "width": 50,
      "height": 10,
      "format": "d/m/Y",
      "style": {
        "font": "helvetica",
        "weight": "normal",
        "style": "normal",
        "size": 10,
        "color": [0.3, 0.3, 0.3],
        "align": "right"
      }
    },

    {
      "id": "el-page-number",
      "pageId": "page-1",
      "type": "pageNumber",
      "x": 90,
      "y": 280,
      "width": 30,
      "height": 10,
      "style": {
        "font": "helvetica",
        "weight": "normal",
        "style": "normal",
        "size": 10,
        "color": [0.5, 0.5, 0.5],
        "align": "center"
      }
    },

    {
      "id": "el-list-statica",
      "pageId": "page-1",
      "type": "list",
      "x": 20,
      "y": 215,
      "width": 80,
      "height": 20,
      "mode": "static",
      "items": [
        { "text": "Elemento primo livello" },
        { "text": "Elemento con sotto-lista", "items": [
          { "text": "Sotto-elemento 1" },
          { "text": "Sotto-elemento 2" }
        ]},
        { "text": "Terzo elemento" }
      ],
      "style": {
        "font": "helvetica",
        "weight": "normal",
        "style": "normal",
        "size": 10,
        "color": [0.2, 0.2, 0.2],
        "bullet": "circle",
        "bulletIndent": 5,
        "textIndent": 15,
        "lineHeight": 1.4
      }
    },

    {
      "id": "el-page-break",
      "pageId": "page-1",
      "type": "pageBreak",
      "x": 0,
      "y": 0,
      "width": 0,
      "height": 0
    },

    {
      "id": "el-container-esempio",
      "pageId": "page-2",
      "type": "container",
      "x": 20,
      "y": 30,
      "width": 170,
      "height": 80,
      "fill": [0.97, 0.97, 0.97],
      "borderColor": [0.8, 0.8, 0.8],
      "borderWidth": 1
    },

    {
      "id": "el-text-container-title",
      "pageId": "page-2",
      "type": "text",
      "x": 30,
      "y": 35,
      "width": 150,
      "height": 10,
      "text": "Sezione con Container",
      "style": {
        "font": "helvetica",
        "weight": "bold",
        "style": "normal",
        "size": 14,
        "color": [0.1, 0.2, 0.5],
        "align": "left"
      }
    },

    {
      "id": "el-rectangle-colors",
      "pageId": "page-2",
      "type": "rectangle",
      "x": 20,
      "y": 120,
      "width": 35,
      "height": 20,
      "fill": [0.2, 0.4, 0.8],
      "stroke": [],
      "strokeWidth": 0
    },

    {
      "id": "el-rectangle-stroke",
      "pageId": "page-2",
      "type": "rectangle",
      "x": 60,
      "y": 120,
      "width": 35,
      "height": 20,
      "fill": [],
      "stroke": [0.8, 0.2, 0.2],
      "strokeWidth": 2
    },

    {
      "id": "el-rectangle-both",
      "pageId": "page-2",
      "type": "rectangle",
      "x": 100,
      "y": 120,
      "width": 35,
      "height": 20,
      "fill": [1, 0.95, 0.8],
      "stroke": [0.8, 0.6, 0],
      "strokeWidth": 1
    },

    {
      "id": "el-ellipse-esempio",
      "pageId": "page-2",
      "type": "ellipse",
      "x": 145,
      "y": 120,
      "width": 35,
      "height": 20,
      "fill": [0.9, 0.9, 1],
      "stroke": [0.2, 0.4, 0.8],
      "strokeWidth": 1
    },

    {
      "id": "el-line-orizzontale",
      "pageId": "page-2",
      "type": "line",
      "x": 20,
      "y": 150,
      "width": 170,
      "height": 0,
      "x2": 190,
      "y2": 150,
      "color": [0, 0, 0],
      "lineWidth": 1
    },

    {
      "id": "el-line-tratteggiata",
      "pageId": "page-2",
      "type": "divider",
      "x": 20,
      "y": 155,
      "width": 170,
      "height": 1,
      "color": [0.5, 0.5, 0.5],
      "lineWidth": 0.5,
      "lineStyle": "dashed"
    },

    {
      "id": "el-line-puntinata",
      "pageId": "page-2",
      "type": "divider",
      "x": 20,
      "y": 160,
      "width": 170,
      "height": 1,
      "color": [0.5, 0.5, 0.5],
      "lineWidth": 0.5,
      "lineStyle": "dotted"
    },

    {
      "id": "el-list-dinamica",
      "pageId": "page-2",
      "type": "list",
      "x": 20,
      "y": 170,
      "width": 80,
      "height": 30,
      "mode": "dynamic",
      "items": [],
      "dynamicConfig": {
        "repeatField": "righe",
        "textField": "descrizione"
      },
      "style": {
        "font": "helvetica",
        "weight": "normal",
        "style": "normal",
        "size": 10,
        "color": [0.2, 0.2, 0.2],
        "bullet": "square",
        "bulletIndent": 5,
        "textIndent": 15,
        "lineHeight": 1.4
      }
    },

    {
      "id": "el-checklist",
      "pageId": "page-2",
      "type": "checklist",
      "x": 110,
      "y": 170,
      "width": 80,
      "height": 30,
      "items": [
        { "text": "Voce completata", "checked": true },
        { "text": "Voce in corso", "checked": false },
        { "text": "Voce da fare", "checked": false }
      ],
      "size": 11,
      "color": [0.2, 0.2, 0.2],
      "checkedColor": [0, 0.6, 0],
      "gap": 3
    },

    {
      "id": "el-radio",
      "pageId": "page-2",
      "type": "radio",
      "x": 20,
      "y": 210,
      "width": 80,
      "height": 30,
      "items": [
        { "text": "Opzione selezionata", "selected": true },
        { "text": "Opzione non selezionata", "selected": false },
        { "text": "Terza opzione", "selected": false }
      ],
      "size": 11,
      "color": [0.2, 0.2, 0.2],
      "selectedColor": [0.2, 0.4, 0.8],
      "gap": 3
    },

    {
      "id": "el-quote",
      "pageId": "page-2",
      "type": "quote",
      "x": 20,
      "y": 245,
      "width": 170,
      "height": 25,
      "text": "La semplicità è la sofisticatezza suprema.",
      "author": "Leonardo da Vinci",
      "barColor": [0.2, 0.4, 0.8],
      "style": {
        "font": "times",
        "weight": "normal",
        "style": "italic",
        "size": 11,
        "color": [0.3, 0.3, 0.3],
        "align": "left"
      }
    },

    {
      "id": "el-callout-info",
      "pageId": "page-2",
      "type": "callout",
      "x": 20,
      "y": 275,
      "width": 80,
      "height": 20,
      "text": "Questo è un callout informativo con testo di esempio.",
      "style": "info",
      "icon": "info",
      "bgColor": [0.9, 0.95, 1],
      "borderColor": [0.2, 0.4, 0.8]
    },

    {
      "id": "el-callout-warning",
      "pageId": "page-2",
      "type": "callout",
      "x": 110,
      "y": 275,
      "width": 80,
      "height": 20,
      "text": "Attenzione: questo è un callout di avviso.",
      "style": "warning",
      "icon": "warning",
      "bgColor": [1, 0.98, 0.9],
      "borderColor": [0.8, 0.6, 0]
    },

    {
      "id": "el-code-block",
      "pageId": "page-2",
      "type": "codeBlock",
      "x": 20,
      "y": 300,
      "width": 80,
      "height": 30,
      "text": "function hello() {\n  console.log('Hello World');\n}",
      "language": "javascript"
    },

    {
      "id": "el-progress-bar",
      "pageId": "page-2",
      "type": "progressBar",
      "x": 110,
      "y": 300,
      "width": 80,
      "height": 15,
      "value": 75,
      "color": [0.2, 0.6, 0.9],
      "bgColor": [0.9, 0.9, 0.9],
      "label": "Completamento: 75%"
    },

    {
      "id": "el-barcode",
      "pageId": "page-2",
      "type": "barcode",
      "x": 20,
      "y": 335,
      "width": 80,
      "height": 25,
      "text": "1234567890",
      "format": "code128",
      "showText": true
    },

    {
      "id": "el-qrcode",
      "pageId": "page-2",
      "type": "qrcode",
      "x": 110,
      "y": 335,
      "width": 25,
      "height": 25,
      "text": "https://engipdf.example.com"
    },

    {
      "id": "el-chart-bar",
      "pageId": "page-2",
      "type": "chart",
      "x": 20,
      "y": 365,
      "width": 50,
      "height": 30,
      "chartType": "bar",
      "data": [
        { "label": "Gen", "value": 40 },
        { "label": "Feb", "value": 65 },
        { "label": "Mar", "value": 55 },
        { "label": "Apr", "value": 80 }
      ],
      "colors": [
        [0.2, 0.4, 0.8],
        [0.8, 0.3, 0.2],
        [0.2, 0.7, 0.4],
        [0.9, 0.6, 0.1]
      ]
    },

    {
      "id": "el-chart-pie",
      "pageId": "page-2",
      "type": "chart",
      "x": 80,
      "y": 365,
      "width": 30,
      "height": 30,
      "chartType": "pie",
      "data": [
        { "label": "A", "value": 30 },
        { "label": "B", "value": 50 },
        { "label": "C", "value": 20 }
      ],
      "colors": [
        [0.2, 0.4, 0.8],
        [0.8, 0.3, 0.2],
        [0.2, 0.7, 0.4]
      ]
    },

    {
      "id": "el-chart-line",
      "pageId": "page-2",
      "type": "chart",
      "x": 120,
      "y": 365,
      "width": 70,
      "height": 30,
      "chartType": "line",
      "data": [
        { "label": "Gen", "value": 20 },
        { "label": "Feb", "value": 45 },
        { "label": "Mar", "value": 35 },
        { "label": "Apr", "value": 70 },
        { "label": "Mag", "value": 55 }
      ],
      "colors": [
        [0.2, 0.6, 0.9]
      ]
    },

    {
      "id": "el-icons",
      "pageId": "page-2",
      "type": "text",
      "x": 20,
      "y": 400,
      "width": 170,
      "height": 10,
      "text": "Icone: check, warning, info, error, star, heart, arrow (disegnate come primitive PDF)",
      "style": {
        "font": "helvetica",
        "weight": "normal",
        "style": "normal",
        "size": 8,
        "color": [0.5, 0.5, 0.5],
        "align": "left"
      }
    },

    {
      "id": "el-spacer",
      "pageId": "page-2",
      "type": "spacer",
      "x": 0,
      "y": 0,
      "width": 0,
      "height": 10
    },

    {
      "id": "el-text-showif",
      "pageId": "page-1",
      "type": "text",
      "x": 20,
      "y": 265,
      "width": 170,
      "height": 10,
      "text": "Questo testo appare solo se lo stato è 'emessa'",
      "showIf": {
        "field": "fattura.stato",
        "op": "eq",
        "value": "emessa"
      },
      "style": {
        "font": "helvetica",
        "weight": "normal",
        "style": "italic",
        "size": 9,
        "color": [0, 0.6, 0],
        "align": "left"
      }
    },

    {
      "id": "el-text-styleif",
      "pageId": "page-1",
      "type": "text",
      "x": 20,
      "y": 270,
      "width": 170,
      "height": 10,
      "text": "Questo testo diventa rosso se il totale supera 1000",
      "styleIf": [
        {
          "field": "fattura.totale",
          "op": "gt",
          "value": "1000",
          "then": {
            "color": [0.8, 0.1, 0.1]
          }
        }
      ],
      "style": {
        "font": "helvetica",
        "weight": "normal",
        "style": "normal",
        "size": 9,
        "color": [0.2, 0.2, 0.2],
        "align": "left"
      }
    },

    {
      "id": "el-image-placeholder",
      "pageId": "page-2",
      "type": "image",
      "x": 150,
      "y": 335,
      "width": 40,
      "height": 25,
      "src": "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==",
      "fit": "contain"
    },

    {
      "id": "el-group-esempio",
      "pageId": "page-2",
      "type": "group",
      "x": 20,
      "y": 415,
      "width": 80,
      "height": 20,
      "children": [
        {
          "id": "el-group-rect",
          "type": "rectangle",
          "x": 0,
          "y": 0,
          "width": 80,
          "height": 20,
          "fill": [0.95, 0.95, 0.95],
          "stroke": [0.7, 0.7, 0.7],
          "strokeWidth": 0.5
        },
        {
          "id": "el-group-text",
          "type": "text",
          "x": 5,
          "y": 5,
          "width": 70,
          "height": 10,
          "text": "Elemento in un gruppo",
          "style": {
            "font": "helvetica",
            "weight": "normal",
            "style": "normal",
            "size": 10,
            "color": [0.2, 0.2, 0.2],
            "align": "center"
          }
        }
      ]
    }
  ]
}
```

---

## Riferimento Rapido per Tipo

### Proprietà Base (tutti gli elementi)

| Proprietà | Tipo | Default | Descrizione |
|---|---|---|---|
| `id` | string | _(richiesto)_ | UUID univoco dell'elemento |
| `pageId` | string | _(richiesto)_ | ID della pagina di appartenenza |
| `type` | string | _(richiesto)_ | Tipo dell'elemento |
| `x` | number | `0` | Posizione X in mm |
| `y` | number | `0` | Posizione Y in mm (da cima) |
| `width` | number | `0` | Larghezza in mm |
| `height` | number | `0` | Altezza in mm |
| `showIf` | object | `null` | Condizione di visibilità |
| `styleIf` | array | `null` | Regole di override stile |
| `repeatOnAllPages` | boolean | `false` | Ripeti su ogni pagina |

### Proprietà Specifiche per Tipo

#### `text`
| Proprietà | Tipo | Default |
|---|---|---|
| `text` | string | `""` |
| `style.font` | string | `"helvetica"` |
| `style.weight` | string | `"normal"` |
| `style.style` | string | `"normal"` |
| `style.size` | number | `12` |
| `style.color` | RGB | `[0,0,0]` |
| `style.align` | string | `"left"` |
| `style.underline` | boolean | `false` |
| `style.lineHeight` | number | `1.4` |

#### `image`
| Proprietà | Tipo | Default |
|---|---|---|
| `src` | string | `""` |
| `fit` | string | `"contain"` |

Valori `fit`: `contain`, `cover`, `stretch`

#### `list`
| Proprietà | Tipo | Default |
|---|---|---|
| `items` | array | `[]` |
| `mode` | string | `"static"` |
| `dynamicConfig.repeatField` | string | `""` |
| `dynamicConfig.textField` | string | `"text"` |
| `style.bullet` | string | `"circle"` |
| `style.bullets` | array | `[]` |
| `style.bulletIndent` | number | `5` |
| `style.textIndent` | number | `15` |

Valori `bullet`: `circle`, `square`, `dash`, `diamond`, `arrow`, `number`

#### `rectangle`
| Proprietà | Tipo | Default |
|---|---|---|
| `fill` | RGB | `[]` |
| `stroke` | RGB | `[]` |
| `strokeWidth` | number | `1` |

#### `line`
| Proprietà | Tipo | Default |
|---|---|---|
| `x2` | number | `null` |
| `y2` | number | `null` |
| `color` | RGB | `[0,0,0]` |
| `lineWidth` | number | `1` |

#### `table`
| Proprietà | Tipo | Default |
|---|---|---|
| `name` | string | `""` |
| `columns` | array | `[]` |
| `rows` | array | `[]` |
| `repeatHeader` | boolean | `true` |
| `headerStyle` | object | `{}` |
| `cellStyle` | object | `{}` |
| `borderColor` | RGB | `[0,0,0]` |
| `borderWidth` | number | `0.5` |
| `mode` | string | `"static"` |
| `dynamicConfig` | object | `null` |

#### `ellipse`
| Proprietà | Tipo | Default |
|---|---|---|
| `fill` | RGB | `[0.9,0.9,0.9]` |
| `stroke` | RGB | `[0,0,0]` |
| `strokeWidth` | number | `0.5` |

#### `divider`
| Proprietà | Tipo | Default |
|---|---|---|
| `color` | RGB | `[0,0,0]` |
| `lineWidth` | number | `0.5` |
| `lineStyle` | string | `"solid"` |

Valori `lineStyle`: `solid`, `dashed`, `dotted`

#### `signature`
| Proprietà | Tipo | Default |
|---|---|---|
| `label` | string | `"Firma"` |
| `color` | RGB | `[0,0,0]` |

#### `container`
| Proprietà | Tipo | Default |
|---|---|---|
| `fill` | RGB | `[1,1,1]` |
| `borderColor` | RGB | `[0,0,0]` |
| `borderWidth` | number | `0.5` |

#### `pageNumber`
| Proprietà | Tipo | Default |
|---|---|---|
| `style` | TextStyle | `{ font: "helvetica", size: 10, align: "center" }` |

#### `date`
| Proprietà | Tipo | Default |
|---|---|---|
| `format` | string | `"d/m/Y"` |
| `style` | TextStyle | `{ font: "helvetica", size: 10 }` |

#### `watermark`
| Proprietà | Tipo | Default |
|---|---|---|
| `text` | string | `"BOZZA"` |
| `fontSize` | number | `48` |
| `color` | RGB | `[0.8,0.8,0.8]` |
| `rotation` | number | `-45` |
| `opacity` | number | `0.3` |

#### `qrcode`
| Proprietà | Tipo | Default |
|---|---|---|
| `text` | string | `""` |

#### `stamp`
| Proprietà | Tipo | Default |
|---|---|---|
| `preset` | string | `"approved"` |
| `color` | RGB | _(dal preset)_ |
| `text` | string | _(dal preset)_ |

Valori `preset`: `approved`, `confidential`, `draft`, `paid`, `urgent`

#### `quote`
| Proprietà | Tipo | Default |
|---|---|---|
| `text` | string | `""` |
| `author` | string | `""` |
| `barColor` | RGB | `[0.5,0.5,0.5]` |
| `style` | TextStyle | `{ font: "times", style: "italic", size: 12 }` |

#### `callout`
| Proprietà | Tipo | Default |
|---|---|---|
| `text` | string | `""` |
| `style` | string | `"info"` |
| `icon` | string | `"info"` |
| `bgColor` | RGB | `[0.9,0.95,1]` |
| `borderColor` | RGB | `[0.2,0.4,0.8]` |

Valori `style`: `info`, `warning`, `error`, `success`

#### `codeBlock`
| Proprietà | Tipo | Default |
|---|---|---|
| `text` | string | `""` |
| `language` | string | `""` |

#### `progressBar`
| Proprietà | Tipo | Default |
|---|---|---|
| `value` | number | `0` |
| `color` | RGB | `[0.2,0.6,0.9]` |
| `bgColor` | RGB | `[0.9,0.9,0.9]` |
| `label` | string | `""` |

#### `icon`
| Proprietà | Tipo | Default |
|---|---|---|
| `name` | string | `"check"` |
| `color` | RGB | `[0,0.6,0]` |

Valori `name`: `check`, `warning`, `info`, `error`, `star`, `heart`, `arrow`

#### `barcode`
| Proprietà | Tipo | Default |
|---|---|---|
| `text` | string | `""` |
| `format` | string | `"code128"` |
| `showText` | boolean | `true` |

Valori `format`: `code128`, `code39`, `ean13`

#### `chart`
| Proprietà | Tipo | Default |
|---|---|---|
| `chartType` | string | `"bar"` |
| `data` | array | `[]` |
| `colors` | RGB[] | `[[0.2,0.4,0.8]]` |

Valori `chartType`: `bar`, `pie`, `line`

#### `checklist`
| Proprietà | Tipo | Default |
|---|---|---|
| `items` | array | `[]` |
| `size` | number | `11` |
| `color` | RGB | `[0,0,0]` |
| `checkedColor` | RGB | `[0,0.6,0]` |
| `gap` | number | `3` |

#### `radio`
| Proprietà | Tipo | Default |
|---|---|---|
| `items` | array | `[]` |
| `size` | number | `11` |
| `color` | RGB | `[0,0,0]` |
| `selectedColor` | RGB | `[0.2,0.4,0.8]` |
| `gap` | number | `3` |

#### `pageBreak` / `spacer`
Nessuna proprietà specifica. Sono marcatori logici senza rendering visibile.

#### `group`
| Proprietà | Tipo | Default |
|---|---|---|
| `children` | array | `[]` |

I children sono elementi annidati con coordinate relative al gruppo.

---

## Operatori Condizionali (showIf / styleIf)

| `op` | Descrizione | Tipo confronto |
|---|---|---|
| `eq` | Uguale | string |
| `neq` | Diverso | string |
| `gt` | Maggiore | float |
| `lt` | Minore | float |
| `gte` | Maggiore o uguale | float |
| `lte` | Minore o uguale | float |
| `empty` | Vuoto/null/[] | — |
| `notempty` | Non vuoto | — |
| `contains` | Contiene sottostringa | string |

---

## Colori RGB

Tutti i colori sono array RGB con valori float da `0` a `1`:

| Colore | RGB |
|---|---|
| Nero | `[0, 0, 0]` |
| Bianco | `[1, 1, 1]` |
| Rosso | `[0.8, 0.1, 0.1]` |
| Verde | `[0, 0.6, 0]` |
| Blu | `[0.2, 0.4, 0.8]` |
| Grigio | `[0.5, 0.5, 0.5]` |
| Giallo | `[1, 0.8, 0]` |

---

## Note Importanti

1. **Coordinate**: Tutte le coordinate sono in millimetri, origine in alto a sinistra
2. **ID**: Ogni `id` deve essere un UUID univoco nell'ambito del template
3. **pageId**: Ogni elemento deve appartenere a una pagina esistente
4. **Font**: I nomi font sono case-insensitive; gli alias vengono risolti automaticamente
5. **Weight**: I valori `"600"`, `"700"`, `"800"`, `"900"` vengono normalizzati in `"bold"`
6. **Table dinamica**: Quando `name` è impostato e i dati esistono nel `data`, le rows vengono generate automaticamente
7. **showIf/styleIf**: Se `field` non esiste nei data, la condizione restituisce `null` (falsa)
8. **repeatOnAllPages**: Supportato da: stamp, watermark, text, image, rectangle, line, ellipse, divider
