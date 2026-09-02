<?php
declare(strict_types=1);

namespace EngiPDF\Font;

class FontManager
{
    /** @var array<string, array<string, string>> Mappa famiglia → varianti → nome Type1 */
    private const TYPE1_MAP = [
        'helvetica' => [
            'normal'  => 'Helvetica',
            'bold'    => 'Helvetica-Bold',
            'italic'  => 'Helvetica-Oblique',
            'bold-italic' => 'Helvetica-BoldOblique',
        ],
        'courier' => [
            'normal'  => 'Courier',
            'bold'    => 'Courier-Bold',
            'italic'  => 'Courier-Oblique',
            'bold-italic' => 'Courier-BoldOblique',
        ],
        'times' => [
            'normal'  => 'Times-Roman',
            'bold'    => 'Times-Bold',
            'italic'  => 'Times-Italic',
            'bold-italic' => 'Times-BoldItalic',
        ],
        'symbol' => [
            'normal'  => 'Symbol',
        ],
        'zapfdingbats' => [
            'normal'  => 'ZapfDingbats',
        ],
    ];

    /** @var array<string, string> Alias → famiglia canonica */
    private const ALIASES = [
        'arial'       => 'helvetica',
        'helv'        => 'helvetica',
        'sans-serif'  => 'helvetica',
        'mono'        => 'courier',
        'monospace'   => 'courier',
        'courier-new' => 'courier',
        'serif'       => 'times',
        'times-new-roman' => 'times',
        'georgia'     => 'times',
        'bookman'     => 'times',
    ];

    /** @var array<string, array{file: string, baseFont: string}> Font TTF registrati */
    private array $ttfFonts = [];

    /** @var array<string, array{objNum: int}> Oggetti PDF già allocati per ogni font */
    private array $pdfFontObjects = [];

    private int $nextObjNum;

    public function __construct(int $startingObjNum = 100)
    {
        $this->nextObjNum = $startingObjNum;
    }

    /**
     * Normalizza il nome della famiglia (lowercase, trim, risolve alias).
     */
    public function normalizeFamily(string $family): string
    {
        $lower = strtolower(trim($family));
        return self::ALIASES[$lower] ?? $lower;
    }

    /**
     * Risolve family + weight → nome esatto del font Type1 PDF.
     * es. ('helvetica', 'bold') → 'Helvetica-Bold'
     */
    public function resolveType1(string $family, string $weight = 'normal', string $style = 'normal'): string
    {
        $family = $this->normalizeFamily($family);

        // Determina la variante
        $isBold   = in_array($weight, ['bold', '600', '700', '800', '900'], true);
        $isItalic = in_array($style, ['italic', 'oblique'], true)
                 || in_array($weight, ['italic', 'oblique'], true); // weight può contenere anche lo style

        if ($isBold && $isItalic) $variant = 'bold-italic';
        elseif ($isBold)          $variant = 'bold';
        elseif ($isItalic)        $variant = 'italic';
        else                      $variant = 'normal';

        // Cerca nella mappa Type1
        if (isset(self::TYPE1_MAP[$family][$variant])) {
            return self::TYPE1_MAP[$family][$variant];
        }
        // Fallback: prima variante disponibile
        if (isset(self::TYPE1_MAP[$family])) {
            return reset(self::TYPE1_MAP[$family]);
        }
        // Fallback totale
        return 'Helvetica';
    }

    /**
     * Registra un font TTF per embedding nel PDF.
     */
    public function registerTtf(string $family, string $filePath, string $weight = 'normal', string $style = 'normal'): void
    {
        $family = $this->normalizeFamily($family);
        $key = "$family:$weight:$style";

        $this->ttfFonts[$key] = [
            'file'     => $filePath,
            'baseFont' => $family . '-' . $weight . $style,
        ];
    }

    /**
     * Restituisce true se il font è TTF registrato.
     */
    public function isTtf(string $family, string $weight = 'normal', string $style = 'normal'): bool
    {
        $family = $this->normalizeFamily($family);
        return isset($this->ttfFonts["$family:$weight:$style"]);
    }

    /**
     * Restituisce i dati TTF per un font registrato.
     */
    public function getTtfData(string $family, string $weight = 'normal', string $style = 'normal'): ?array
    {
        $family = $this->normalizeFamily($family);
        return $this->ttfFonts["$family:$weight:$style"] ?? null;
    }

    /**
     * Registra un oggetto PDF per un font e restituisce il numero oggetto.
     */
    public function allocatePdfObject(string $fontName): int
    {
        if (!isset($this->pdfFontObjects[$fontName])) {
            $this->pdfFontObjects[$fontName] = [
                'objNum' => $this->nextObjNum++,
            ];
        }
        return $this->pdfFontObjects[$fontName]['objNum'];
    }

    /**
     * Restituisce il numero oggetto allocato per un font (-1 se non allocato).
     */
    public function getPdfObjectNum(string $fontName): int
    {
        return $this->pdfFontObjects[$fontName]['objNum'] ?? -1;
    }

    /**
     * Restituisce tutti gli oggetti PDF font allocati.
     * @return array<string, array{objNum: int}>
     */
    public function getAllPdfObjects(): array
    {
        return $this->pdfFontObjects;
    }

    /**
     * Restituisce tutti i nomi Type1 usati (per generare le righe /F? ...).
     * @return array<string, string>
     */
    public function getUsedFonts(): array
    {
        $result = [];
        foreach ($this->pdfFontObjects as $fontName => $info) {
            $result[$fontName] = $info['objNum'];
        }
        return $result;
    }

    /**
     * Elenco completo dei font Type1 disponibili (per UI/autocomplete).
     * @return array<string, array{family: string, variants: string[]}>
     */
    public static function getAvailableFonts(): array
    {
        $fonts = [];
        foreach (self::TYPE1_MAP as $family => $variants) {
            $fonts[$family] = [
                'family'   => $family,
                'variants' => array_keys($variants),
            ];
        }
        return $fonts;
    }

    /**
     * Prossimo numero oggetto disponibile.
     */
    public function getNextObjNum(): int
    {
        return $this->nextObjNum;
    }

    public function setNextObjNum(int $num): void
    {
        $this->nextObjNum = $num;
    }
}
