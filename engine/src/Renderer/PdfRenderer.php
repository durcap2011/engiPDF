<?php
declare(strict_types=1);

namespace EngiPDF\Renderer;

use EngiPDF\PdfWriter\PdfDocument;
use EngiPDF\PdfWriter\PdfPage;
use EngiPDF\Template\PlaceholderResolver;

class PdfRenderer
{
    private PlaceholderResolver $resolver;

    public function __construct()
    {
        $this->resolver = new PlaceholderResolver();
    }

    public function render(array $template, array $data = []): string
    {
        $pageSettings = $template['page'];
        $mmToPt = 72 / 25.4;

        $doc = new PdfDocument(
            $pageSettings['width'] * $mmToPt,
            $pageSettings['height'] * $mmToPt
        );

        // Registra eventuali font TTF dal template
        if (isset($template['fonts']) && is_array($template['fonts'])) {
            foreach ($template['fonts'] as $family => $config) {
                if (is_array($config) && isset($config['file'])) {
                    $weight = $config['weight'] ?? 'normal';
                    $style  = $config['style']  ?? 'normal';
                    $doc->getFontManager()->registerTtf($family, $config['file'], $weight, $style);
                }
            }
        }

        $page = $doc->createPage();
        $pageHeightPt = $pageSettings['height'] * $mmToPt;

        foreach ($template['elements'] as $el) {
            $this->renderElement($page, $el, $data, $mmToPt, $pageHeightPt);
        }

        return $doc->render();
    }

    private function renderElement(
        PdfPage $page,
        array $el,
        array $data,
        float $mmToPt,
        float $pageHeightPt
    ): void {
        $x = $el['x'] * $mmToPt;
        $rawY = $el['y'] * $mmToPt;
        $w = ($el['width'] ?? 0) * $mmToPt;
        $h = ($el['height'] ?? 0) * $mmToPt;

        // Conversione coordinate: PDF usa origine in basso, editor in alto
        $y = $pageHeightPt - $rawY - $h;

        match ($el['type'] ?? '') {
            'text'      => $this->renderText($page, $el, $data, $x, $y, $w, $h),
            'rectangle' => $this->renderRectangle($page, $el, $x, $y, $w, $h),
            'line'      => $this->renderLine($page, $el, $x, $y, $w, $h),
            'list'      => $this->renderList($page, $el, $data, $x, $y, $w, $h, $pageHeightPt),
            default     => null,
        };
    }

    private function renderText(
        PdfPage $page,
        array $el,
        array $data,
        float $x,
        float $y,
        float $w,
        float $h
    ): void {
        $text = $this->resolver->resolve($el['text'] ?? '', $data);
        $style = $el['style'] ?? [];

        $font   = $style['font']   ?? 'helvetica';
        $weight = $style['weight'] ?? 'normal';
        $s      = $style['style']  ?? 'normal';
        $size   = $style['size']   ?? 12;
        $color  = $style['color']  ?? [0, 0, 0];

        // Mappatura weight per il frontend: "bold" → "bold", "normal" → "normal"
        // Se il frontend usa "600"/"700" normalizziamo
        if (in_array($weight, ['600', '700', '800', '900'], true)) {
            $weight = 'bold';
        }

        $textY = $y + $h - $size;
        $page->text($x, $textY, $text, $font, $size, $color, $weight, $s);
    }

    private function renderRectangle(
        PdfPage $page,
        array $el,
        float $x,
        float $y,
        float $w,
        float $h
    ): void {
        $fill = $el['fill'] ?? [];
        $stroke = $el['stroke'] ?? [];
        $strokeWidth = $el['strokeWidth'] ?? 1;

        $page->rectangle($x, $y, $w, $h, $fill, $stroke, $strokeWidth);
    }

    private function renderLine(
        PdfPage $page,
        array $el,
        float $x,
        float $y,
        float $w,
        float $h
    ): void {
        $color = $el['color'] ?? [0, 0, 0];
        $lineWidth = $el['lineWidth'] ?? 1;

        $page->line($x, $y, $x + $w, $y, $color, $lineWidth);
    }

    private function renderList(
        PdfPage $page,
        array $el,
        array $data,
        float $x,
        float $y,
        float $w,
        float $h,
        float $pageHeightPt
    ): void {
        $style = $el['style'] ?? [];
        $items = $el['items'] ?? [];

        // Se dinamica, prendi i dati dall'array ripetuto
        if (($el['mode'] ?? 'static') === 'dynamic' && isset($el['dynamicConfig'])) {
            $field     = $el['dynamicConfig']['repeatField'] ?? '';
            $textField = $el['dynamicConfig']['textField'] ?? 'text';
            if (isset($data[$field]) && is_array($data[$field])) {
                $items = [];
                foreach ($data[$field] as $row) {
                    $items[] = ['text' => $row[$textField] ?? ''];
                }
            }
        }

        $font   = $style['font']   ?? 'helvetica';
        $weight = $style['weight'] ?? 'normal';
        $s      = $style['style']  ?? 'normal';
        $size   = $style['size']   ?? 12;
        $color  = $style['color']  ?? [0, 0, 0];

        if (in_array($weight, ['600', '700', '800', '900'], true)) {
            $weight = 'bold';
        }

        $bulletIndent = ($style['bulletIndent'] ?? 5) * (72 / 25.4);
        $textIndent   = ($style['textIndent']   ?? 15) * (72 / 25.4);
        $lineHeight   = $size * ($style['lineHeight'] ?? 1.4);
        $bullet       = $style['bullet'] ?? 'circle';
        $bullets      = $style['bullets'] ?? [];

        $currentY = $y + $h - $size;

        $this->renderListItems($page, $items, $data, $x, $currentY, $font, $size, $color, $weight, $s, $bulletIndent, $textIndent, $lineHeight, 0, $bullet, $bullets);
    }

    private function renderListItems(
        PdfPage $page,
        array $items,
        array $data,
        float $x,
        float &$currentY,
        string $font,
        float $size,
        array $color,
        string $weight,
        string $s,
        float $bulletIndent,
        float $textIndent,
        float $lineHeight,
        int $level,
        string $bullet,
        array $bullets
    ): void {
        $nestedIndent = $level * 20 * (72 / 25.4);
        $bulletRadius = $size * 0.25;
        $bulletSize = $size * 0.7;

        foreach ($items as $item) {
            $text = $this->resolver->resolve($item['text'] ?? '', $data);

            // Seleziona il bullet corretto in base al livello
            if ($level === 0) {
                $currentBullet = $bullet;
            } else {
                $currentBullet = $bullets[$level] ?? $bullets[count($bullets) - 1] ?? $bullet;
            }

            $bulletX = $x + $bulletIndent + $nestedIndent;
            $bulletY = $currentY;

            // Disegna il bullet in base al tipo
            $this->drawBullet($page, $currentBullet, $bulletX, $bulletY, $bulletRadius, $bulletSize, $color);

            // Testo
            $page->text($x + $textIndent + $nestedIndent, $currentY, $text, $font, $size, $color, $weight, $s);

            $currentY -= $lineHeight;

            // Figli annidati
            if (!empty($item['items']) && is_array($item['items'])) {
                $this->renderListItems($page, $item['items'], $data, $x, $currentY, $font, $size, $color, $weight, $s, $bulletIndent, $textIndent, $lineHeight, $level + 1, $bullet, $bullets);
            }
        }
    }

    private function drawBullet(
        PdfPage $page,
        string $type,
        float $x,
        float $y,
        float $radius,
        float $size,
        array $color
    ): void {
        $cy = $y + ($size * 0.35);

        switch ($type) {
            case 'circle':
                $page->circle($x, $cy, $radius, $color);
                break;

            case 'square':
                $half = $radius * 0.8;
                $page->rectangle($x - $half, $cy - $half, $half * 2, $half * 2, $color, $color);
                break;

            case 'dash':
                $w = $radius * 1.6;
                $h = $radius * 0.5;
                $page->rectangle($x - $w/2, $cy - $h/2, $w, $h, $color, $color);
                break;

            case 'diamond':
                $r = $radius * 1.1;
                $page->polygon([
                    [$x, $cy - $r],
                    [$x + $r, $cy],
                    [$x, $cy + $r],
                    [$x - $r, $cy],
                ], $color);
                break;

            case 'arrow':
                $s = $radius * 1.2;
                $page->polygon([
                    [$x - $s, $cy - $s * 0.5],
                    [$x + $s * 0.3, $cy - $s * 0.5],
                    [$x + $s * 0.3, $cy - $s],
                    [$x + $s, $cy],
                    [$x + $s * 0.3, $cy + $s],
                    [$x + $s * 0.3, $cy + $s * 0.5],
                    [$x - $s, $cy + $s * 0.5],
                ], $color);
                break;

            case 'number':
                $page->circle($x, $cy, $radius, $color);
                break;

            default:
                $page->circle($x, $cy, $radius, $color);
                break;
        }
    }
}
