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

        if (isset($template['fonts']) && is_array($template['fonts'])) {
            foreach ($template['fonts'] as $family => $config) {
                if (is_array($config) && isset($config['file'])) {
                    $weight = $config['weight'] ?? 'normal';
                    $style  = $config['style']  ?? 'normal';
                    $doc->getFontManager()->registerTtf($family, $config['file'], $weight, $style);
                }
            }
        }

        $pageHeightPt = $pageSettings['height'] * $mmToPt;
        $pageWidthPt  = $pageSettings['width'] * $mmToPt;

        $headerHeightPt = ($pageSettings['headerHeight'] ?? 0) * $mmToPt;
        $footerHeightPt = ($pageSettings['footerHeight'] ?? 0) * $mmToPt;

        $contentTop    = $pageHeightPt - $headerHeightPt;
        $contentBottom = $footerHeightPt;

        $headerEls = [];
        $footerEls = [];
        $contentEls = [];

        foreach ($template['elements'] as $el) {
            $elBottom = ($el['y'] ?? 0) * $mmToPt;
            $elTop = $elBottom + ($el['height'] ?? 0) * $mmToPt;

            if ($elTop <= $headerHeightPt && $headerHeightPt > 0) {
                $headerEls[] = $el;
            } elseif ($elBottom >= $pageHeightPt - $footerHeightPt && $footerHeightPt > 0) {
                $footerEls[] = $el;
            } else {
                $contentEls[] = $el;
            }
        }

        $page = $doc->createPage();
        $this->drawHeaderFooter($page, $doc, $headerEls, $footerEls, $data, $mmToPt, $pageHeightPt);

        $currentY = $contentTop;

        foreach ($contentEls as $el) {
            $this->renderElement($page, $doc, $el, $data, $mmToPt, $pageHeightPt, $pageWidthPt, $contentTop, $contentBottom, $currentY, $headerEls, $footerEls);
        }

        return $doc->render();
    }

    private function drawHeaderFooter(
        PdfPage $page,
        PdfDocument $doc,
        array $headerEls,
        array $footerEls,
        array $data,
        float $mmToPt,
        float $pageHeightPt
    ): void {
        foreach ($headerEls as $el) {
            $this->renderFixedElement($page, $doc, $el, $data, $mmToPt, $pageHeightPt);
        }
        foreach ($footerEls as $el) {
            $this->renderFixedElement($page, $doc, $el, $data, $mmToPt, $pageHeightPt);
        }
    }

    private function renderFixedElement(
        PdfPage $page,
        PdfDocument $doc,
        array $el,
        array $data,
        float $mmToPt,
        float $pageHeightPt
    ): void {
        $rawX = $el['x'] * $mmToPt;
        $rawY = $el['y'] * $mmToPt;
        $w = ($el['width'] ?? 0) * $mmToPt;
        $h = ($el['height'] ?? 0) * $mmToPt;
        $y = $pageHeightPt - $rawY - $h;

        match ($el['type'] ?? '') {
            'text'      => $this->renderText($page, $el, $data, $rawX, $y, $w, $h),
            'rectangle' => $this->renderRectangle($page, $el, $rawX, $y, $w, $h),
            'line'      => $this->renderLine($page, $el, $rawX, $y, $w, $h),
            'list'      => $this->renderList($page, $el, $data, $rawX, $y, $w, $h, $pageHeightPt),
            'image'     => $this->renderImage($page, $doc, $el, $rawX, $y, $w, $h),
            default     => null,
        };
    }

    private function renderImage(
        PdfPage $page,
        PdfDocument $doc,
        array $el,
        float $x,
        float $y,
        float $w,
        float $h
    ): void {
        $src = $el['src'] ?? '';
        if ($src === '') return;

        if (!preg_match('/^data:image\/(\w+);base64,(.+)$/', $src, $m)) return;

        $format = strtolower($m[1]);
        $rawData = base64_decode($m[2]);
        if ($rawData === false) return;

        $imgName = 'img_' . md5($src);

        if ($format === 'jpeg' || $format === 'jpg') {
            $doc->registerImage($imgName, $rawData);
        } elseif ($format === 'png') {
            $tmpFile = tempnam(sys_get_temp_dir(), 'png2jpg');
            file_put_contents($tmpFile, $rawData);
            $gd = @imagecreatefrompng($tmpFile);
            @unlink($tmpFile);
            if ($gd === false) return;
            ob_start();
            imagejpeg($gd, null, 90);
            $jpegData = ob_get_clean();
            imagedestroy($gd);
            if ($jpegData === false || $jpegData === '') return;
            $doc->registerImage($imgName, $jpegData);
        } else {
            return;
        }

        $safeName = preg_replace('/[^a-zA-Z0-9]/', '_', $imgName);
        $page->image($x, $y, $w, $h, $safeName);
    }

    private function renderElement(
        PdfPage $page,
        PdfDocument $doc,
        array $el,
        array $data,
        float $mmToPt,
        float $pageHeightPt,
        float $pageWidthPt,
        float $contentTop,
        float $contentBottom,
        float &$currentY,
        array $headerEls,
        array $footerEls
    ): void {
        $rawX = $el['x'] * $mmToPt;
        $rawY = $el['y'] * $mmToPt;
        $w = ($el['width'] ?? 0) * $mmToPt;
        $h = ($el['height'] ?? 0) * $mmToPt;

        $y = $pageHeightPt - $rawY - $h;

        match ($el['type'] ?? '') {
            'text'      => $this->renderText($page, $el, $data, $rawX, $y, $w, $h),
            'rectangle' => $this->renderRectangle($page, $el, $rawX, $y, $w, $h),
            'line'      => $this->renderLine($page, $el, $rawX, $y, $w, $h),
            'list'      => $this->renderList($page, $el, $data, $rawX, $y, $w, $h, $pageHeightPt),
            'table'     => $this->renderTable($page, $doc, $el, $data, $rawX, $rawY, $w, $h, $pageHeightPt, $pageWidthPt, $contentTop, $contentBottom, $currentY, $headerEls, $footerEls),
            'image'     => $this->renderImage($page, $doc, $el, $rawX, $y, $w, $h),
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

    private function renderTable(
        PdfPage $page,
        PdfDocument $doc,
        array $el,
        array $data,
        float $x,
        float $rawY,
        float $w,
        float $h,
        float $pageHeightPt,
        float $pageWidthPt,
        float $contentTop,
        float $contentBottom,
        float &$currentY,
        array $headerEls,
        array $footerEls
    ): void {
        $columns = $el['columns'] ?? [];
        $rows = $el['rows'] ?? [];
        $repeatHeader = $el['repeatHeader'] ?? true;
        $headerStyleDefault = $el['headerStyle'] ?? [];
        $cellStyleDefault = $el['cellStyle'] ?? [];
        $borderColor = $el['borderColor'] ?? [0, 0, 0];
        $borderWidth = $el['borderWidth'] ?? 0.5;
        $name = $el['name'] ?? '';

        if ($name !== '' && isset($data[$name]) && is_array($data[$name])) {
            $rows = [];
            foreach ($data[$name] as $row) {
                if (is_array($row)) {
                    $cells = [];
                    foreach ($row as $val) {
                        $cells[] = ['text' => (string)$val, 'style' => []];
                    }
                    $rows[] = ['cells' => $cells];
                }
            }
        }

        if (empty($columns) || empty($rows)) {
            return;
        }

        $resolvedColumns = [];
        foreach ($columns as $col) {
            $resolvedColumns[] = [
                'header' => $this->resolver->resolve($col['header'] ?? '', $data),
                'width' => $col['width'] ?? 50,
                'headerStyle' => $col['headerStyle'] ?? $headerStyleDefault,
            ];
        }

        $resolvedRows = [];
        foreach ($rows as $row) {
            $cells = $row['cells'] ?? [];
            $resolvedCells = [];
            $cellStyles = [];
            foreach ($cells as $cell) {
                $cellText = is_array($cell) ? ($cell['text'] ?? '') : $cell;
                $cellStyle = is_array($cell) ? ($cell['style'] ?? []) : [];
                $resolvedCells[] = $this->resolver->resolve($cellText, $data);
                $cellStyles[] = $cellStyle;
            }
            $resolvedRows[] = ['cells' => $resolvedCells, 'styles' => $cellStyles];
        }

        $totalColWidth = array_sum(array_column($resolvedColumns, 'width'));
        $colWidthsPt = [];
        foreach ($resolvedColumns as $col) {
            $colWidthsPt[] = ($col['width'] / $totalColWidth) * $w;
        }

        $firstHeaderStyle = $resolvedColumns[0]['headerStyle'] ?? $headerStyleDefault;
        $headerFontSize = $firstHeaderStyle['size'] ?? 10;
        $headerLineHeight = $headerFontSize * 1.4;
        $headerHeight = $headerLineHeight + 8;

        $cellFontSize = $cellStyleDefault['size'] ?? 10;
        $cellLineHeight = $cellFontSize * 1.4;
        $cellHeight = $cellLineHeight + 8;

        $tableTopY = $pageHeightPt - $rawY;
        $currentY = $tableTopY;
        $pageNeedsHeader = true;

        foreach ($resolvedRows as $rowIdx => $row) {
            $spaceNeeded = $cellHeight;
            if ($pageNeedsHeader) {
                $spaceNeeded += $headerHeight;
            }

            if ($currentY - $spaceNeeded < $contentBottom) {
                $page = $doc->createPage();
                $this->drawHeaderFooter($page, $doc, $headerEls, $footerEls, $data, 72 / 25.4, $pageHeightPt);
                $currentY = $contentTop;
                $pageNeedsHeader = $repeatHeader;
            }

            if ($pageNeedsHeader) {
                $this->drawTableHeaderRow($page, $resolvedColumns, $x, $currentY - $headerHeight, $colWidthsPt, $headerHeight, $borderColor, $borderWidth);
                $currentY -= $headerHeight;
                $pageNeedsHeader = false;
            }

            $this->drawTableDataRow($page, $row['cells'], $row['styles'], $cellStyleDefault, $x, $currentY - $cellHeight, $colWidthsPt, $cellHeight, $borderColor, $borderWidth);
            $currentY -= $cellHeight;
        }
    }

    private function estimateTextWidth(string $text, float $fontSize): float
    {
        return strlen($text) * $fontSize * 0.5;
    }

    private function drawTableHeaderRow(
        PdfPage $page,
        array $columns,
        float $x,
        float $y,
        array $colWidths,
        float $rowHeight,
        array $borderColor,
        float $borderWidth
    ): void {
        $currentX = $x;

        foreach ($columns as $idx => $col) {
            $colWidth = $colWidths[$idx] ?? 50;
            $style = $col['headerStyle'] ?? [];

            // Sfondo header
            $background = [0.85, 0.85, 0.85];
            $page->rectangle($currentX, $y, $colWidth, $rowHeight, $background, $borderColor, $borderWidth);

            // Testo
            $font = $style['font'] ?? 'helvetica';
            $weight = $style['weight'] ?? 'normal';
            $s = $style['style'] ?? 'normal';
            $size = $style['size'] ?? 10;
            $color = $style['color'] ?? [0, 0, 0];
            $align = $style['align'] ?? 'center';

            if (in_array($weight, ['600', '700', '800', '900'], true)) {
                $weight = 'bold';
            }

            $textX = $currentX + 4;
            $textY = $y + $rowHeight - $size - 3;

            if ($align === 'center') {
                $textWidth = $this->estimateTextWidth($col['header'], $size);
                $textX = $currentX + ($colWidth - $textWidth) / 2;
            } elseif ($align === 'right') {
                $textWidth = $this->estimateTextWidth($col['header'], $size);
                $textX = $currentX + $colWidth - $textWidth - 4;
            }

            $page->text($textX, $textY, $col['header'], $font, $size, $color, $weight, $s);

            $currentX += $colWidth;
        }
    }

    private function drawTableDataRow(
        PdfPage $page,
        array $cells,
        array $cellStyles,
        array $defaultStyle,
        float $x,
        float $y,
        array $colWidths,
        float $rowHeight,
        array $borderColor,
        float $borderWidth
    ): void {
        $currentX = $x;

        foreach ($cells as $idx => $cellText) {
            $colWidth = $colWidths[$idx] ?? 50;

            // Merge stile default con stile per-cella
            $override = $cellStyles[$idx] ?? [];
            $style = array_merge($defaultStyle, $override);

            // Sfondo cella (solo se esplicitamente impostato)
            $background = $style['background'] ?? [];
            if (!empty($background)) {
                $page->rectangle($currentX, $y, $colWidth, $rowHeight, $background, $borderColor, $borderWidth);
            } else {
                $page->rectangle($currentX, $y, $colWidth, $rowHeight, [], $borderColor, $borderWidth);
            }

            // Testo
            $font = $style['font'] ?? 'helvetica';
            $weight = $style['weight'] ?? 'normal';
            $s = $style['style'] ?? 'normal';
            $size = $style['size'] ?? 10;
            $color = $style['color'] ?? [0, 0, 0];
            $align = $style['align'] ?? 'left';

            if (in_array($weight, ['600', '700', '800', '900'], true)) {
                $weight = 'bold';
            }

            $textX = $currentX + 4;
            $textY = $y + $rowHeight - $size - 3;

            if ($align === 'center') {
                $textWidth = $this->estimateTextWidth($cellText, $size);
                $textX = $currentX + ($colWidth - $textWidth) / 2;
            } elseif ($align === 'right') {
                $textWidth = $this->estimateTextWidth($cellText, $size);
                $textX = $currentX + $colWidth - $textWidth - 4;
            }

            $page->text($textX, $textY, $cellText, $font, $size, $color, $weight, $s);

            $currentX += $colWidth;
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
