<?php
declare(strict_types=1);

namespace EngiPDF\PdfWriter;

use EngiPDF\Font\FontManager;

class PdfDocument
{
    private array $pages = [];
    private array $pageDimensions = [];
    private array $objects = [];
    private array $images = [];
    private float $widthPt;
    private float $heightPt;
    private FontManager $fontManager;

    public function __construct(
        float $widthPt = 595.28,
        float $heightPt = 841.89,
        ?FontManager $fontManager = null
    ) {
        $this->widthPt = $widthPt;
        $this->heightPt = $heightPt;
        $this->fontManager = $fontManager ?? new FontManager();
    }

    public function createPage(?float $widthPt = null, ?float $heightPt = null): PdfPage
    {
        $page = new PdfPage($this);
        $this->pages[] = $page;
        $this->pageDimensions[] = [
            'width' => $widthPt ?? $this->widthPt,
            'height' => $heightPt ?? $this->heightPt
        ];
        return $page;
    }

    public function getPageDimensions(int $index): array
    {
        return $this->pageDimensions[$index] ?? ['width' => $this->widthPt, 'height' => $this->heightPt];
    }

    public function registerFont(string $name): void
    {
        $this->fontManager->allocatePdfObject($name);
    }

    public function getFontManager(): FontManager
    {
        return $this->fontManager;
    }

    public function addObject(string $content): int
    {
        $num = $this->fontManager->getNextObjNum();
        $this->fontManager->setNextObjNum($num + 1);
        $this->objects[$num] = $content;
        return $num;
    }

    public function registerImage(string $name, string $jpegData): int
    {
        $num = $this->fontManager->getNextObjNum();
        $this->fontManager->setNextObjNum($num + 1);
        $this->images[$name] = ['objNum' => $num, 'data' => $jpegData];
        return $num;
    }

    public function getImages(): array
    {
        return $this->images;
    }

    public function getWidth(): float
    {
        return $this->widthPt;
    }

    public function getHeight(): float
    {
        return $this->heightPt;
    }

    public function render(): string
    {
        $pdf = "%PDF-1.7\n";
        $pdf .= "%\xE2\xE3\xCF\xD3\n";

        $offsets = [];

        // ---- Catalog (obj 1) ----
        $offsets[1] = strlen($pdf);
        $pdf .= "1 0 obj\n";
        $pdf .= "<< /Type /Catalog /Pages 2 0 R >>\n";
        $pdf .= "endobj\n";

        // ---- Pages (obj 2) ----
        $kids = [];
        for ($i = 0; $i < count($this->pages); $i++) {
            $kids[] = ($i + 3) . " 0 R";
        }
        $offsets[2] = strlen($pdf);
        $pdf .= "2 0 obj\n";
        $pdf .= "<< /Type /Pages /Kids [" . implode(' ', $kids) . "] /Count " . count($this->pages) . " >>\n";
        $pdf .= "endobj\n";

        // ---- Font objects ----
        // Type1 standard + TTF embedded
        $usedFonts = $this->fontManager->getUsedFonts();
        $fontObjNums = [];  // fontName → objNum per /Resources

        foreach ($usedFonts as $fontName => $objNum) {
            $fontObjNums[$fontName] = $objNum;

            if ($this->fontManager->isTtf($fontName)) {
                // TTF: Type0 + CIDFontType2 + FontDescriptor + FontFile2
                $this->renderTtfFont($pdf, $offsets, $fontName, $objNum);
            } else {
                // Type1 standard - parse "family:weight:style"
                $parts = explode(':', $fontName);
                $family = $parts[0] ?? 'helvetica';
                $weight = $parts[1] ?? 'normal';
                $style  = $parts[2] ?? 'normal';
                $baseFont = $this->fontManager->resolveType1($family, $weight, $style);
                $offsets[$objNum] = strlen($pdf);
                $pdf .= "$objNum 0 obj\n";
                $pdf .= "<< /Type /Font /Subtype /Type1 /BaseFont /$baseFont /Encoding /WinAnsiEncoding >>\n";
                $pdf .= "endobj\n";
            }
        }

        // ---- Page objects + Content streams ----
        $nextObj = $this->fontManager->getNextObjNum();
        foreach ($this->pages as $idx => $page) {
            $pageObjNum = $idx + 3;
            $contentObjNum = $nextObj + $idx;

            // Resources font
            $fontRes = '';
            foreach ($fontObjNums as $name => $objNum) {
                $fontRes .= "/F_" . preg_replace('/[^a-zA-Z0-9]/', '_', $name) . " $objNum 0 R ";
            }

            // Resources XObject (immagini)
            $xObjRes = '';
            foreach ($this->images as $imgName => $imgInfo) {
                $safeName = preg_replace('/[^a-zA-Z0-9]/', '_', $imgName);
                $xObjRes .= "/$safeName {$imgInfo['objNum']} 0 R ";
            }

            $resources = "<< /Font << $fontRes >>";
            if ($xObjRes !== '') {
                $resources .= " /XObject << $xObjRes >>";
            }
            $resources .= " >>";

            $dims = $this->getPageDimensions($idx);
            $offsets[$pageObjNum] = strlen($pdf);
            $pdf .= "$pageObjNum 0 obj\n";
            $pdf .= "<< /Type /Page /Parent 2 0 R\n";
            $pdf .= "   /MediaBox [0 0 {$dims['width']} {$dims['height']}]\n";
            $pdf .= "   /Resources $resources\n";
            $pdf .= "   /Contents $contentObjNum 0 R >>\n";
            $pdf .= "endobj\n";

            $stream = $page->buildStream();
            $offsets[$contentObjNum] = strlen($pdf);
            $pdf .= "$contentObjNum 0 obj\n";
            $pdf .= "<< /Length " . strlen($stream) . " >>\n";
            $pdf .= "stream\n";
            $pdf .= $stream;
            $pdf .= "\nendstream\n";
            $pdf .= "endobj\n";
        }

        // ---- Extra objects (images, etc.) ----
        foreach ($this->objects as $num => $content) {
            if (!isset($offsets[$num])) {
                $offsets[$num] = strlen($pdf);
                $pdf .= "$num 0 obj\n";
                $pdf .= $content;
                $pdf .= "\nendobj\n";
            }
        }

        // ---- Image XObjects ----
        foreach ($this->images as $imgName => $imgInfo) {
            $num = $imgInfo['objNum'];
            $data = $imgInfo['data'];
            $safeName = preg_replace('/[^a-zA-Z0-9]/', '_', $imgName);
            $offsets[$num] = strlen($pdf);
            $pdf .= "$num 0 obj\n";
            $pdf .= "<< /Type /XObject /Subtype /Image /Width 1 /Height 1 /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /DCTDecode /Length " . strlen($data) . " >>\n";
            $pdf .= "stream\n";
            $pdf .= $data;
            $pdf .= "\nendstream\n";
            $pdf .= "endobj\n";
        }

        // ---- Xref ----
        $maxObj = max(array_keys($offsets));
        $totalObjs = $maxObj + 1;

        $xrefOffset = strlen($pdf);
        $pdf .= "xref\n";
        $pdf .= "0 $totalObjs\n";
        $pdf .= "0000000000 65535 f \n";

        for ($i = 1; $i < $totalObjs; $i++) {
            if (isset($offsets[$i])) {
                $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
            } else {
                $pdf .= "0000000000 00000 f \n";
            }
        }

        // ---- Trailer ----
        $pdf .= "trailer\n";
        $pdf .= "<< /Size $totalObjs /Root 1 0 R >>\n";
        $pdf .= "startxref\n";
        $pdf .= "$xrefOffset\n";
        $pdf .= "%%EOF\n";

        return $pdf;
    }

    /**
     * Genera gli oggetti PDF per un font TTF embedded.
     * Struttura: Type0 → CIDFontType2 → FontDescriptor → FontFile2
     */
    private function renderTtfFont(string &$pdf, array &$offsets, string $fontName, int $type0Obj): void
    {
        $ttfData = $this->fontManager->getTtfData($fontName);
        if (!$ttfData) return;

        $file = $ttfData['file'];
        $basePdfFont = $ttfData['baseFont'];

        if (!file_exists($file)) {
            // Fallback a Helvetica se il file non esiste
            $offsets[$type0Obj] = strlen($pdf);
            $pdf .= "$type0Obj 0 obj\n";
            $pdf .= "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>\n";
            $pdf .= "endobj\n";
            return;
        }

        $ttfBytes = file_get_contents($file);
        $compressed = gzcompress($ttfBytes);

        $next = $this->fontManager->getNextObjNum();
        $fontFileObj  = $next;
        $descriptorObj = $next + 1;
        $cidFontObj   = $next + 2;
        $this->fontManager->setNextObjNum($next + 3);

        // Esparse TTF per FontBBox e Ascent/Descent (semplice: valori default)
        $fontBBox = [-500, -200, 1500, 1000];
        $ascent = 800;
        $descent = -200;

        // FontFile2 stream
        $offsets[$fontFileObj] = strlen($pdf);
        $pdf .= "$fontFileObj 0 obj\n";
        $pdf .= "<< /Length " . strlen($compressed);
        $pdf .= " /Length1 " . strlen($ttfBytes);
        $pdf .= " /Filter /FlateDecode >>\n";
        $pdf .= "stream\n";
        $pdf .= $compressed;
        $pdf .= "\nendstream\n";
        $pdf .= "endobj\n";

        // FontDescriptor
        $offsets[$descriptorObj] = strlen($pdf);
        $pdf .= "$descriptorObj 0 obj\n";
        $pdf .= "<< /Type /FontDescriptor\n";
        $pdf .= "   /FontName /$basePdfFont\n";
        $pdf .= "   /Flags 32\n";
        $pdf .= "   /FontBBox [" . implode(' ', $fontBBox) . "]\n";
        $pdf .= "   /ItalicAngle 0\n";
        $pdf .= "   /Ascent $ascent\n";
        $pdf .= "   /Descent $descent\n";
        $pdf .= "   /StemV 80\n";
        $pdf .= "   /FontFile2 $fontFileObj 0 R >>\n";
        $pdf .= "endobj\n";

        // CIDFontType2 (discendente)
        $offsets[$cidFontObj] = strlen($pdf);
        $pdf .= "$cidFontObj 0 obj\n";
        $pdf .= "<< /Type /Font\n";
        $pdf .= "   /Subtype /CIDFontType2\n";
        $pdf .= "   /BaseFont /$basePdfFont\n";
        $pdf .= "   /CIDSystemInfo << /Registry (Adobe) /Ordering (Identity) /Supplement 0 >>\n";
        $pdf .= "   /CIDToGIDMap /Identity\n";
        $pdf .= "   /DW 1000\n";
        $pdf .= "   /FontDescriptor $descriptorObj 0 R >>\n";
        $pdf .= "endobj\n";

        // Type0 (font principale)
        $offsets[$type0Obj] = strlen($pdf);
        $pdf .= "$type0Obj 0 obj\n";
        $pdf .= "<< /Type /Font\n";
        $pdf .= "   /Subtype /Type0\n";
        $pdf .= "   /BaseFont /$basePdfFont\n";
        $pdf .= "   /Encoding /Identity-H\n";
        $pdf .= "   /DescendantFonts [$cidFontObj 0 R] >>\n";
        $pdf .= "endobj\n";
    }
}
