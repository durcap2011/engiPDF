<?php
declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use EngiPDF\Renderer\PdfRenderer;

$template = [
    'version' => 1,
    'name' => 'Test Lista Annidata',
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
            'text' => 'Test Lista Annidata con Bullet Personalizzati',
            'style' => [
                'font' => 'helvetica', 'weight' => 'bold', 'size' => 18,
                'color' => [0, 0, 0], 'align' => 'left',
            ],
        ],
        [
            'id' => 'lista',
            'type' => 'list',
            'x' => 20, 'y' => 40, 'width' => 170, 'height' => 100,
            'mode' => 'static',
            'style' => [
                'font' => 'helvetica', 'weight' => 'normal', 'size' => 12,
                'color' => [0, 0, 0], 'align' => 'left',
                'bullet' => 'circle',
                'bullets' => ['circle', 'square', 'diamond'],
                'bulletIndent' => 5,
                'textIndent' => 15,
                'lineHeight' => 1.6,
            ],
            'items' => [
                ['text' => 'Capitolo 1', 'items' => [
                    ['text' => 'Sotto-voce 1.1', 'items' => [
                        ['text' => 'Dettaglio 1.1.1'],
                        ['text' => 'Dettaglio 1.1.2'],
                    ]],
                    ['text' => 'Sotto-voce 1.2'],
                ]],
                ['text' => 'Capitolo 2', 'items' => [
                    ['text' => 'Sotto-voce 2.1'],
                    ['text' => 'Sotto-voce 2.2'],
                ]],
                ['text' => 'Capitolo 3'],
            ],
        ],
    ],
];

$renderer = new PdfRenderer();
$pdf = $renderer->render($template);

$outputPath = __DIR__ . '/test_lista_annidata_v2.pdf';
file_put_contents($outputPath, $pdf);

echo "PDF lista annidata generato: $outputPath\n";
echo "Dimensione: " . strlen($pdf) . " byte\n";
