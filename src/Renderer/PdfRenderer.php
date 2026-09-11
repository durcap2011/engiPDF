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
        $mmToPt = 72 / 25.4;

        $doc = new PdfDocument(595.28, 841.89);

        if (isset($template['fonts']) && is_array($template['fonts'])) {
            foreach ($template['fonts'] as $family => $config) {
                if (is_array($config) && isset($config['file'])) {
                    $weight = $config['weight'] ?? 'normal';
                    $style  = $config['style']  ?? 'normal';
                    $doc->getFontManager()->registerTtf($family, $config['file'], $weight, $style);
                }
            }
        }

        $pages = $this->normalizePages($template);

        $repeatElements = [];
        foreach (($template['elements'] ?? []) as $el) {
            if (!empty($el['repeatOnAllPages'])) {
                $repeatElements[] = $el;
            }
        }

        $pageNum = 0;
        foreach ($pages as $pageData) {
            $pageNum++;
            $pageSettings = $pageData['settings'];
            $pageElements = $pageData['elements'];

            $pageHeightPt = $pageSettings['height'] * $mmToPt;
            $pageWidthPt  = $pageSettings['width'] * $mmToPt;

            $headerHeightPt = ($pageSettings['headerHeight'] ?? 0) * $mmToPt;
            $footerHeightPt = ($pageSettings['footerHeight'] ?? 0) * $mmToPt;

            $contentTop    = $pageHeightPt - $headerHeightPt;
            $contentBottom = $footerHeightPt;

            $headerEls = [];
            $footerEls = [];
            $contentEls = [];

            foreach ($pageElements as $el) {
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

            $page = $doc->createPage($pageWidthPt, $pageHeightPt);
            $this->drawHeaderFooter($page, $doc, $headerEls, $footerEls, $data, $mmToPt, $pageHeightPt, $pageNum);

            $currentY = $contentTop;

            foreach ($contentEls as $el) {
                $this->renderElement($page, $doc, $el, $data, $mmToPt, $pageHeightPt, $pageWidthPt, $contentTop, $contentBottom, $currentY, $headerEls, $footerEls, $pageNum);
            }

            foreach ($repeatElements as $el) {
                $rawX = $el['x'] * $mmToPt;
                $rawY = $el['y'] * $mmToPt;
                $w = ($el['width'] ?? 0) * $mmToPt;
                $h = ($el['height'] ?? 0) * $mmToPt;
                $y = $pageHeightPt - $rawY - $h;

                $el['_mmToPt'] = $mmToPt;

                $this->renderRepeatElement($page, $doc, $el, $data, $rawX, $y, $w, $h, $pageNum);
            }
        }

        return $doc->render();
    }

    private function normalizePages(array $template): array
    {
        if (isset($template['pages']) && is_array($template['pages'])) {
            $result = [];
            foreach ($template['pages'] as $pageData) {
                $pageId = $pageData['id'] ?? '';
                $settings = $pageData['settings'] ?? $template['page'] ?? [];
                $pageElements = [];
                foreach (($template['elements'] ?? []) as $el) {
                    if (($el['pageId'] ?? '') === $pageId) {
                        $pageElements[] = $el;
                    }
                }
                $result[] = [
                    'id' => $pageId,
                    'settings' => $settings,
                    'elements' => $pageElements
                ];
            }
            return $result;
        }

        return [[
            'id' => '',
            'settings' => $template['page'] ?? [],
            'elements' => $template['elements'] ?? []
        ]];
    }

    private function drawHeaderFooter(
        PdfPage $page,
        PdfDocument $doc,
        array $headerEls,
        array $footerEls,
        array $data,
        float $mmToPt,
        float $pageHeightPt,
        int $pageNum = 1
    ): void {
        foreach ($headerEls as $el) {
            $this->renderFixedElement($page, $doc, $el, $data, $mmToPt, $pageHeightPt, $pageNum);
        }
        foreach ($footerEls as $el) {
            $this->renderFixedElement($page, $doc, $el, $data, $mmToPt, $pageHeightPt, $pageNum);
        }
    }

    private function renderRepeatElement(
        PdfPage $page,
        PdfDocument $doc,
        array $el,
        array $data,
        float $x,
        float $y,
        float $w,
        float $h,
        int $pageNum
    ): void {
        if (isset($el['showIf']) && !$this->evaluateCondition($el['showIf'], $data)) {
            return;
        }

        $styleOverrides = [];
        if (isset($el['styleIf']) && is_array($el['styleIf'])) {
            foreach ($el['styleIf'] as $rule) {
                if ($this->evaluateCondition($rule, $data)) {
                    $styleOverrides = $rule['then'] ?? [];
                    break;
                }
            }
        }
        if (!empty($styleOverrides)) {
            $el = $this->applyStyleOverrides($el, $styleOverrides);
        }

        $type = $el['type'] ?? '';
        match ($type) {
            'stamp' => $this->renderStamp($page, $el, $data, $x, $y, $w, $h),
            'watermark' => $this->renderWatermark($page, $el, $data, $x, $y, $w, $h),
            'text' => $this->renderText($page, $el, $data, $x, $y, $w, $h),
            'image' => $this->renderImage($page, $doc, $el, $x, $y, $w, $h),
            'rectangle' => $this->renderRectangle($page, $el, $x, $y, $w, $h),
            'line' => $this->renderLine($page, $el, $x, $y, $w, $h),
            'ellipse' => $this->renderEllipse($page, $el, $x, $y, $w, $h),
            'divider' => $this->renderDivider($page, $el, $x, $y, $w, $h),
            default => null,
        };
    }

    private function renderFixedElement(
        PdfPage $page,
        PdfDocument $doc,
        array $el,
        array $data,
        float $mmToPt,
        float $pageHeightPt,
        int $pageNum = 1
    ): void {
        // Check showIf condition
        if (isset($el['showIf']) && !$this->evaluateCondition($el['showIf'], $data)) {
            return;
        }

        // Apply styleIf overrides
        $styleOverrides = [];
        if (isset($el['styleIf']) && is_array($el['styleIf'])) {
            foreach ($el['styleIf'] as $rule) {
                if ($this->evaluateCondition($rule, $data)) {
                    $styleOverrides = $rule['then'] ?? [];
                    break;
                }
            }
        }
        if (!empty($styleOverrides)) {
            $el = $this->applyStyleOverrides($el, $styleOverrides);
        }

        $rawX = $el['x'] * $mmToPt;
        $rawY = $el['y'] * $mmToPt;
        $w = ($el['width'] ?? 0) * $mmToPt;
        $h = ($el['height'] ?? 0) * $mmToPt;
        $y = $pageHeightPt - $rawY - $h;

        $el['_mmToPt'] = $mmToPt;

        match ($el['type'] ?? '') {
            'text'      => $this->renderText($page, $el, $data, $rawX, $y, $w, $h),
            'rectangle' => $this->renderRectangle($page, $el, $rawX, $y, $w, $h),
            'line'      => $this->renderLine($page, $el, $rawX, $y, $w, $h),
            'list'      => $this->renderList($page, $el, $data, $rawX, $y, $w, $h, $pageHeightPt),
            'image'     => $this->renderImage($page, $doc, $el, $rawX, $y, $w, $h),
            'ellipse'   => $this->renderEllipse($page, $el, $rawX, $y, $w, $h),
            'divider'   => $this->renderDivider($page, $el, $rawX, $y, $w, $h),
            'signature' => $this->renderSignature($page, $el, $data, $rawX, $y, $w, $h),
            'container' => $this->renderContainer($page, $el, $rawX, $y, $w, $h),
            'pageNumber' => $this->renderPageNumber($page, $el, $rawX, $y, $w, $h, $pageNum),
            'date'      => $this->renderDate($page, $el, $rawX, $y, $w, $h),
            'watermark' => $this->renderWatermark($page, $el, $data, $rawX, $y, $w, $h),
            'qrcode'    => $this->renderQrCode($page, $el, $data, $rawX, $y, $w, $h),
            'checklist' => $this->renderChecklist($page, $el, $data, $rawX, $y, $w, $h),
            'radio'     => $this->renderRadio($page, $el, $data, $rawX, $y, $w, $h),
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
        array $footerEls,
        int $pageNum = 1
    ): void {
        // Check showIf condition
        if (isset($el['showIf']) && !$this->evaluateCondition($el['showIf'], $data)) {
            return;
        }

        // Apply styleIf overrides
        $styleOverrides = [];
        if (isset($el['styleIf']) && is_array($el['styleIf'])) {
            foreach ($el['styleIf'] as $rule) {
                if ($this->evaluateCondition($rule, $data)) {
                    $styleOverrides = $rule['then'] ?? [];
                    break;
                }
            }
        }
        if (!empty($styleOverrides)) {
            $el = $this->applyStyleOverrides($el, $styleOverrides);
        }

        $rawX = $el['x'] * $mmToPt;
        $rawY = $el['y'] * $mmToPt;
        $w = ($el['width'] ?? 0) * $mmToPt;
        $h = ($el['height'] ?? 0) * $mmToPt;

        $y = $pageHeightPt - $rawY - $h;

        $el['_mmToPt'] = $mmToPt;

        match ($el['type'] ?? '') {
            'text'      => $this->renderText($page, $el, $data, $rawX, $y, $w, $h),
            'rectangle' => $this->renderRectangle($page, $el, $rawX, $y, $w, $h),
            'line'      => $this->renderLine($page, $el, $rawX, $y, $w, $h),
            'list'      => $this->renderList($page, $el, $data, $rawX, $y, $w, $h, $pageHeightPt),
            'table'     => $this->renderTable($page, $doc, $el, $data, $rawX, $rawY, $w, $h, $pageHeightPt, $pageWidthPt, $contentTop, $contentBottom, $currentY, $headerEls, $footerEls),
            'image'     => $this->renderImage($page, $doc, $el, $rawX, $y, $w, $h),
            'ellipse'   => $this->renderEllipse($page, $el, $rawX, $y, $w, $h),
            'divider'   => $this->renderDivider($page, $el, $rawX, $y, $w, $h),
            'signature' => $this->renderSignature($page, $el, $data, $rawX, $y, $w, $h),
            'container' => $this->renderContainer($page, $el, $rawX, $y, $w, $h),
            'pageNumber' => $this->renderPageNumber($page, $el, $rawX, $y, $w, $h, $pageNum),
            'date'      => $this->renderDate($page, $el, $rawX, $y, $w, $h),
            'watermark' => $this->renderWatermark($page, $el, $data, $rawX, $y, $w, $h),
            'qrcode'    => $this->renderQrCode($page, $el, $data, $rawX, $y, $w, $h),
            'stamp'     => $this->renderStamp($page, $el, $data, $rawX, $y, $w, $h),
            'quote'     => $this->renderQuote($page, $el, $data, $rawX, $y, $w, $h),
            'callout'   => $this->renderCallout($page, $el, $data, $rawX, $y, $w, $h),
            'codeBlock' => $this->renderCodeBlock($page, $el, $data, $rawX, $y, $w, $h),
            'progressBar' => $this->renderProgressBar($page, $el, $data, $rawX, $y, $w, $h),
            'icon'      => $this->renderIcon($page, $el, $rawX, $y, $w, $h),
            'barcode'   => $this->renderBarcode($page, $el, $data, $rawX, $y, $w, $h),
            'chart'     => $this->renderChart($page, $el, $rawX, $y, $w, $h),
            'checklist' => $this->renderChecklist($page, $el, $data, $rawX, $y, $w, $h),
            'radio'     => $this->renderRadio($page, $el, $data, $rawX, $y, $w, $h),
            'pageBreak' => null,
            'spacer'    => null,
            default     => null,
        };
    }

    private function evaluateCondition(array $rule, array $data): bool
    {
        $field = $rule['field'] ?? '';
        $op = $rule['op'] ?? 'eq';
        $value = $rule['value'] ?? '';

        $fieldValue = $this->getNestedValue($data, $field);

        switch ($op) {
            case 'eq':
                return (string)$fieldValue === (string)$value;
            case 'neq':
                return (string)$fieldValue !== (string)$value;
            case 'gt':
                return (float)$fieldValue > (float)$value;
            case 'lt':
                return (float)$fieldValue < (float)$value;
            case 'gte':
                return (float)$fieldValue >= (float)$value;
            case 'lte':
                return (float)$fieldValue <= (float)$value;
            case 'empty':
                return $fieldValue === null || $fieldValue === '' || $fieldValue === [];
            case 'notempty':
                return $fieldValue !== null && $fieldValue !== '' && $fieldValue !== [];
            case 'contains':
                return str_contains((string)$fieldValue, (string)$value);
            default:
                return true;
        }
    }

    private function getNestedValue(array $data, string $path)
    {
        $parts = explode('.', $path);
        $current = $data;

        foreach ($parts as $part) {
            if (!is_array($current) || !array_key_exists($part, $current)) {
                return null;
            }
            $current = $current[$part];
        }

        return $current;
    }

    private function applyStyleOverrides(array $el, array $overrides): array
    {
        foreach ($overrides as $key => $value) {
            if ($key === 'fill') {
                if (isset($el['style'])) {
                    $el['style']['color'] = $value;
                }
                if (array_key_exists('color', $el)) {
                    $el['color'] = $value;
                }
                if (array_key_exists('fill', $el)) {
                    $el['fill'] = $value;
                }
            } else {
                $el[$key] = $value;
            }
        }

        return $el;
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
        $underline = $style['underline'] ?? false;

        // Mappatura weight per il frontend: "bold" → "bold", "normal" → "normal"
        // Se il frontend usa "600"/"700" normalizziamo
        if (in_array($weight, ['600', '700', '800', '900'], true)) {
            $weight = 'bold';
        }

        $textY = $y + $h - $size;
        $page->text($x, $textY, $text, $font, $size, $color, $weight, $s);

        if ($underline) {
            $textWidth = $this->measureTextWidth($text, $font, $size);
            $underlineY = $textY - 2;
            $page->line($x, $underlineY, $x + $textWidth, $underlineY, $color, 0.5);
        }
    }

    private function measureTextWidth(string $text, string $font, float $size): float {
        $metrics = [
            'helvetica'          => 0.5,
            'helvetica-bold'     => 0.55,
            'helvetica-oblique'  => 0.5,
            'helvetica-boldoblique' => 0.55,
            'times'              => 0.45,
            'times-bold'         => 0.5,
            'times-italic'       => 0.45,
            'times-bolditalic'   => 0.5,
            'courier'            => 0.6,
            'courier-bold'       => 0.6,
            'courier-oblique'    => 0.6,
            'courier-boldoblique'=> 0.6,
        ];
        $key = $font . ($weight === 'bold' ? '-bold' : '') . ($s === 'italic' ? '-italic' : '');
        $ratio = $metrics[$key] ?? $metrics[$font] ?? 0.5;
        return strlen($text) * $size * $ratio;
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

        $x2 = $el['x2'] ?? null;
        $y2 = $el['y2'] ?? null;

        if ($x2 !== null && $y2 !== null) {
            $x2Pt = $x2 * (72 / 25.4);
            $y2Pt = $y2 * (72 / 25.4);
            $page->line($x, $y, $x2Pt, $y2Pt, $color, $lineWidth);
        } else {
            $page->line($x, $y, $x + $w, $y, $color, $lineWidth);
        }
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

    private function renderEllipse(
        PdfPage $page,
        array $el,
        float $x,
        float $y,
        float $w,
        float $h
    ): void {
        $cx = $x + $w / 2;
        $cy = $y + $h / 2;
        $rx = $w / 2;
        $ry = $h / 2;
        $fill = $el['fill'] ?? [0.9, 0.9, 0.9];
        $stroke = $el['stroke'] ?? [0, 0, 0];
        $strokeWidth = ($el['strokeWidth'] ?? 0.5) * (72 / 25.4);
        $page->ellipse($cx, $cy, $rx, $ry, $fill, $stroke, $strokeWidth);
    }

    private function renderDivider(
        PdfPage $page,
        array $el,
        float $x,
        float $y,
        float $w,
        float $h
    ): void {
        $color = $el['color'] ?? [0, 0, 0];
        $lineWidth = ($el['lineWidth'] ?? 0.5) * (72 / 25.4);
        $lineStyle = $el['lineStyle'] ?? 'solid';
        $cy = $y + $h / 2;
        $page->line($x, $cy, $x + $w, $cy, $color, $lineWidth, $lineStyle);
    }

    private function renderSignature(
        PdfPage $page,
        array $el,
        array $data,
        float $x,
        float $y,
        float $w,
        float $h
    ): void {
        $color = $el['color'] ?? [0, 0, 0];
        $label = $this->resolver->resolve($el['label'] ?? 'Firma', $data);
        $lineY = $y + 10;
        $page->line($x, $lineY, $x + $w, $lineY, $color, 0.5, 'dashed');
        $textY = $lineY - 12;
        $textX = $x + $w / 2;
        $page->text($textX, $textY, $label, 'helvetica', 8, $color, 'normal', 'normal', 'center');
    }

    private function renderContainer(
        PdfPage $page,
        array $el,
        float $x,
        float $y,
        float $w,
        float $h
    ): void {
        $fill = $el['fill'] ?? [1, 1, 1];
        $borderColor = $el['borderColor'] ?? [0, 0, 0];
        $borderWidth = ($el['borderWidth'] ?? 0.5) * (72 / 25.4);
        $page->rectangle($x, $y, $w, $h, $fill, $borderColor, $borderWidth);
    }

    private function renderPageNumber(
        PdfPage $page,
        array $el,
        float $x,
        float $y,
        float $w,
        float $h,
        int $pageNum
    ): void {
        $style = $el['style'] ?? [];
        $font = $style['font'] ?? 'helvetica';
        $weight = $style['weight'] ?? 'normal';
        $s = $style['style'] ?? 'normal';
        $size = $style['size'] ?? 10;
        $color = $style['color'] ?? [0, 0, 0];
        $align = $style['align'] ?? 'center';
        $text = (string) $pageNum;
        $textY = $y + $h - $size;
        $textX = $x;
        if ($align === 'center') {
            $textX = $x + $w / 2;
        } elseif ($align === 'right') {
            $textX = $x + $w;
        }
        $page->text($textX, $textY, $text, $font, $size, $color, $weight, $s, $align);
    }

    private function renderDate(
        PdfPage $page,
        array $el,
        float $x,
        float $y,
        float $w,
        float $h
    ): void {
        $style = $el['style'] ?? [];
        $font = $style['font'] ?? 'helvetica';
        $weight = $style['weight'] ?? 'normal';
        $s = $style['style'] ?? 'normal';
        $size = $style['size'] ?? 10;
        $color = $style['color'] ?? [0, 0, 0];
        $align = $style['align'] ?? 'left';
        $format = $el['format'] ?? 'd/m/Y';
        $text = date($format);
        $textY = $y + $h - $size;
        $textX = $x;
        if ($align === 'center') {
            $textX = $x + $w / 2;
        } elseif ($align === 'right') {
            $textX = $x + $w;
        }
        $page->text($textX, $textY, $text, $font, $size, $color, $weight, $s, $align);
    }

    private function renderWatermark(
        PdfPage $page,
        array $el,
        array $data,
        float $x,
        float $y,
        float $w,
        float $h
    ): void {
        $text = $this->resolver->resolve($el['text'] ?? 'BOZZA', $data);
        $fontSize = $el['fontSize'] ?? 48;
        $color = $el['color'] ?? [0.8, 0.8, 0.8];
        $rotation = $el['rotation'] ?? -45;
        $opacity = $el['opacity'] ?? 0.3;
        $cx = $x + $w / 2;
        $cy = $y + $h / 2;
        $page->textRotated($cx, $cy, $text, 'helvetica', $fontSize, $color, 'bold', 'normal', $rotation, $opacity);
    }

    private function renderQrCode(
        PdfPage $page,
        array $el,
        array $data,
        float $x,
        float $y,
        float $w,
        float $h
    ): void {
        $text = $this->resolver->resolve($el['text'] ?? '', $data);
        if ($text === '') return;

        $size = 21;
        $cellW = $w / $size;
        $cellH = $h / $size;

        $modules = $this->generateQrModules($text, $size);

        $page->stream .= "q\n";
        $page->stream .= "1 0 0 1 0 0 cm\n";

        for ($r = 0; $r < $size; $r++) {
            for ($c = 0; $c < $size; $c++) {
                if ($modules[$r][$c]) {
                    $cx = $x + $c * $cellW;
                    $cy = $y + $h - ($r + 1) * $cellH;
                    $page->stream .= sprintf("%.4f %.4f %.4f %.4f re f\n", $cx, $cy, $cellW, $cellH);
                }
            }
        }

        $page->stream .= "Q\n";
    }

    private function generateQrModules(string $text, int $size): array
    {
        $grid = [];
        for ($r = 0; $r < $size; $r++) {
            $grid[$r] = [];
            for ($c = 0; $c < $size; $c++) {
                $inFinder = ($r < 7 && $c < 7) || ($r < 7 && $c >= $size - 7) || ($r >= $size - 7 && $c < 7);
                $inBorder = $inFinder && ($r === 0 || $r === 6 || $c === 0 || $c === 6 || ($r >= 2 && $r <= 4 && $c >= 2 && $c <= 4));
                $hash = (($r * 31 + $c * 17 + strlen($text)) % 7);
                $grid[$r][$c] = $inBorder || (!$inFinder && $hash < 3);
            }
        }
        return $grid;
    }

    private function renderStamp(
        PdfPage $page,
        array $el,
        array $data,
        float $x,
        float $y,
        float $w,
        float $h
    ): void {
        $presets = [
            'approved' => ['color' => [0, 0.6, 0], 'text' => 'APPROVATO'],
            'confidential' => ['color' => [0.6, 0, 0], 'text' => 'RISERVATO'],
            'draft' => ['color' => [0.5, 0.5, 0.5], 'text' => 'BOZZA'],
            'paid' => ['color' => [0, 0, 0.6], 'text' => 'PAGATO'],
            'urgent' => ['color' => [0.6, 0, 0.6], 'text' => 'URGENTE'],
        ];

        $preset = $presets[$el['preset']] ?? $presets['approved'];
        $color = $el['color'] ?? $preset['color'];
        $text = $this->resolver->resolve($el['text'] ?? $preset['text'], $data);

        $fontSize = 14;
        $cx = $x + $w / 2;
        $cy = $y + $h / 2;
        $borderWidth = 2.5;
        $radius = 3.5;

        $bgColor = [
            $color[0] * 0.1 + 0.9,
            $color[1] * 0.1 + 0.9,
            $color[2] * 0.1 + 0.9,
        ];

        $rad = -15 * M_PI / 180;
        $cos = cos($rad);
        $sin = sin($rad);

        $page->stream .= "q\n";
        $page->stream .= sprintf("1 0 0 1 %.4f %.4f cm\n", $cx, $cy);
        $page->stream .= sprintf("%.6f %.6f %.6f %.6f 0 0 cm\n", $cos, $sin, -$sin, $cos);
        $page->stream .= sprintf("1 0 0 1 %.4f %.4f cm\n", -$cx, -$cy);

        $page->stream .= sprintf("%.4f %.4f %.4f rg\n", $bgColor[0], $bgColor[1], $bgColor[2]);
        $page->stream .= sprintf("%.4f %.4f %.4f RG\n", $color[0], $color[1], $color[2]);
        $page->stream .= sprintf("%.4f w\n", $borderWidth);
        $this->roundedRect($page, $x, $y, $w, $h, $radius);
        $page->stream .= "B\n";

        $page->text($cx, $cy - $fontSize / 3, $text, 'helvetica', $fontSize, $color, 'bold', 'normal', 'center');

        $page->stream .= "Q\n";
    }

    private function roundedRect(PdfPage $page, float $x, float $y, float $w, float $h, float $r): void
    {
        $page->stream .= sprintf("%.4f %.4f m\n", $x + $r, $y);
        $page->stream .= sprintf("%.4f %.4f l\n", $x + $w - $r, $y);
        $page->stream .= sprintf("%.4f %.4f %.4f 270 360 arc\n", $x + $w - $r, $y + $r, $r);
        $page->stream .= sprintf("%.4f %.4f l\n", $x + $w, $y + $h - $r);
        $page->stream .= sprintf("%.4f %.4f %.4f 0 90 arc\n", $x + $w - $r, $y + $h - $r, $r);
        $page->stream .= sprintf("%.4f %.4f l\n", $x + $r, $y + $h);
        $page->stream .= sprintf("%.4f %.4f %.4f 90 180 arc\n", $x + $r, $y + $h - $r, $r);
        $page->stream .= sprintf("%.4f %.4f l\n", $x, $y + $r);
        $page->stream .= sprintf("%.4f %.4f %.4f 180 270 arc\n", $x + $r, $y + $r, $r);
        $page->stream .= "h\n";
    }

    private function renderQuote(
        PdfPage $page,
        array $el,
        array $data,
        float $x,
        float $y,
        float $w,
        float $h
    ): void {
        $barColor = $el['barColor'] ?? [0.5, 0.5, 0.5];
        $style = $el['style'] ?? [];
        $font = $style['font'] ?? 'times';
        $size = $style['size'] ?? 12;
        $color = $style['color'] ?? [0.2, 0.2, 0.2];
        $text = $this->resolver->resolve($el['text'] ?? '', $data);
        $author = $this->resolver->resolve($el['author'] ?? '', $data);

        $page->rectangle($x, $y, 4, $h, $barColor);

        $textX = $x + 14;
        $textY = $y + $h - $size - 4;
        $textW = $w - 20;
        $page->textWrapped($textX, $textY, $text, $font, $size, $color, 'normal', 'italic', $textW);

        if ($author !== '') {
            $authorY = $y + 4;
            $page->text($x + $w - 10, $authorY, '— ' . $author, 'times', 9, [0.5, 0.5, 0.5], 'normal', 'normal', 'right');
        }
    }

    private function renderCallout(
        PdfPage $page,
        array $el,
        array $data,
        float $x,
        float $y,
        float $w,
        float $h
    ): void {
        $bgColor = $el['bgColor'] ?? [0.9, 0.95, 1];
        $borderColor = $el['borderColor'] ?? [0.2, 0.4, 0.8];
        $text = $this->resolver->resolve($el['text'] ?? '', $data);

        $page->rectangle($x, $y, $w, $h, $bgColor);
        $page->rectangle($x, $y, 4, $h, $borderColor);

        $textX = $x + 14;
        $textY = $y + $h / 2 - 4;
        $page->text($textX, $textY, $text, 'helvetica', 10, [0.2, 0.2, 0.2]);
    }

    private function renderCodeBlock(
        PdfPage $page,
        array $el,
        array $data,
        float $x,
        float $y,
        float $w,
        float $h
    ): void {
        $bgColor = [0.12, 0.12, 0.12];
        $textColor = [0.83, 0.83, 0.83];
        $text = $this->resolver->resolve($el['text'] ?? '', $data);
        $language = $el['language'] ?? '';

        $page->rectangle($x, $y, $w, $h, $bgColor, $bgColor, 0);

        if ($language !== '') {
            $page->text($x + 8, $y + $h - 10, strtoupper($language), 'helvetica', 7, [0.4, 0.4, 0.4]);
        }

        $lines = explode("\n", $text);
        $lineY = $y + $h - 20;
        $lineHeight = 10;
        foreach ($lines as $line) {
            if ($lineY < $y + 4) break;
            $page->text($x + 8, $lineY, $line, 'courier', 9, $textColor);
            $lineY -= $lineHeight;
        }
    }

    private function renderProgressBar(
        PdfPage $page,
        array $el,
        array $data,
        float $x,
        float $y,
        float $w,
        float $h
    ): void {
        $value = max(0, min(100, $el['value'] ?? 0));
        $color = $el['color'] ?? [0.2, 0.6, 0.9];
        $bgColor = $el['bgColor'] ?? [0.9, 0.9, 0.9];
        $label = $this->resolver->resolve($el['label'] ?? '', $data);

        $barH = min($h - 6, 6);
        $barY = $y + ($h - $barH) / 2;

        $page->rectangle($x, $barY, $w, $barH, $bgColor, $bgColor, 0);

        $fillW = ($value / 100) * $w;
        if ($fillW > 0) {
            $page->rectangle($x, $barY, $fillW, $barH, $color, $color, 0);
        }

        if ($label !== '') {
            $page->text($x + $w / 2, $barY - 3, $label, 'helvetica', 8, [0.4, 0.4, 0.4], 'normal', 'normal', 'center');
        }
    }

    private function renderIcon(
        PdfPage $page,
        array $el,
        float $x,
        float $y,
        float $w,
        float $h
    ): void {
        $color = $el['color'] ?? [0, 0.6, 0];
        $size = min($w, $h) * 0.6;
        $cx = $x + $w / 2;
        $cy = $y + $h / 2;

        $page->stream .= "q\n";
        $page->stream .= "{$color[0]} {$color[1]} {$color[2]} rg\n";

        switch ($el['name'] ?? 'check') {
            case 'check':
                $page->line($cx - $size * 0.3, $cy, $cx - $size * 0.05, $cy - $size * 0.3, $color, $size * 0.12);
                $page->line($cx - $size * 0.05, $cy - $size * 0.3, $cx + $size * 0.35, $cy + $size * 0.3, $color, $size * 0.12);
                break;
            case 'warning':
                $page->polygon([
                    [$cx, $cy + $size * 0.4],
                    [$cx - $size * 0.35, $cy - $size * 0.3],
                    [$cx + $size * 0.35, $cy - $size * 0.3],
                ], $color);
                $page->text($cx - 2, $cy - $size * 0.15, '!', 'helvetica', $size * 0.4, [1, 1, 1]);
                break;
            case 'info':
                $page->circle($cx, $cy, $size * 0.4, $color);
                $page->text($cx - 1.5, $cy - $size * 0.1, 'i', 'times', $size * 0.35, [1, 1, 1], 'bold');
                break;
            case 'error':
                $page->circle($cx, $cy, $size * 0.4, $color);
                $page->line($cx - $size * 0.15, $cy + $size * 0.15, $cx + $size * 0.15, $cy - $size * 0.15, [1, 1, 1], $size * 0.08);
                $page->line($cx + $size * 0.15, $cy + $size * 0.15, $cx - $size * 0.15, $cy - $size * 0.15, [1, 1, 1], $size * 0.08);
                break;
            case 'star':
                $points = [];
                for ($i = 0; $i < 10; $i++) {
                    $angle = ($i * 36 - 90) * M_PI / 180;
                    $r = $i % 2 === 0 ? $size * 0.4 : $size * 0.18;
                    $points[] = [$cx + $r * cos($angle), $cy + $r * sin($angle)];
                }
                $page->polygon($points, $color);
                break;
            case 'heart':
                $page->circle($cx - $size * 0.15, $cy + $size * 0.05, $size * 0.18, $color);
                $page->circle($cx + $size * 0.15, $cy + $size * 0.05, $size * 0.18, $color);
                $page->polygon([
                    [$cx - $size * 0.33, $cy + $size * 0.1],
                    [$cx, $cy - $size * 0.3],
                    [$cx + $size * 0.33, $cy + $size * 0.1],
                ], $color);
                break;
            case 'arrow':
                $page->line($cx - $size * 0.3, $cy, $cx + $size * 0.3, $cy, $color, $size * 0.1);
                $page->line($cx + $size * 0.15, $cy + $size * 0.15, $cx + $size * 0.3, $cy, $color, $size * 0.1);
                $page->line($cx + $size * 0.15, $cy - $size * 0.15, $cx + $size * 0.3, $cy, $color, $size * 0.1);
                break;
        }

        $page->stream .= "Q\n";
    }

    private function renderBarcode(
        PdfPage $page,
        array $el,
        array $data,
        float $x,
        float $y,
        float $w,
        float $h
    ): void {
        $text = $this->resolver->resolve($el['text'] ?? '', $data);
        $showText = $el['showText'] ?? true;
        $bars = $this->generateBarcode($text, $el['format'] ?? 'code128');

        $barCount = count($bars);
        if ($barCount === 0) return;

        $barH = $showText ? $h * 0.8 : $h;
        $barW = $w / $barCount;

        $page->stream .= "q\n";
        $page->stream .= "0 0 0 rg\n";

        for ($i = 0; $i < $barCount; $i++) {
            if ($bars[$i]) {
                $bx = $x + $i * $barW;
                $page->stream .= sprintf("%.4f %.4f %.4f %.4f re f\n", $bx, $y + ($h - $barH), $barW, $barH);
            }
        }

        $page->stream .= "Q\n";

        if ($showText) {
            $page->text($x + $w / 2, $y + 2, $text, 'courier', 8, [0, 0, 0], 'normal', 'normal', 'center');
        }
    }

    private function generateBarcode(string $text, string $format): array
    {
        $bars = [];
        $len = strlen($text);

        switch ($format) {
            case 'code39':
                for ($i = 0; $i < $len; $i++) {
                    $code = ord($text[$i]) % 16;
                    for ($b = 0; $b < 4; $b++) {
                        $bars[] = ($code >> $b) & 1;
                        $bars[] = (($code >> $b) & 1) ? 0 : 1;
                    }
                }
                break;
            case 'ean13':
                for ($i = 0; $i < max($len, 13); $i++) {
                    $n = intval($text[$i] ?? '0');
                    $bars[] = $n % 2;
                    $bars[] = $n % 3 === 0 ? 1 : 0;
                    $bars[] = $n % 2 === 0 ? 1 : 0;
                }
                break;
            default:
                for ($i = 0; $i < $len; $i++) {
                    $code = ord($text[$i]);
                    $bars[] = $code % 2;
                    $bars[] = ($code % 4) >= 2 ? 1 : 0;
                    $bars[] = ($code % 8) >= 4 ? 1 : 0;
                    $bars[] = $code % 2;
                }
                break;
        }

        return $bars;
    }

    private function renderChart(
        PdfPage $page,
        array $el,
        float $x,
        float $y,
        float $w,
        float $h
    ): void {
        $chartType = $el['chartType'] ?? 'bar';
        $data = $el['data'] ?? [];
        $colors = $el['colors'] ?? [[0.2, 0.4, 0.8]];

        if (empty($data)) return;

        $maxVal = max(array_column($data, 'value'));
        if ($maxVal === 0) $maxVal = 1;

        $padding = 4;
        $chartW = $w - $padding * 2;
        $chartH = $h - $padding * 2;

        switch ($chartType) {
            case 'bar':
                $barCount = count($data);
                $barW = $chartW / $barCount * 0.7;
                $gap = $chartW / $barCount * 0.3;

                foreach ($data as $i => $d) {
                    $val = $d['value'];
                    $barH = ($val / $maxVal) * $chartH;
                    $color = $colors[$i % count($colors)];
                    $bx = $x + $padding + $i * ($barW + $gap) + $gap / 2;
                    $by = $y + $padding + $chartH - $barH;
                    $page->rectangle($bx, $by, $barW, $barH, $color, $color, 0);
                }
                break;

            case 'pie':
                $total = array_sum(array_column($data, 'value'));
                if ($total === 0) return;

                $cx = $x + $w / 2;
                $cy = $y + $h / 2;
                $radius = min($chartW, $chartH) / 2 - 2;

                $cumAngle = 0;
                foreach ($data as $i => $d) {
                    $angle = ($d['value'] / $total) * 360;
                    $color = $colors[$i % count($colors)];

                    $startRad = $cumAngle * M_PI / 180;
                    $endRad = ($cumAngle + $angle) * M_PI / 180;

                    $page->stream .= "q\n";
                    $page->stream .= "{$color[0]} {$color[1]} {$color[2]} rg\n";
                    $page->stream .= sprintf("%.4f %.4f m\n", $cx, $cy);
                    $page->stream .= sprintf("%.4f %.4f l\n", $cx + $radius * cos($startRad), $cy + $radius * sin($startRad));

                    $steps = max(2, (int)($angle / 5));
                    for ($s = 1; $s <= $steps; $s++) {
                        $a = $startRad + ($endRad - $startRad) * ($s / $steps);
                        $page->stream .= sprintf("%.4f %.4f l\n", $cx + $radius * cos($a), $cy + $radius * sin($a));
                    }

                    $page->stream .= "f\nQ\n";
                    $cumAngle += $angle;
                }
                break;

            case 'line':
                $points = [];
                foreach ($data as $i => $d) {
                    $px = $x + $padding + ($i / max(count($data) - 1, 1)) * $chartW;
                    $py = $y + $padding + (1 - $d['value'] / $maxVal) * $chartH;
                    $points[] = [$px, $py];
                }

                $color = $colors[0] ?? [0, 0, 0];
                for ($i = 0; $i < count($points) - 1; $i++) {
                    $page->line($points[$i][0], $points[$i][1], $points[$i + 1][0], $points[$i + 1][1], $color, 1);
                }

                foreach ($points as $pt) {
                    $page->circle($pt[0], $pt[1], 2, $color);
                }
                break;
        }
    }

    private function renderChecklist(
        PdfPage $page,
        array $el,
        array $data,
        float $x,
        float $y,
        float $w,
        float $h
    ): void {
        $items = $el['items'] ?? [];
        $size = ($el['size'] ?? 11) * ($el['_mmToPt'] ?? (72 / 25.4));
        $color = $el['color'] ?? [0, 0, 0];
        $checkedColor = $el['checkedColor'] ?? [0, 0.6, 0];
        $gap = ($el['gap'] ?? 3) * ($el['_mmToPt'] ?? (72 / 25.4));
        $checkboxSize = $size * 0.9;

        $currentY = $y + $h;

        foreach ($items as $item) {
            $currentY -= $size;

            $cbColor = !empty($item['checked']) ? $checkedColor : $color;
            $page->checkbox($x, $currentY, $checkboxSize, !empty($item['checked']), $cbColor);

            $textColor = $color;
            $page->text(
                $x + $checkboxSize + $size * 0.3,
                $currentY + $checkboxSize * 0.15,
                $w - $checkboxSize - $size * 0.3,
                $this->resolver->resolve($item['text'] ?? '', $data),
                $textColor,
                'helvetica',
                'normal',
                $size,
                'left'
            );

            $currentY -= $gap;
        }
    }

    private function renderRadio(
        PdfPage $page,
        array $el,
        array $data,
        float $x,
        float $y,
        float $w,
        float $h
    ): void {
        $items = $el['items'] ?? [];
        $size = ($el['size'] ?? 11) * ($el['_mmToPt'] ?? (72 / 25.4));
        $color = $el['color'] ?? [0, 0, 0];
        $selectedColor = $el['selectedColor'] ?? [0, 0.5, 1];
        $gap = ($el['gap'] ?? 3) * ($el['_mmToPt'] ?? (72 / 25.4));
        $radioSize = $size * 0.9;

        $currentY = $y + $h;

        foreach ($items as $item) {
            $currentY -= $size;

            $dotColor = !empty($item['selected']) ? $selectedColor : $color;
            $page->radio($x, $currentY, $radioSize, !empty($item['selected']), $dotColor);

            $textColor = $color;
            $page->text(
                $x + $radioSize + $size * 0.3,
                $currentY + $radioSize * 0.15,
                $w - $radioSize - $size * 0.3,
                $this->resolver->resolve($item['text'] ?? '', $data),
                $textColor,
                'helvetica',
                'normal',
                $size,
                'left'
            );

            $currentY -= $gap;
        }
    }
}
