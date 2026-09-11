# engiPDF

Web editor + pure PHP PDF generator for templates.

## Requirements

- PHP >= 8.2
- Extensions: `zlib`, `mbstring`, `gd`

## Installation

```bash
composer require durcap2011/engipdf
```

## Usage

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

## Structure

```
engipdf/
├── src/                    # PHP engine (PDF generator)
│   ├── PdfWriter/          # Binary PDF writer
│   ├── Font/               # Type1 and TTF font handling
│   ├── Renderer/           # Template → PDF renderer
│   └── Template/           # Loader and placeholder resolver
├── editor/                 # Vue 3 web editor (source)
│   └── dist/               # Static editor build
├── templates/              # Example JSON templates
├── examples/               # Usage examples
└── docs/                   # Full documentation
```

## Editor

The editor is a Vue 3 app that runs entirely in the browser. To start it locally:

```bash
cd editor
npm install
npm run dev
```

Opens at `http://localhost:5173`.

To generate a production build:

```bash
npm run build
```

Static files are generated in `editor/dist/`.

Open the *HOST_SERVER*/*ROOT_DIR*/vendor/durcap2011/engipdf/editor/dist/ path to open the editor.
Modify the *HOST_SERVER*/*ROOT_DIR*/vendor/durcap2011/engipdf/editor/dist/index.html file by replacing lines 10 and 11, respectively, with:
```html
<script type="module" crossorigin src="./assets/index-ChsEoyqH.js"></script>
<link rel="stylesheet" crossorigin href="./assets/index-D9zxqf0v.css" />
```


## Documentation

- [User guide](docs/guide.md)
- [Components guide](docs/components-guide.md)
- [JSON template reference](docs/template.md)
- [Technical documentation](docs/technical_documentation.md)

## Components

28 components available in the editor: text, table, rectangle, line, list, image, QR code, barcode, citation, callout, code block, checklist, radio, ellipse, divider, stamp, watermark, signature, progress bar, chart, icon, group, container, page break, page number, date, data repeat, spacer.

## Running Tests

```bash
composer install
./vendor/bin/phpunit
```

## License

MIT — see [LICENSE](LICENSE).
