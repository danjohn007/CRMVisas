<?php
/**
 * Generador PDF ligero para reportes administrativos.
 * No requiere dependencias externas.
 */
class SimplePdf
{
    private array $pages = [[]];
    private int $pageIndex = 0;
    private float $pageWidth = 612.0;
    private float $pageHeight = 792.0;
    private float $margin = 38.0;
    private float $y = 748.0;

    private array $navy = [0.08, 0.18, 0.36];
    private array $blue = [0.12, 0.35, 0.65];
    private array $light = [0.94, 0.96, 0.99];
    private array $border = [0.82, 0.85, 0.89];
    private array $muted = [0.35, 0.39, 0.45];
    private array $black = [0.12, 0.14, 0.17];

    public function addHeader(string $brand, string $title, string $subtitle = '', string $status = ''): void
    {
        $this->ensureSpace(82);
        $x = $this->margin;
        $w = $this->pageWidth - ($this->margin * 2);

        $this->rect($x, $this->y - 62, $w, 62, $this->light, $this->border);
        $this->rect($x, $this->y - 62, 6, 62, $this->navy, null);

        $this->drawText($x + 18, $this->y - 18, $brand, 8.5, true, $this->muted);
        $this->drawText($x + 18, $this->y - 38, $title, 17, true, $this->navy);
        if ($subtitle !== '') {
            $this->drawText($x + 18, $this->y - 53, $subtitle, 8.5, false, $this->muted);
        }

        if ($status !== '') {
            $label = strtoupper($status);
            $labelW = max(72, min(130, strlen($this->toAscii($label)) * 5.4 + 20));
            $this->rect($x + $w - $labelW - 12, $this->y - 35, $labelW, 22, $this->navy, null);
            $this->drawText($x + $w - $labelW, $this->y - 28, $label, 8, true, [1, 1, 1]);
        }

        $this->y -= 76;
    }

    public function addSection(string $title): void
    {
        $this->ensureSpace(30);
        $x = $this->margin;
        $w = $this->pageWidth - ($this->margin * 2);
        $this->rect($x, $this->y - 20, $w, 20, $this->navy, null);
        $this->drawText($x + 10, $this->y - 14, strtoupper($title), 9, true, [1, 1, 1]);
        $this->y -= 28;
    }

    /**
     * $items = [['label'=>'Ingresos','value'=>'$1,000.00'], ...]
     */
    public function addKeyValueGrid(array $items, int $columns = 2): void
    {
        $columns = max(1, min(3, $columns));
        $gap = 8.0;
        $fullW = $this->pageWidth - ($this->margin * 2);
        $cellW = ($fullW - (($columns - 1) * $gap)) / $columns;
        $cellH = 38.0;

        $rows = array_chunk($items, $columns);
        foreach ($rows as $row) {
            $this->ensureSpace($cellH + 8);
            foreach ($row as $i => $item) {
                $x = $this->margin + $i * ($cellW + $gap);
                $this->rect($x, $this->y - $cellH, $cellW, $cellH, [0.98, 0.985, 0.995], $this->border);
                $this->drawText($x + 9, $this->y - 14, (string)($item['label'] ?? ''), 7.5, true, $this->muted);
                $valueColor = $item['color'] ?? $this->black;
                $this->drawText($x + 9, $this->y - 30, (string)($item['value'] ?? ''), 11, true, $valueColor);
            }
            $this->y -= $cellH + 8;
        }
    }

    /**
     * $rows: arrays in the same order as $headers.
     * $widths: PDF points; if omitted, distributed equally.
     * $aligns: left|right|center for each column.
     */
    public function addTable(array $headers, array $rows, array $widths = [], array $aligns = []): void
    {
        $count = count($headers);
        if ($count === 0) return;

        $fullW = $this->pageWidth - ($this->margin * 2);
        if (count($widths) !== $count || array_sum($widths) <= 0) {
            $widths = array_fill(0, $count, $fullW / $count);
        } else {
            $sum = array_sum($widths);
            if (abs($sum - $fullW) > 0.1) {
                $scale = $fullW / $sum;
                foreach ($widths as &$w) $w *= $scale;
                unset($w);
            }
        }
        if (count($aligns) !== $count) {
            $aligns = array_fill(0, $count, 'left');
        }

        $this->drawTableHeader($headers, $widths, $aligns);

        foreach ($rows as $row) {
            $cells = array_values($row);
            $wrapped = [];
            $maxLines = 1;

            for ($i = 0; $i < $count; $i++) {
                $cellText = isset($cells[$i]) ? (string)$cells[$i] : '';
                $wrapped[$i] = $this->wrapForWidth($cellText, max(24, $widths[$i] - 10), 7.5);
                $maxLines = max($maxLines, count($wrapped[$i]));
            }

            $rowH = max(22, 8 + ($maxLines * 9.5));
            if (($this->y - $rowH) < 52) {
                $this->newPage();
                $this->drawTableHeader($headers, $widths, $aligns);
            }

            $x = $this->margin;
            for ($i = 0; $i < $count; $i++) {
                $this->rect($x, $this->y - $rowH, $widths[$i], $rowH, [1,1,1], $this->border);
                $lineY = $this->y - 12;
                foreach ($wrapped[$i] as $line) {
                    $this->drawCellText($x + 5, $lineY, $widths[$i] - 10, $line, 7.5, $aligns[$i]);
                    $lineY -= 9.5;
                }
                $x += $widths[$i];
            }
            $this->y -= $rowH;
        }
        $this->y -= 10;
    }

    public function addNote(string $label, string $text): void
    {
        if (trim($text) === '') return;
        $lines = $this->wrapForWidth($label . ': ' . $text, $this->pageWidth - ($this->margin * 2) - 20, 8);
        $h = 14 + count($lines) * 10;
        $this->ensureSpace($h + 8);
        $this->rect($this->margin, $this->y - $h, $this->pageWidth - ($this->margin * 2), $h, [1.0, 0.98, 0.91], [0.92, 0.78, 0.40]);
        $yy = $this->y - 14;
        foreach ($lines as $line) {
            $this->drawText($this->margin + 10, $yy, $line, 8, false, [0.35, 0.27, 0.08]);
            $yy -= 10;
        }
        $this->y -= $h + 8;
    }

    // Compatibilidad con la versión anterior.
    public function addLine(string $text = ''): void
    {
        $lines = $this->wrapForWidth($text, $this->pageWidth - ($this->margin * 2), 9);
        foreach ($lines as $line) {
            $this->ensureSpace(13);
            $this->drawText($this->margin, $this->y, $line, 9, false, $this->black);
            $this->y -= 13;
        }
    }

    public function addBlank(): void
    {
        $this->ensureSpace(10);
        $this->y -= 10;
    }

    public function output(string $filename): void
    {
        foreach ($this->pages as $i => $_) {
            $pageNo = $i + 1;
            $total = count($this->pages);
            $this->pageIndex = $i;
            $this->drawText($this->margin, 28, 'Reporte generado por la intranet', 7, false, $this->muted);
            $this->drawText($this->pageWidth - $this->margin - 74, 28, "Pagina $pageNo de $total", 7, false, $this->muted);
            $this->line($this->margin, 38, $this->pageWidth - $this->margin, 38, $this->border);
        }

        $objects = [];
        $pageObjectNumbers = [];
        $contentObjectNumbers = [];

        // 1 Catalog, 2 Pages, 3 Helvetica, 4 Helvetica-Bold
        $nextObject = 5;
        foreach ($this->pages as $_page) {
            $pageObjectNumbers[] = $nextObject++;
            $contentObjectNumbers[] = $nextObject++;
        }

        $objects[1] = '<< /Type /Catalog /Pages 2 0 R >>';
        $kids = implode(' ', array_map(fn($n) => $n . ' 0 R', $pageObjectNumbers));
        $objects[2] = '<< /Type /Pages /Kids [' . $kids . '] /Count ' . count($pageObjectNumbers) . ' >>';
        $objects[3] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>';
        $objects[4] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>';

        foreach ($this->pages as $i => $commands) {
            $pageObj = $pageObjectNumbers[$i];
            $contentObj = $contentObjectNumbers[$i];
            $objects[$pageObj] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Resources << /Font << /F1 3 0 R /F2 4 0 R >> >> /Contents ' . $contentObj . ' 0 R >>';
            $stream = implode("\n", $commands) . "\n";
            $objects[$contentObj] = "<< /Length " . strlen($stream) . " >>\nstream\n" . $stream . "endstream";
        }

        ksort($objects);
        $pdf = "%PDF-1.4\n";
        $offsets = [0];
        foreach ($objects as $num => $body) {
            $offsets[$num] = strlen($pdf);
            $pdf .= $num . " 0 obj\n" . $body . "\nendobj\n";
        }

        $xref = strlen($pdf);
        $maxObj = max(array_keys($objects));
        $pdf .= "xref\n0 " . ($maxObj + 1) . "\n";
        $pdf .= "0000000000 65535 f \n";
        for ($i = 1; $i <= $maxObj; $i++) {
            $offset = $offsets[$i] ?? 0;
            $pdf .= sprintf('%010d 00000 n ', $offset) . "\n";
        }

        $pdf .= "trailer\n<< /Size " . ($maxObj + 1) . " /Root 1 0 R >>\nstartxref\n" . $xref . "\n%%EOF";

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . basename($filename) . '"');
        header('Content-Length: ' . strlen($pdf));
        echo $pdf;
        exit;
    }

    private function drawTableHeader(array $headers, array $widths, array $aligns): void
    {
        $this->ensureSpace(26);
        $x = $this->margin;
        $h = 24;
        foreach ($headers as $i => $header) {
            $this->rect($x, $this->y - $h, $widths[$i], $h, $this->light, $this->border);
            $this->drawCellText($x + 5, $this->y - 15, $widths[$i] - 10, (string)$header, 7.3, $aligns[$i], true, $this->navy);
            $x += $widths[$i];
        }
        $this->y -= $h;
    }

    private function drawCellText(float $x, float $y, float $width, string $text, float $size, string $align = 'left', bool $bold = false, ?array $color = null): void
    {
        $ascii = $this->toAscii($text);
        $approx = strlen($ascii) * $size * 0.50;
        if ($align === 'right') {
            $x += max(0, $width - $approx);
        } elseif ($align === 'center') {
            $x += max(0, ($width - $approx) / 2);
        }
        $this->drawText($x, $y, $ascii, $size, $bold, $color ?? $this->black);
    }

    private function ensureSpace(float $height): void
    {
        if (($this->y - $height) < 52) {
            $this->newPage();
        }
    }

    private function newPage(): void
    {
        $this->pages[] = [];
        $this->pageIndex = count($this->pages) - 1;
        $this->y = 748;
    }

    private function drawText(float $x, float $y, string $text, float $size = 9, bool $bold = false, ?array $color = null): void
    {
        $text = $this->toAscii($text);
        $font = $bold ? '/F2' : '/F1';
        $c = $color ?? $this->black;
        $cmd = sprintf(
            "BT %.3F %.3F %.3F rg %s %.2F Tf 1 0 0 1 %.2F %.2F Tm (%s) Tj ET",
            $c[0], $c[1], $c[2], $font, $size, $x, $y, $this->escapePdfText($text)
        );
        $this->pages[$this->pageIndex][] = $cmd;
    }

    private function rect(float $x, float $y, float $w, float $h, ?array $fill = null, ?array $stroke = null): void
    {
        $parts = ['q'];
        if ($fill !== null) {
            $parts[] = sprintf("%.3F %.3F %.3F rg", $fill[0], $fill[1], $fill[2]);
        }
        if ($stroke !== null) {
            $parts[] = sprintf("%.3F %.3F %.3F RG", $stroke[0], $stroke[1], $stroke[2]);
            $parts[] = '0.6 w';
        }
        $op = $fill !== null && $stroke !== null ? 'B' : ($fill !== null ? 'f' : 'S');
        $parts[] = sprintf("%.2F %.2F %.2F %.2F re %s", $x, $y, $w, $h, $op);
        $parts[] = 'Q';
        $this->pages[$this->pageIndex][] = implode("\n", $parts);
    }

    private function line(float $x1, float $y1, float $x2, float $y2, array $color): void
    {
        $this->pages[$this->pageIndex][] = sprintf(
            "q %.3F %.3F %.3F RG 0.5 w %.2F %.2F m %.2F %.2F l S Q",
            $color[0], $color[1], $color[2], $x1, $y1, $x2, $y2
        );
    }

    private function wrapForWidth(string $text, float $width, float $fontSize): array
    {
        $ascii = trim($this->toAscii($text));
        if ($ascii === '') return [''];
        $maxChars = max(6, (int) floor($width / max(3.5, $fontSize * 0.52)));

        $result = [];
        foreach (preg_split('/\r?\n/', $ascii) as $paragraph) {
            $paragraph = trim($paragraph);
            if ($paragraph === '') {
                $result[] = '';
                continue;
            }
            $wrapped = wordwrap($paragraph, $maxChars, "\n", true);
            foreach (explode("\n", $wrapped) as $line) $result[] = $line;
        }
        return $result ?: [''];
    }

    private function toAscii(string $text): string
    {
        if (function_exists('iconv')) {
            $converted = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
            if ($converted !== false) return $converted;
        }
        return preg_replace('/[^\x20-\x7E]/', '?', $text) ?? $text;
    }

    private function escapePdfText(string $text): string
    {
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
    }
}
