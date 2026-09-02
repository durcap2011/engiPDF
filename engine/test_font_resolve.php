<?php
declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use EngiPDF\Font\FontManager;

$fm = new FontManager();

$tests = [
    ['helvetica', 'normal', 'normal'],
    ['helvetica', 'bold',   'normal'],
    ['helvetica', 'normal', 'italic'],
    ['helvetica', 'bold',   'italic'],
    ['times',     'normal', 'normal'],
    ['times',     'bold',   'normal'],
    ['times',     'normal', 'italic'],
    ['times',     'bold',   'italic'],
    ['courier',   'normal', 'normal'],
    ['courier',   'bold',   'normal'],
    ['courier',   'normal', 'italic'],
    ['courier',   'bold',   'italic'],
    ['arial',     'normal', 'normal'],
    ['arial',     'bold',   'normal'],
];

foreach ($tests as [$family, $weight, $style]) {
    $result = $fm->resolveType1($family, $weight, $style);
    echo "$family + $weight + $style => $result\n";
}
