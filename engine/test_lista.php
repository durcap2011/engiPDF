<?php
declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use EngiPDF\Renderer\PdfRenderer;
use EngiPDF\Template\TemplateLoader;

$template = [
    'version' => 1,
    'name' => 'Test Lista',
    'page' => [
        'width' => 210,
        'height' => 297,
        'unit' => 'mm',
        'margins' => ['top' => 20, 'right' => 20, 'bottom' => 20, 'left' => 20],
    ],
    'defaultFont' => 'helvetica',
    'elements' => [
        [
            'id' => 'title',
            'type' => 'text',
            'x' => 20, 'y' => 20, 'width' => 170, 'height' => 12,
            'text' => 'Test Lista Puntata',
            'style' => [
                'font' => 'helvetica', 'weight' => 'bold', 'size' => 20,
                'color' => [0, 0, 0], 'align' => 'left',
            ],
        ],
        [
            'id' => 'lista1',
            'type' => 'list',
            'x' => 20, 'y' => 40, 'width' => 170, 'height' => 60,
            'mode' => 'static',
            'style' => [
                'font' => 'helvetica', 'weight' => 'normal', 'size' => 12,
                'color' => [0, 0, 0], 'align' => 'left',
                'bullet' => '•', 'bulletIndent' => 5, 'textIndent' => 15,
                'lineHeight' => 1.6,
            ],
            'items' => [
                ['text' => 'Primo elemento della lista'],
                ['text' => 'Secondo elemento della lista'],
                ['text' => 'Terzo elemento della lista'],
                ['text' => 'Quarto elemento con testo piu lungo per verificare il word wrap'],
            ],
        ],
        [
            'id' => 'subtitle',
            'type' => 'text',
            'x' => 20, 'y' => 110, 'width' => 170, 'height' => 10,
            'text' => 'Test Lista Dinamica',
            'style' => [
                'font' => 'helvetica', 'weight' => 'bold', 'size' => 16,
                'color' => [0, 0, 0], 'align' => 'left',
            ],
        ],
        [
            'id' => 'lista2',
            'type' => 'list',
            'x' => 20, 'y' => 125, 'width' => 170, 'height' => 60,
            'mode' => 'dynamic',
            'dynamicConfig' => [
                'repeatField' => 'prodotti',
                'textField' => 'descrizione',
            ],
            'style' => [
                'font' => 'helvetica', 'weight' => 'normal', 'size' => 11,
                'color' => [0, 0, 0], 'align' => 'left',
                'bullet' => '-', 'bulletIndent' => 5, 'textIndent' => 15,
                'lineHeight' => 1.5,
            ],
            'items' => [],
        ],
    ],
];

$data = [
    'prodotti' => [
        ['descrizione' => 'Prodotto A - € 100,00'],
        ['descrizione' => 'Prodotto B - € 250,00'],
        ['descrizione' => 'Prodotto C - € 50,00'],
    ],
];

$renderer = new PdfRenderer();
$pdf = $renderer->render($template, $data);

$outputPath = __DIR__ . '/test_lista.pdf';
file_put_contents($outputPath, $pdf);

echo "PDF lista generato: $outputPath\n";
echo "Dimensione: " . strlen($pdf) . " byte\n";
