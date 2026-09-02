<?php
declare(strict_types=1);

namespace EngiPDF\PdfWriter;

use EngiPDF\Font\FontManager;

class PdfPage
{
    private string $stream = '';
    private PdfDocument $doc;

    public function __construct(PdfDocument $doc)
    {
        $this->doc = $doc;
    }

    /**
     * Risolve il nome del font per il content stream PDF.
     * Registra il font se non già registrato.
     */
    private function resolveFontRef(string $family, string $weight = 'normal', string $style = 'normal'): string
    {
        $fm = $this->doc->getFontManager();
        $normalized = $fm->normalizeFamily($family);
        $key = "$normalized:$weight:$style";

        if ($fm->getPdfObjectNum($key) === -1) {
            $fm->allocatePdfObject($key);
        }

        return 'F_' . preg_replace('/[^a-zA-Z0-9]/', '_', $key);
    }

    public function text(
        float $x,
        float $y,
        string $text,
        string $font = 'helvetica',
        float $size = 12,
        array $color = [0, 0, 0],
        string $weight = 'normal',
        string $style = 'normal'
    ): self {
        $escaped = addcslashes($text, '()\\');
        $fontRef = $this->resolveFontRef($font, $weight, $style);

        $this->stream .= "BT\n";
        $this->stream .= "/$fontRef {$size} Tf\n";
        $this->stream .= "{$color[0]} {$color[1]} {$color[2]} rg\n";
        $this->stream .= "$x $y Td\n";
        $this->stream .= "($escaped) Tj\n";
        $this->stream .= "ET\n";
        return $this;
    }

    public function rectangle(
        float $x,
        float $y,
        float $w,
        float $h,
        array $fill = [],
        array $stroke = [],
        float $strokeWidth = 1
    ): self {
        $this->stream .= "q\n";

        if (!empty($fill)) {
            $this->stream .= "{$fill[0]} {$fill[1]} {$fill[2]} rg\n";
        }
        if (!empty($stroke)) {
            $this->stream .= "{$stroke[0]} {$stroke[1]} {$stroke[2]} RG\n";
            $this->stream .= "{$strokeWidth} w\n";
        }

        $this->stream .= "$x $y $w $h re\n";

        if (!empty($fill) && !empty($stroke)) {
            $this->stream .= "B\n";
        } elseif (!empty($fill)) {
            $this->stream .= "f\n";
        } else {
            $this->stream .= "S\n";
        }

        $this->stream .= "Q\n";
        return $this;
    }

    public function line(
        float $x1,
        float $y1,
        float $x2,
        float $y2,
        array $color = [0, 0, 0],
        float $width = 1
    ): self {
        $this->stream .= "q\n";
        $this->stream .= "{$color[0]} {$color[1]} {$color[2]} RG\n";
        $this->stream .= "{$width} w\n";
        $this->stream .= "$x1 $y1 m\n";
        $this->stream .= "$x2 $y2 l\n";
        $this->stream .= "S\n";
        $this->stream .= "Q\n";
        return $this;
    }

    public function circle(
        float $cx,
        float $cy,
        float $radius,
        array $fill = [0, 0, 0]
    ): self {
        $k = 0.5522847498;
        $ox = $radius * $k;
        $oy = $radius * $k;

        $x0 = $cx - $radius;
        $y0 = $cy - $radius;
        $x1 = $cx + $radius;
        $y1 = $cy + $radius;

        $this->stream .= "q\n";
        $this->stream .= "{$fill[0]} {$fill[1]} {$fill[2]} rg\n";
        $this->stream .= sprintf("%.4f %.4f m\n", $x0, $cy);
        $this->stream .= sprintf("%.4f %.4f %.4f %.4f %.4f %.4f c\n", $x0, $cy + $oy, $cx - $ox, $y1, $cx, $y1);
        $this->stream .= sprintf("%.4f %.4f %.4f %.4f %.4f %.4f c\n", $cx + $ox, $y1, $x1, $cy + $oy, $x1, $cy);
        $this->stream .= sprintf("%.4f %.4f %.4f %.4f %.4f %.4f c\n", $x1, $cy - $oy, $cx + $ox, $y0, $cx, $y0);
        $this->stream .= sprintf("%.4f %.4f %.4f %.4f %.4f %.4f c\n", $cx - $ox, $y0, $x0, $cy - $oy, $x0, $cy);
        $this->stream .= "f\n";
        $this->stream .= "Q\n";
        return $this;
    }

    public function polygon(
        array $points,
        array $fill = [0, 0, 0]
    ): self {
        if (count($points) < 3) {
            return $this;
        }

        $this->stream .= "q\n";
        $this->stream .= "{$fill[0]} {$fill[1]} {$fill[2]} rg\n";

        // Primo punto
        $first = $points[0];
        $this->stream .= sprintf("%.4f %.4f m\n", $first[0], $first[1]);

        // Punti successivi
        for ($i = 1; $i < count($points); $i++) {
            $p = $points[$i];
            $this->stream .= sprintf("%.4f %.4f l\n", $p[0], $p[1]);
        }

        // Chiudi il percorso
        $this->stream .= "h\n";
        $this->stream .= "f\n";
        $this->stream .= "Q\n";
        return $this;
    }

    public function image(
        float $x,
        float $y,
        float $w,
        float $h,
        string $imageName
    ): self {
        $this->stream .= "q\n";
        $this->stream .= "$w 0 0 $h $x $y cm\n";
        $this->stream .= "/$imageName Do\n";
        $this->stream .= "Q\n";
        return $this;
    }

    public function buildStream(): string
    {
        return $this->stream;
    }

    public function getDocument(): PdfDocument
    {
        return $this->doc;
    }
}
