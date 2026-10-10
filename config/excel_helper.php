<?php
/**
 * Helper Pembaca & Pembuat File Excel (XLSX, XLS) & CSV
 * Perpustakaan Digital SMKN 2 Sumbawa Besar
 * Mandiri tanpa dependensi composer (menggunakan ZipArchive & SimpleXML)
 */

if (!defined('BASE_PATH')) {
    require_once __DIR__ . '/config.php';
}

/**
 * Konversi nama kolom Excel (A, B, AA, AB...) ke index array 0-based
 */
function excel_col_to_index($col) {
    $col = strtoupper(trim($col));
    $len = strlen($col);
    $idx = 0;
    for ($i = 0; $i < $len; $i++) {
        $idx = $idx * 26 + (ord($col[$i]) - ord('A') + 1);
    }
    return $idx - 1;
}

if (!function_exists('col_to_letter')) {
    /**
     * Konversi index kolom 0-based ke huruf Excel (0 -> A, 1 -> B, 26 -> AA...)
     */
    function col_to_letter($colIndex) {
        $letter = '';
        $colIndex++;
        while ($colIndex > 0) {
            $m = ($colIndex - 1) % 26;
            $letter = chr(65 + $m) . $letter;
            $colIndex = (int)(($colIndex - $m) / 26);
        }
        return $letter;
    }
}

/**
 * Parsing file Spreadsheet (.xlsx, .xls, .csv, .txt) menjadi array baris data
 * @param string $filePath Path file fisik di server (misal $_FILES['...']['tmp_name'])
 * @param string $originalName Nama asli file untuk deteksi ekstensi
 * @return array Array 2D berisi data per baris [ [col0, col1, ...], ... ]
 * @throws Exception
 */
function parse_spreadsheet_file($filePath, $originalName) {
    if (!file_exists($filePath) || !is_readable($filePath)) {
        throw new Exception("File tidak ditemukan atau tidak dapat dibaca: " . htmlspecialchars($originalName));
    }

    $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

    if ($ext === 'xlsx') {
        return parse_xlsx_file($filePath);
    } elseif ($ext === 'xls') {
        return parse_xls_file($filePath);
    } elseif (in_array($ext, ['csv', 'txt'])) {
        return parse_csv_file($filePath);
    } else {
        throw new Exception("Format file .$ext tidak didukung. Silakan gunakan format Excel (.xlsx, .xls) atau .csv.");
    }
}

/**
 * Parsing OpenXML Excel (.xlsx)
 */
function parse_xlsx_file($filePath) {
    $zip = new ZipArchive();
    $res = $zip->open($filePath);
    if ($res !== true) {
        throw new Exception("Gagal membuka file Excel (.xlsx). Pastikan file tidak korup atau diproteksi password.");
    }

    // 1. Baca Shared Strings jika ada
    $sharedStrings = [];
    $sharedStringsXml = $zip->getFromName('xl/sharedStrings.xml');
    if ($sharedStringsXml !== false) {
        // Gunakan SimpleXML
        $xml = @simplexml_load_string($sharedStringsXml);
        if ($xml) {
            foreach ($xml->si as $si) {
                if (isset($si->t)) {
                    $sharedStrings[] = (string)$si->t;
                } elseif (isset($si->r)) {
                    $text = '';
                    foreach ($si->r as $r) {
                        $text .= (string)$r->t;
                    }
                    $sharedStrings[] = $text;
                } else {
                    $sharedStrings[] = '';
                }
            }
        }
    }

    // 2. Tentukan sheet yang akan dibaca (default: sheet1.xml)
    $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
    if ($sheetXml === false) {
        // Coba cari sheet lain
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (strpos($name, 'xl/worksheets/sheet') === 0) {
                $sheetXml = $zip->getFromName($name);
                break;
            }
        }
    }

    $zip->close();

    if ($sheetXml === false) {
        throw new Exception("Lembar kerja (worksheet) tidak ditemukan di dalam file Excel.");
    }

    // 3. Parse XML sheet
    $xml = @simplexml_load_string($sheetXml);
    if (!$xml || !isset($xml->sheetData)) {
        throw new Exception("Format data XML di dalam file Excel tidak valid.");
    }

    $rows = [];
    foreach ($xml->sheetData->row as $row) {
        $rowCells = [];
        $maxCol = 0;

        foreach ($row->c as $cell) {
            $ref = (string)$cell['r']; // e.g. "A1", "B2"
            $type = (string)$cell['t']; // e.g. "s" (string), "inlineStr", "b", etc.

            // Ambil nama kolom alfabetis
            preg_match('/^([A-Z]+)(\d+)$/', $ref, $matches);
            $colLetter = $matches[1] ?? 'A';
            $colIndex = excel_col_to_index($colLetter);

            if ($colIndex > $maxCol) {
                $maxCol = $colIndex;
            }

            $val = '';
            if ($type === 's') {
                $sIndex = (int)(string)$cell->v;
                $val = $sharedStrings[$sIndex] ?? '';
            } elseif ($type === 'inlineStr' && isset($cell->is->t)) {
                $val = (string)$cell->is->t;
            } elseif ($type === 'b') {
                $val = ((string)$cell->v === '1') ? '1' : '0';
            } else {
                $val = isset($cell->v) ? (string)$cell->v : '';
            }

            $rowCells[$colIndex] = trim($val);
        }

        // Susun baris secara berurutan, isi kolom kosong jika ada lompatan
        $orderedRow = [];
        for ($c = 0; $c <= $maxCol; $c++) {
            $orderedRow[$c] = $rowCells[$c] ?? '';
        }

        // Cek jika baris tidak sepenuhnya kosong
        $hasData = false;
        foreach ($orderedRow as $v) {
            if ($v !== '') {
                $hasData = true;
                break;
            }
        }

        if ($hasData) {
            $rows[] = $orderedRow;
        }
    }

    return $rows;
}

/**
 * Parsing file .xls (Bisa berupa XML Spreadsheet 2003, HTML Table, atau fallback CSV)
 */
function parse_xls_file($filePath) {
    $content = file_get_contents($filePath);
    if ($content === false) {
        throw new Exception("Tidak dapat membaca isi file .xls.");
    }

    // 1. Cek apakah ini XML Spreadsheet 2003
    if (strpos($content, '<?xml') !== false && (strpos($content, 'Workbook') !== false || strpos($content, 'urn:schemas-microsoft-com:office:spreadsheet') !== false)) {
        // Bersihkan namespace agar mudah di-parse
        $cleanXml = preg_replace('/(<\/?)(\w+):([^>]*>)/', '$1$3', $content);
        $xml = @simplexml_load_string($cleanXml);
        if ($xml && isset($xml->Worksheet->Table->Row)) {
            $rows = [];
            foreach ($xml->Worksheet->Table->Row as $row) {
                $cells = [];
                foreach ($row->Cell as $cell) {
                    $cells[] = isset($cell->Data) ? trim((string)$cell->Data) : '';
                }
                if (!empty(array_filter($cells, fn($x) => $x !== ''))) {
                    $rows[] = $cells;
                }
            }
            return $rows;
        }
    }

    // 2. Cek apakah ini HTML Table (banyak aplikasi web mengekspor .xls sebagai HTML <table>)
    if (stripos($content, '<table') !== false && stripos($content, '<tr') !== false) {
        $dom = new DOMDocument();
        @$dom->loadHTML('<?xml encoding="UTF-8">' . $content);
        $rows = [];
        $trList = $dom->getElementsByTagName('tr');
        foreach ($trList as $tr) {
            $cells = [];
            $tdList = $tr->getElementsByTagName('td');
            if ($tdList->length === 0) {
                $tdList = $tr->getElementsByTagName('th');
            }
            foreach ($tdList as $td) {
                $cells[] = trim($td->textContent);
            }
            if (!empty(array_filter($cells, fn($x) => $x !== ''))) {
                $rows[] = $cells;
            }
        }
        if (!empty($rows)) {
            return $rows;
        }
    }

    // 3. Fallback: Parse sebagai CSV/TSV
    return parse_csv_file($filePath);
}

/**
 * Parsing file CSV atau TXT dengan deteksi otomatis delimiter (, atau ; atau \t)
 */
function parse_csv_file($filePath) {
    $handle = fopen($filePath, 'r');
    if (!$handle) {
        throw new Exception("Tidak dapat membuka file CSV/Text.");
    }

    // Baca baris pertama untuk deteksi delimiter dan buang UTF-8 BOM jika ada
    $firstLine = fgets($handle);
    if ($firstLine === false) {
        fclose($handle);
        return [];
    }

    // Buang BOM UTF-8
    $firstLine = preg_replace('/^\xEF\xBB\xBF/', '', $firstLine);

    // Deteksi delimiter terbanyak
    $delimiters = [',', ';', "\t", '|'];
    $bestDelimiter = ',';
    $maxCount = 0;
    foreach ($delimiters as $d) {
        $count = substr_count($firstLine, $d);
        if ($count > $maxCount) {
            $maxCount = $count;
            $bestDelimiter = $d;
        }
    }

    // Parse baris pertama
    $rows = [];
    $firstRow = str_getcsv($firstLine, $bestDelimiter, '"', '\\');
    if (!empty(array_filter($firstRow, fn($x) => trim($x) !== ''))) {
        $rows[] = array_map('trim', $firstRow);
    }

    // Parse baris-baris berikutnya
    while (($row = fgetcsv($handle, 0, $bestDelimiter, '"', '\\')) !== false) {
        $trimmed = array_map('trim', $row);
        // Lewati baris kosong
        if (empty(array_filter($trimmed, fn($x) => $x !== ''))) {
            continue;
        }
        $rows[] = $trimmed;
    }

    fclose($handle);
    return $rows;
}

/**
 * Menghasilkan file Excel (.xlsx) murni secara dinamis untuk download template
 */
function download_template_xlsx($filename, $headers, $sampleRows = []) {
    // Cari direktori temp yang writable oleh web server
    // Fallback ke direktori uploads dalam project jika sys_get_temp_dir() gagal
    $tempFile = '';
    $tmpDirs = [
        sys_get_temp_dir(),
        '/tmp',
        dirname(__DIR__) . '/uploads',
        dirname(__DIR__) . '/uploads/backups',
        dirname(__DIR__),
    ];
    foreach ($tmpDirs as $dir) {
        if (is_dir($dir) && is_writable($dir)) {
            $candidate = $dir . DIRECTORY_SEPARATOR . 'xlsx_tpl_' . uniqid() . '.tmp';
            // Coba buat file
            if (@file_put_contents($candidate, '') !== false) {
                $tempFile = $candidate;
                break;
            }
        }
    }

    if (empty($tempFile)) {
        // Sebagai last resort, gunakan output buffering + php://memory tidak bisa di ZipArchive
        // Tampilkan error yang informatif
        http_response_code(500);
        header('Content-Type: text/html; charset=UTF-8');
        echo '<p style="color:red;font-family:sans-serif;">Gagal membuat file template: tidak ada direktori temp yang dapat ditulis. Hubungi administrator.</p>';
        exit;
    }

    $zip = new ZipArchive();
    $zip->open($tempFile, ZipArchive::CREATE | ZipArchive::OVERWRITE);

    // 1. [Content_Types].xml
    $contentTypes = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
    <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
    <Default Extension="xml" ContentType="application/xml"/>
    <Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
    <Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
    <Override PartName="/xl/sharedStrings.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sharedStringTable+xml"/>
    <Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>
</Types>';
    $zip->addFromString('[Content_Types].xml', $contentTypes);

    // 2. _rels/.rels
    $rels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
    <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>
</Relationships>';
    $zip->addFromString('_rels/.rels', $rels);

    // 3. xl/_rels/workbook.xml.rels
    $wbRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
    <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>
    <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/sharedStrings" Target="sharedStrings.xml"/>
    <Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>
</Relationships>';
    $zip->addFromString('xl/_rels/workbook.xml.rels', $wbRels);

    // 4. xl/workbook.xml
    $wb = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
    <sheets>
        <sheet name="Template Data" sheetId="1" r:id="rId1"/>
    </sheets>
</workbook>';
    $zip->addFromString('xl/workbook.xml', $wb);

    // 5. xl/styles.xml (header bold & background)
    $styles = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
    <fonts count="2">
        <font><name val="Calibri"/><sz val="11"/></font>
        <font><b/><color rgb="FFFFFFFF"/><name val="Calibri"/><sz val="11"/></font>
    </fonts>
    <fills count="3">
        <fill><patternFill patternType="none"/></fill>
        <fill><patternFill patternType="gray125"/></fill>
        <fill><patternFill patternType="solid"><fgColor rgb="FF2563EB"/></patternFill></fill>
    </fills>
    <borders count="1">
        <border><left/><right/><top/><bottom/><diagonal/></border>
    </borders>
    <cellStyleXfs count="1">
        <xf numFmtId="0" fontId="0" fillId="0" borderId="0"/>
    </cellStyleXfs>
    <cellXfs count="2">
        <xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>
        <xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1"/>
    </cellXfs>
</styleSheet>';
    $zip->addFromString('xl/styles.xml', $styles);

    // Kumpulkan semua string ke sharedStrings
    $strings = [];
    $allRows = array_merge([$headers], $sampleRows);
    foreach ($allRows as $r) {
        foreach ($r as $c) {
            $str = (string)$c;
            if (!in_array($str, $strings, true)) {
                $strings[] = $str;
            }
        }
    }

    $stringMap = array_flip($strings);

    // 6. xl/sharedStrings.xml
    $ssXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n";
    $ssXml .= '<sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" count="' . count($strings) . '" uniqueCount="' . count($strings) . '">';
    foreach ($strings as $s) {
        $ssXml .= '<si><t>' . htmlspecialchars($s, ENT_QUOTES | ENT_XML1, 'UTF-8') . '</t></si>';
    }
    $ssXml .= '</sst>';
    $zip->addFromString('xl/sharedStrings.xml', $ssXml);

    // 7. xl/worksheets/sheet1.xml
    $shXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n";
    $shXml .= '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">';
    $shXml .= '<sheetData>';

    foreach ($allRows as $rIdx => $row) {
        $rowNum = $rIdx + 1;
        $isHeader = ($rIdx === 0);
        $styleAttr = $isHeader ? ' s="1"' : '';
        $shXml .= '<row r="' . $rowNum . '">';
        foreach ($row as $cIdx => $val) {
            $colLetter = col_to_letter($cIdx);
            $cellRef = $colLetter . $rowNum;
            $sId = $stringMap[(string)$val];
            $shXml .= '<c r="' . $cellRef . '" t="s"' . $styleAttr . '><v>' . $sId . '</v></c>';
        }
        $shXml .= '</row>';
    }

    $shXml .= '</sheetData>';
    $shXml .= '</worksheet>';
    $zip->addFromString('xl/worksheets/sheet1.xml', $shXml);

    $zip->close();

    // Output ke browser sebagai download
    if (ob_get_level()) {
        ob_end_clean();
    }
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . filesize($tempFile));
    header('Cache-Control: max-age=0');
    readfile($tempFile);
    @unlink($tempFile);
    exit;
}
