<?php

namespace Database\Seeders;

use App\Models\Task;

/**
 * Pembuat file contoh (PDF/XLSX/DOCX/JPG) untuk data demo, tanpa library tambahan.
 * Isinya hanya penanda "CONTOH DEMO" supaya jelas bukan dokumen asli.
 */
class DemoFiles
{
    private static function lines(string $title, ?Task $task): array
    {
        $p = $task?->project;
        return array_values(array_filter([
            'CONTOH DOKUMEN DEMO - IZINARA',
            $title,
            $p ? 'Proyek: '.$p->code.' - '.$p->name : null,
            $task ? 'Kegiatan: '.$task->wbs.' '.$task->name : null,
            'Dibuat otomatis oleh Izinara untuk simulasi. Bukan dokumen resmi.',
        ]));
    }

    private static function ascii(string $s): string
    {
        $s = preg_replace('/[^\x20-\x7E]/', '-', $s);
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $s);
    }

    public static function pdf(string $title, ?Task $task = null): string
    {
        $y = 780;
        $stream = "BT\n";
        foreach (self::lines($title, $task) as $i => $line) {
            $size = $i === 0 ? 14 : ($i === 1 ? 12 : 10);
            $stream .= "/F1 $size Tf 1 0 0 1 56 $y Tm (".self::ascii(mb_strimwidth($line, 0, 95, '...')).") Tj\n";
            $y -= $i === 0 ? 26 : 18;
        }
        $stream .= "ET\n0.06 0.42 0.36 RG 2 w 56 800 m 540 800 l S\n";
        $objs = [
            '<< /Type /Catalog /Pages 2 0 R >>',
            '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 5 0 R >> >> /Contents 4 0 R >>',
            '<< /Length '.strlen($stream)." >>\nstream\n".$stream."endstream",
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
        ];
        $out = "%PDF-1.4\n";
        $off = [];
        foreach ($objs as $i => $o) {
            $off[] = strlen($out);
            $out .= ($i + 1)." 0 obj\n$o\nendobj\n";
        }
        $x = strlen($out);
        $out .= "xref\n0 ".(count($objs) + 1)."\n0000000000 65535 f \n";
        foreach ($off as $o) {
            $out .= sprintf("%010d 00000 n \n", $o);
        }
        return $out."trailer\n<< /Size ".(count($objs) + 1)." /Root 1 0 R >>\nstartxref\n$x\n%%EOF\n";
    }

    private static function zip(array $files): ?string
    {
        if (! class_exists(\ZipArchive::class)) {
            return null;
        }
        $tmp = tempnam(sys_get_temp_dir(), 'izd');
        $z = new \ZipArchive();
        if ($z->open($tmp, \ZipArchive::OVERWRITE) !== true) {
            return null;
        }
        foreach ($files as $n => $c) {
            $z->addFromString($n, $c);
        }
        $z->close();
        $data = file_get_contents($tmp);
        @unlink($tmp);
        return $data;
    }

    private static function x(string $s): string
    {
        return htmlspecialchars($s, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    public static function docx(string $title, ?Task $task = null): ?string
    {
        $body = '';
        foreach (self::lines($title, $task) as $i => $l) {
            $b = $i < 2 ? '<w:rPr><w:b/></w:rPr>' : '';
            $body .= '<w:p><w:r>'.$b.'<w:t xml:space="preserve">'.self::x($l).'</w:t></w:r></w:p>';
        }
        return self::zip([
            '[Content_Types].xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/></Types>',
            '_rels/.rels' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/></Relationships>',
            'word/document.xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body>'.$body.'</w:body></w:document>',
        ]);
    }

    public static function xlsx(string $title, ?Task $task = null): ?string
    {
        $rows = [];
        foreach (self::lines($title, $task) as $l) {
            $rows[] = [$l];
        }
        $rows[] = [];
        $rows[] = ['No', 'Uraian', 'Qty (kg)', 'Keterangan'];
        $items = $task?->project?->items ?? collect();
        $n = 1;
        foreach ($items->take(8) as $it) {
            $rows[] = [$n++, $it->code.' '.$it->name, (float) ($it->pivot->qty_kg ?? 1000), 'contoh'];
        }
        if ($n === 1) {
            $rows[] = [1, 'Contoh baris', 1000, 'contoh'];
        }
        $xml = '';
        foreach ($rows as $r => $cols) {
            $xml .= '<row r="'.($r + 1).'">';
            foreach (array_values($cols) as $c => $v) {
                $ref = chr(65 + $c).($r + 1);
                $xml .= is_int($v) || is_float($v)
                    ? '<c r="'.$ref.'"><v>'.$v.'</v></c>'
                    : '<c r="'.$ref.'" t="inlineStr"><is><t>'.self::x((string) $v).'</t></is></c>';
            }
            $xml .= '</row>';
        }
        return self::zip([
            '[Content_Types].xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/></Types>',
            '_rels/.rels' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>',
            'xl/workbook.xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Data" sheetId="1" r:id="rId1"/></sheets></workbook>',
            'xl/_rels/workbook.xml.rels' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/></Relationships>',
            'xl/worksheets/sheet1.xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>'.$xml.'</sheetData></worksheet>',
        ]);
    }

    public static function jpg(string $title): ?string
    {
        if (! function_exists('imagecreatetruecolor')) {
            return null;
        }
        $im = imagecreatetruecolor(640, 400);
        $bg = imagecolorallocate($im, 245, 248, 246);
        $fg = imagecolorallocate($im, 15, 107, 92);
        $mut = imagecolorallocate($im, 100, 115, 110);
        imagefilledrectangle($im, 0, 0, 640, 400, $bg);
        imagefilledrectangle($im, 0, 0, 640, 46, $fg);
        imagestring($im, 5, 16, 15, 'IZINARA - CONTOH FOTO / LABEL (DEMO)', imagecolorallocate($im, 255, 255, 255));
        imagerectangle($im, 40, 80, 600, 360, $mut);
        $y = 110;
        foreach (str_split(preg_replace('/[^\x20-\x7E]/', '-', $title), 60) as $line) {
            imagestring($im, 4, 60, $y, $line, $fg);
            $y += 22;
        }
        imagestring($im, 3, 60, 320, 'Gambar ilustrasi untuk simulasi Izinara.', $mut);
        ob_start();
        imagejpeg($im, null, 80);
        imagedestroy($im);
        return ob_get_clean();
    }
}
