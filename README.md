# engiPDF

Editor web + Generatore PDF puro PHP per template.

## Struttura

```
engiPDF/
├── editor/          # Frontend Vue 3 + TypeScript
├── engine/          # Backend PHP puro (nessuna libreria esterna)
└── templates/       # Template JSON di esempio
```

## Quick Start

### Editor Web

```bash
cd editor
npm install
npm run dev
```

L'editor si avvia su `http://localhost:5173`.

### Generatore PDF

```bash
cd engine
composer install
php test_hello.php      # Test Hello World
php test_template.php   # Test fattura da template
```

## Componenti Supportati

| Componente | Tipo | Descrizione |
|------------|------|-------------|
| Testo | `text` | Blocco di testo con font, colore, allineamento |
| Rettangolo | `rectangle` | Forma con riempimento e bordo |
| Linea | `line` | Linea retta con spessore e colore |
| Lista | `list` | Lista puntata/numerata (statica o dinamica) |
| Immagine | `image` | Immagine JPEG (in fase di implementazione) |

## JSON Template

```json
{
  "version": 1,
  "name": "Mio Template",
  "page": {
    "width": 210,
    "height": 297,
    "unit": "mm"
  },
  "elements": [
    {
      "id": "title",
      "type": "text",
      "x": 20, "y": 20,
      "width": 170, "height": 15,
      "text": "FATTURA {{ fattura.numero }}",
      "style": {
        "font": "helvetica",
        "weight": "bold",
        "size": 24,
        "color": [0, 0, 0],
        "align": "center"
      }
    }
  ]
}
```

## Placeholder

```
{{ variabile }}                    → sostituzione semplice
{{ oggetto.proprieta }}            → accesso annidato
{{ valore | currency }}            → formattato come valuta
{{ data | date:"d/m/Y" }}         → formattato come data
{{ numero | number:2 }}           → formattato come numero
```

## Genera PDF da PHP

```php
use EngiPDF\Renderer\PdfRenderer;
use EngiPDF\Template\TemplateLoader;

$loader = new TemplateLoader();
$renderer = new PdfRenderer();

$template = $loader->loadFromFile('templates/fattura.json');

$data = [
    'fattura' => ['numero' => '2026/001'],
    'cliente' => ['nome' => 'Mario', 'cognome' => 'Rossi'],
];

$pdf = $renderer->render($template, $data);
file_put_contents('output.pdf', $pdf);
```

## Shortcut Tasto

| Combinazione | Azione |
|--------------|--------|
| `Ctrl+Z` | Undo |
| `Ctrl+Y` / `Ctrl+Shift+Z` | Redo |
| `Ctrl+D` | Duplica elemento |
| `Canc` | Elimina elemento |

## Tecnologie

- **Frontend**: Vue 3, TypeScript, Vite, Pinia
- **Backend**: PHP 8.2+ puro, zero librerie esterne
- **PDF**: Generatore nativo (Header → Body → Xref → Trailer)
