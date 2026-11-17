<?php

namespace App\Services;

/** Small dependency-free PDF writer for the internal one-page demo document. */
final class MinimalPdfGenerator
{
    /** @param list<string> $lines */
    public function make(array $lines): string
    {
        $content = "BT\n/F1 16 Tf\n50 790 Td\n";
        foreach ($lines as $index => $line) {
            if ($index > 0) $content .= "0 -22 Td\n";
            $line = iconv('UTF-8', 'Windows-1252//TRANSLIT', $line) ?: $line;
            $line = preg_replace('/[^\x20-\x7E\x80-\xFF]/', ' ', $line) ?? '';
            $line = substr($line, 0, 100);
            $content .= '/F1 '.($index === 0 ? 16 : 10)." Tf\n(".str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $line).") Tj\n";
        }
        $content .= "ET\n";

        $objects = [
            '<< /Type /Catalog /Pages 2 0 R >>',
            '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>',
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>',
            '<< /Length '.strlen($content)." >>\nstream\n".$content."endstream",
        ];
        $pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
        $offsets = [0];
        foreach ($objects as $index => $object) {
            $offsets[] = strlen($pdf);
            $pdf .= ($index + 1)." 0 obj\n".$object."\nendobj\n";
        }
        $xref = strlen($pdf);
        $pdf .= 'xref'."\n0 ".(count($objects) + 1)."\n0000000000 65535 f \n";
        foreach (array_slice($offsets, 1) as $offset) $pdf .= sprintf('%010d 00000 n ', $offset)."\n";
        $pdf .= 'trailer'."\n<< /Size ".(count($objects) + 1)." /Root 1 0 R >>\nstartxref\n{$xref}\n%%EOF";

        return $pdf;
    }
}
