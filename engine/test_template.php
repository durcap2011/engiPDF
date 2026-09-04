<?php
declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use EngiPDF\Renderer\PdfRenderer;
use EngiPDF\Template\TemplateLoader;

$loader = new TemplateLoader();
$renderer = new PdfRenderer();

// Carica template
$template = $loader->loadFromFile(__DIR__ . '/../templates/template_tabella.json');

// Dati di esempio
$data = [
    'nome' => 'Azienda XYZ S.r.l.',
    'tabella' => [
        ['Laptop ASUS', '2', '899.00', '1798.00'],
        ['Mouse Logitech', '5', '29.90', '149.50'],
        ['Tastiera Meccanica', '3', '79.00', '237.00'],
        ['Monitor 27"', '1', '349.00', '349.00'],
        ['Laptop ASUS', '2', '899.00', '1798.00'],
        ['Mouse Logitech', '5', '29.90', '149.50'],
        ['Tastiera Meccanica', '3', '79.00', '237.00'],
        ['Monitor 27"', '1', '349.00', '349.00'],
        ['Laptop ASUS', '2', '899.00', '1798.00'],
        ['Mouse Logitech', '5', '29.90', '149.50'],
        ['Tastiera Meccanica', '3', '79.00', '237.00'],
        ['Monitor 27"', '1', '349.00', '349.00'],
        ['Laptop ASUS', '2', '899.00', '1798.00'],
        ['Mouse Logitech', '5', '29.90', '149.50'],
        ['Tastiera Meccanica', '3', '79.00', '237.00'],
        ['Monitor 27"', '1', '349.00', '349.00'],
        ['Laptop ASUS', '2', '899.00', '1798.00'],
        ['Mouse Logitech', '5', '29.90', '149.50'],
        ['Tastiera Meccanica', '3', '79.00', '237.00'],
        ['Monitor 27"', '1', '349.00', '349.00'],
        ['Laptop ASUS', '2', '899.00', '1798.00'],
        ['Mouse Logitech', '5', '29.90', '149.50'],
        ['Tastiera Meccanica', '3', '79.00', '237.00'],
        ['Monitor 27"', '1', '349.00', '349.00'],
        ['Laptop ASUS', '2', '899.00', '1798.00'],
        ['Mouse Logitech', '5', '29.90', '149.50'],
        ['Tastiera Meccanica', '3', '79.00', '237.00'],
        ['Monitor 27"', '1', '349.00', '349.00'],
        ['Laptop ASUS', '2', '899.00', '1798.00'],
        ['Mouse Logitech', '5', '29.90', '149.50'],
        ['Tastiera Meccanica', '3', '79.00', '237.00'],
        ['Monitor 27"', '1', '349.00', '349.00'],
        ['Laptop ASUS', '2', '899.00', '1798.00'],
        ['Mouse Logitech', '5', '29.90', '149.50'],
        ['Tastiera Meccanica', '3', '79.00', '237.00'],
        ['Monitor 27"', '1', '349.00', '349.00'],
        ['Laptop ASUS', '2', '899.00', '1798.00'],
        ['Mouse Logitech', '5', '29.90', '149.50'],
        ['Tastiera Meccanica', '3', '79.00', '237.00'],
        ['Monitor 27"', '1', '349.00', '349.00'],
        ['Laptop ASUS', '2', '899.00', '1798.00'],
        ['Mouse Logitech', '5', '29.90', '149.50'],
        ['Tastiera Meccanica', '3', '79.00', '237.00'],
        ['Monitor 27"', '1', '349.00', '349.00'],
        ['Laptop ASUS', '2', '899.00', '1798.00'],
        ['Mouse Logitech', '5', '29.90', '149.50'],
        ['Tastiera Meccanica', '3', '79.00', '237.00'],
        ['Monitor 27"', '1', '349.00', '349.00'],
        ['Laptop ASUS', '2', '899.00', '1798.00'],
        ['Mouse Logitech', '5', '29.90', '149.50'],
        ['Tastiera Meccanica', '3', '79.00', '237.00'],
        ['Monitor 27"', '1', '349.00', '349.00'],
    ],
];

// Renderizza
$pdf = $renderer->render($template, $data);

$outputPath = __DIR__ . '/test_template.pdf';
file_put_contents($outputPath, $pdf);

echo "Fattura PDF generata: $outputPath\n";
echo "Dimensione: " . strlen($pdf) . " byte\n";
