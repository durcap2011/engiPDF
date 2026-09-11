# engiPDF

Editor web + Generatore PDF puro PHP per template.

## Requisiti

- PHP ≥ 8.2
- Estensioni: `zlib`, `mbstring`, `gd`

## Installazione

```bash
composer require durcap2011/engipdf
```

## Utilizzo

```php
<?php

require 'vendor/autoload.php';

use EngiPDF\Renderer\PdfRenderer;
use EngiPDF\Template\TemplateLoader;

$loader = new TemplateLoader();
$renderer = new PdfRenderer();

$template = $loader->loadFromFile('templates/fattura.json');

$data = [
    'fattura' => ['numero' => '2026/001'],
    'cliente' => ['nome' => 'Mario Rossi'],
];

$pdf = $renderer->render($template, $data);
file_put_contents('output.pdf', $pdf);
```

## Struttura

```
engipdf/
├── src/                    # Motore PHP (PDF generator)
│   ├── PdfWriter/          # Scrittura binaria PDF
│   ├── Font/               # Gestione font Type1 e TTF
│   ├── Renderer/           # Renderizzatore template → PDF
│   └── Template/           # Loader e risolutore placeholder
├── editor/                 # Editor web Vue 3 (sorgente)
│   └── dist/               # Build statico dell'editor
├── templates/              # Template JSON di esempio
├── examples/               # Esempi d'uso
└── docs/                   # Documentazione completa
```

## Editor

L'editor è un'app Vue 3 che funziona interamente nel browser. Per avviarlo in locale:

```bash
cd editor
npm install
npm run dev
```

Si apre su `http://localhost:5173`.

Per generare il build di produzione:

```bash
npm run build
```

I file statici vengono generati in `editor/dist/`.

## Documentazione

- [Guida utente](docs/guide.md)
- [Guida componenti](docs/components-guide.md)
- [Riferimento template JSON](docs/template.md)
- [Documentazione tecnica](docs/technical_documentation.md)

## Componenti

28 componenti disponibili nell'editor: testo, tabella, rettangolo, linea, lista, immagine, QR code, barcode, citazione, callout, blocco codice, checklist, radio, ellisse, divisore, timbro, filigrana, firma, barra di progresso, grafico, icona, gruppo, contenitore, taglio pagina, numero pagina, data, ripeti dati, spaziatore.

## Esecuzione test

```bash
composer install
./vendor/bin/phpunit
```

## Licenza

MIT — vedi [LICENSE](LICENSE).
