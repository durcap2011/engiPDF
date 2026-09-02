<?php
declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use EngiPDF\PdfWriter\PdfDocument;

// Test 1: PDF Hello World
$doc = new PdfDocument(595.28, 841.89); // A4
$page = $doc->createPage();

$page->text(100, 750, 'Hello World!', 'helvetica', 36, [0, 0, 0]);
$page->text(100, 700, 'engiPDF - Generatore PDF Puro PHP', 'helvetica', 18, [0.2, 0.2, 0.6]);
$page->text(100, 660, 'Nessuna libreria esterna. Solo PHP puro.', 'helvetica', 12, [0.4, 0.4, 0.4]);

// Rettangolo
$page->rectangle(100, 600, 200, 40, [0.9, 0.9, 1.0], [0, 0, 0.5], 0.5);

// Linea
$page->line(100, 580, 400, 580, [0, 0, 0], 0.5);

// Testo dentro il rettangolo
$page->text(110, 612, 'Rettangolo di test', 'helvetica', 14, [0, 0, 0.5]);

$output = $doc->render();
file_put_contents(__DIR__ . '/test_hello.pdf', $output);

echo "PDF generato: " . __DIR__ . '/test_hello.pdf' . "\n";
echo "Dimensione: " . strlen($output) . " byte\n";
