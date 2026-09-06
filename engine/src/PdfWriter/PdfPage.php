<?php
declare(strict_types=1);

namespace EngiPDF\PdfWriter;

use EngiPDF\Font\FontManager;

class PdfPage
{
    public string $stream = '';
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
        string $style = 'normal',
        string $align = 'left'
    ): self {
        $fontRef = $this->resolveFontRef($font, $weight, $style);
        $encoded = $this->encodePdfString($text);

        $this->stream .= "BT\n";
        $this->stream .= "/$fontRef {$size} Tf\n";
        $this->stream .= "{$color[0]} {$color[1]} {$color[2]} rg\n";

        if ($align === 'center' || $align === 'right') {
            $textWidth = $this->measureTextWidth($text, $font, $size, $weight, $style);
            if ($align === 'center') {
                $x -= $textWidth / 2;
            } else {
                $x -= $textWidth;
            }
        }

        $this->stream .= "$x $y Td\n";
        $this->stream .= "$encoded Tj\n";
        $this->stream .= "ET\n";
        return $this;
    }

    public function textWrapped(
        float $x,
        float $y,
        string $text,
        string $font = 'helvetica',
        float $size = 12,
        array $color = [0, 0, 0],
        string $weight = 'normal',
        string $style = 'normal',
        float $maxWidth = 200
    ): self {
        $fontRef = $this->resolveFontRef($font, $weight, $style);
        $charWidth = $size * 0.52;
        $charsPerLine = (int)($maxWidth / $charWidth);
        if ($charsPerLine < 1) $charsPerLine = 1;

        $words = explode(' ', $text);
        $lines = [];
        $currentLine = '';

        foreach ($words as $word) {
            $testLine = $currentLine === '' ? $word : $currentLine . ' ' . $word;
            if (strlen($testLine) > $charsPerLine && $currentLine !== '') {
                $lines[] = $currentLine;
                $currentLine = $word;
            } else {
                $currentLine = $testLine;
            }
        }
        if ($currentLine !== '') {
            $lines[] = $currentLine;
        }

        $lineY = $y;
        $lineHeight = $size * 1.4;

        foreach ($lines as $line) {
            $encoded = $this->encodePdfString($line);
            $this->stream .= "BT\n";
            $this->stream .= "/$fontRef {$size} Tf\n";
            $this->stream .= "{$color[0]} {$color[1]} {$color[2]} rg\n";
            $this->stream .= "$x $lineY Td\n";
            $this->stream .= "$encoded Tj\n";
            $this->stream .= "ET\n";
            $lineY -= $lineHeight;
        }

        return $this;
    }

    private function measureTextWidth(string $text, string $font, float $size, string $weight = 'normal', string $style = 'normal'): float
    {
        $metrics = [
            'helvetica' => ['normal' => 0.52, 'bold' => 0.55],
            'times' => ['normal' => 0.44, 'bold' => 0.47],
            'courier' => ['normal' => 0.60, 'bold' => 0.60],
        ];

        $family = strtolower($font);
        if (!isset($metrics[$family])) {
            $family = 'helvetica';
        }

        $w = $metrics[$family][$weight] ?? $metrics[$family]['normal'] ?? 0.52;
        return strlen($text) * $size * $w;
    }

    public function encodePdfString(string $text): string
    {
        if (!mb_check_encoding($text, 'UTF-8')) {
            $text = mb_convert_encoding($text, 'UTF-8', 'ISO-8859-1');
        }

        $hasNonAscii = false;
        for ($i = 0; $i < strlen($text); $i++) {
            if (ord($text[$i]) > 127) {
                $hasNonAscii = true;
                break;
            }
        }

        if ($hasNonAscii) {
            $latin1 = mb_convert_encoding($text, 'ISO-8859-1', 'UTF-8');
            $escaped = addcslashes($latin1, '()\\');
            return "($escaped)";
        }

        $escaped = addcslashes($text, '()\\');
        return "($escaped)";
    }

    public function textRotated(
        float $x,
        float $y,
        string $text,
        string $font = 'helvetica',
        float $size = 12,
        array $color = [0, 0, 0],
        string $weight = 'normal',
        string $style = 'normal',
        float $rotation = 0,
        float $opacity = 1.0
    ): self {
        $fontRef = $this->resolveFontRef($font, $weight, $style);
        $encoded = $this->encodePdfString($text);

        $this->stream .= "q\n";
        $this->stream .= "{$color[0]} {$color[1]} {$color[2]} rg\n";

        if ($opacity < 1.0) {
            $this->stream .= "/ExtGSpace << /gs2 << /ca {$opacity} >> >>";
        }

        $rad = $rotation * M_PI / 180;
        $cos = cos($rad);
        $sin = sin($rad);

        $this->stream .= sprintf("%.6f %.6f %.6f %.6f %.4f %.4f cm\n", $cos, $sin, -$sin, $cos, $x, $y);
        $this->stream .= "BT\n";
        $this->stream .= "/$fontRef {$size} Tf\n";
        $this->stream .= "0 0 Td\n";
        $this->stream .= "$encoded Tj\n";
        $this->stream .= "ET\n";
        $this->stream .= "Q\n";

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
        float $width = 1,
        string $style = 'solid'
    ): self {
        $this->stream .= "q\n";
        $this->stream .= "{$color[0]} {$color[1]} {$color[2]} RG\n";
        $this->stream .= "{$width} w\n";

        if ($style === 'dashed') {
            $this->stream .= "[3 2] 0 d\n";
        } elseif ($style === 'dotted') {
            $this->stream .= "[1 2] 0 d\n";
        }

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

    public function checkbox(
        float $x,
        float $y,
        float $size,
        bool $checked,
        array $color = [0, 0, 0]
    ): self {
        $this->stream .= "q\n";
        $this->stream .= "{$color[0]} {$color[1]} {$color[2]} RG\n";
        $this->stream .= "0.8 w\n";

        $this->stream .= sprintf("%.4f %.4f m\n", $x, $y);
        $this->stream .= sprintf("%.4f %.4f l\n", $x + $size, $y);
        $this->stream .= sprintf("%.4f %.4f l\n", $x + $size, $y + $size);
        $this->stream .= sprintf("%.4f %.4f l\n", $x, $y + $size);
        $this->stream .= sprintf("%.4f %.4f l\n", $x, $y);
        $this->stream .= "S\n";

        if ($checked) {
            $this->stream .= "{$color[0]} {$color[1]} {$color[2]} RG\n";
            $this->stream .= "1.2 w\n";
            $m = $size * 0.2;
            $this->stream .= sprintf("%.4f %.4f m\n", $x + $m, $y + $size * 0.5);
            $this->stream .= sprintf("%.4f %.4f l\n", $x + $size * 0.45, $y + $size - $m);
            $this->stream .= sprintf("%.4f %.4f l\n", $x + $size - $m, $y + $m);
            $this->stream .= "S\n";
        }

        $this->stream .= "Q\n";
        return $this;
    }

    public function radio(
        float $x,
        float $y,
        float $size,
        bool $selected,
        array $color = [0, 0, 0]
    ): self {
        $this->stream .= "q\n";
        $this->stream .= "{$color[0]} {$color[1]} {$color[2]} RG\n";
        $this->stream .= "0.8 w\n";

        $cx = $x + $size / 2;
        $cy = $y + $size / 2;
        $radius = $size / 2;

        $k = 0.5522847498;
        $ox = $radius * $k;
        $oy = $radius * $k;

        $this->stream .= sprintf("%.4f %.4f m\n", $cx, $cy - $radius);
        $this->stream .= sprintf("%.4f %.4f %.4f %.4f %.4f %.4f c\n", $cx + $ox, $cy - $radius, $cx + $radius, $cy - $oy, $cx + $radius, $cy);
        $this->stream .= sprintf("%.4f %.4f %.4f %.4f %.4f %.4f c\n", $cx + $radius, $cy + $oy, $cx + $ox, $cy + $radius, $cx, $cy + $radius);
        $this->stream .= sprintf("%.4f %.4f %.4f %.4f %.4f %.4f c\n", $cx - $ox, $cy + $radius, $cx - $radius, $cy + $oy, $cx - $radius, $cy);
        $this->stream .= sprintf("%.4f %.4f %.4f %.4f %.4f %.4f c\n", $cx - $radius, $cy - $oy, $cx - $ox, $cy - $radius, $cx, $cy - $radius);
        $this->stream .= "S\n";

        if ($selected) {
            $this->stream .= "{$color[0]} {$color[1]} {$color[2]} rg\n";
            $dotRadius = $radius * 0.45;
            $dotOx = $dotRadius * $k;
            $dotOy = $dotRadius * $k;

            $this->stream .= sprintf("%.4f %.4f m\n", $cx, $cy - $dotRadius);
            $this->stream .= sprintf("%.4f %.4f %.4f %.4f %.4f %.4f c\n", $cx + $dotOx, $cy - $dotRadius, $cx + $dotRadius, $cy - $dotOy, $cx + $dotRadius, $cy);
            $this->stream .= sprintf("%.4f %.4f %.4f %.4f %.4f %.4f c\n", $cx + $dotRadius, $cy + $dotOy, $cx + $dotOx, $cy + $dotRadius, $cx, $cy + $dotRadius);
            $this->stream .= sprintf("%.4f %.4f %.4f %.4f %.4f %.4f c\n", $cx - $dotOx, $cy + $dotRadius, $cx - $dotRadius, $cy + $dotOy, $cx - $dotRadius, $cy);
            $this->stream .= sprintf("%.4f %.4f %.4f %.4f %.4f %.4f c\n", $cx - $dotRadius, $cy - $dotOy, $cx - $dotOx, $cy - $dotRadius, $cx, $cy - $dotRadius);
            $this->stream .= "f\n";
        }

        $this->stream .= "Q\n";
        return $this;
    }

    public function ellipse(
        float $cx,
        float $cy,
        float $rx,
        float $ry,
        array $fill = [0, 0, 0],
        array $stroke = [0, 0, 0],
        float $strokeWidth = 0.5
    ): self {
        $k = 0.5522847498;
        $ox = $rx * $k;
        $oy = $ry * $k;

        $x0 = $cx - $rx;
        $y0 = $cy - $ry;
        $x1 = $cx + $rx;
        $y1 = $cy + $ry;

        $hasFill = ($fill[0] !== null);
        $hasStroke = ($strokeWidth > 0);

        $this->stream .= "q\n";
        $this->stream .= "{$fill[0]} {$fill[1]} {$fill[2]} rg\n";
        $this->stream .= "{$stroke[0]} {$stroke[1]} {$stroke[2]} RG\n";
        $this->stream .= sprintf("%.4f w\n", $strokeWidth);
        $this->stream .= sprintf("%.4f %.4f m\n", $x0, $cy);
        $this->stream .= sprintf("%.4f %.4f %.4f %.4f %.4f %.4f c\n", $x0, $cy + $oy, $cx - $ox, $y1, $cx, $y1);
        $this->stream .= sprintf("%.4f %.4f %.4f %.4f %.4f %.4f c\n", $cx + $ox, $y1, $x1, $cy + $oy, $x1, $cy);
        $this->stream .= sprintf("%.4f %.4f %.4f %.4f %.4f %.4f c\n", $x1, $cy - $oy, $cx + $ox, $y0, $cx, $y0);
        $this->stream .= sprintf("%.4f %.4f %.4f %.4f %.4f %.4f c\n", $cx - $ox, $y0, $x0, $cy - $oy, $x0, $cy);

        if ($hasFill && $hasStroke) {
            $this->stream .= "B\n";
        } elseif ($hasFill) {
            $this->stream .= "f\n";
        } elseif ($hasStroke) {
            $this->stream .= "S\n";
        }

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
