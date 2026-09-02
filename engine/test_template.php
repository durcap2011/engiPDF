<?php
declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use EngiPDF\Renderer\PdfRenderer;
use EngiPDF\Template\TemplateLoader;

$loader = new TemplateLoader();
$renderer = new PdfRenderer();

// Carica template
$template = $loader->loadFromFile(__DIR__ . '/../templates/fattura.json');

// Dati di esempio
$data = [
    'fattura' => [
        'numero' => '2026/001',
        'data' => '02/09/2026',
    ],
    'cliente' => [
        'nome' => 'Mario',
        'cognome' => 'Rossi',
        'indirizzo' => 'Via Roma 10, Milano',
    ],
    'azienda' => [
        'nome' => 'engiPDF S.r.l.',
        'indirizzo' => 'Via Dante 5, Torino',
    ],
    'totale' => '1.400,00',
];

// Renderizza
$pdf = $renderer->render($template, $data);

$outputPath = __DIR__ . '/test_fattura.pdf';
file_put_contents($outputPath, $pdf);

echo "Fattura PDF generata: $outputPath\n";
echo "Dimensione: " . strlen($pdf) . " byte\n";
