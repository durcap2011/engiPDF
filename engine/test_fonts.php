<?php
declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use EngiPDF\Renderer\PdfRenderer;

$template = [
    'version' => 1,
    'name' => 'Test Font',
    'page' => [
        'width' => 210,
        'height' => 297,
        'unit' => 'mm',
        'margins' => ['top' => 20, 'right' => 20, 'bottom' => 20, 'left' => 20],
    ],
    'defaultFont' => 'helvetica',
    'elements' => [],
];

$fonts = [
    ['family' => 'helvetica', 'weight' => 'normal',  'label' => 'Helvetica Normal'],
    ['family' => 'helvetica', 'weight' => 'bold',    'label' => 'Helvetica Bold'],
    ['family' => 'helvetica', 'weight' => 'italic',  'label' => 'Helvetica Italic'],
    ['family' => 'times',     'weight' => 'normal',  'label' => 'Times Roman'],
    ['family' => 'times',     'weight' => 'bold',    'label' => 'Times Bold'],
    ['family' => 'times',     'weight' => 'italic',  'label' => 'Times Italic'],
    ['family' => 'courier',   'weight' => 'normal',  'label' => 'Courier Normal'],
    ['family' => 'courier',   'weight' => 'bold',    'label' => 'Courier Bold'],
    ['family' => 'courier',   'weight' => 'italic',  'label' => 'Courier Italic'],
    // Alias
    ['family' => 'arial',     'weight' => 'normal',  'label' => 'Arial → Helvetica'],
    ['family' => 'Arial',     'weight' => 'bold',    'label' => 'ARIAL BOLD → Helvetica-Bold'],
    ['family' => 'sans-serif','weight' => 'normal',  'label' => 'sans-serif → Helvetica'],
    ['family' => 'serif',     'weight' => 'normal',  'label' => 'serif → Times'],
    ['family' => 'monospace', 'weight' => 'normal',  'label' => 'monospace → Courier'],
];

$y = 25;
foreach ($fonts as $i => $f) {
    $template['elements'][] = [
        'id' => "font_$i",
        'type' => 'text',
        'x' => 20, 'y' => $y, 'width' => 170, 'height' => 8,
        'text' => $f['label'] . ' — Hello World 0123',
        'style' => [
            'font'   => $f['family'],
            'weight' => $f['weight'],
            'size'   => 12,
            'color'  => [0, 0, 0],
            'align'  => 'left',
        ],
    ];
    $y += 10;
}

$renderer = new PdfRenderer();
$pdf = $renderer->render($template);

$outputPath = __DIR__ . '/test_fonts.pdf';
file_put_contents($outputPath, $pdf);

echo "PDF font test generato: $outputPath\n";
echo "Dimensione: " . strlen($pdf) . " byte\n";
