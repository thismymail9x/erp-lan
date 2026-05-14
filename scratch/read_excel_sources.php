<?php
$xlDir = 'writable/temp_excel/xl';
$sharedStringsFile = $xlDir . '/sharedStrings.xml';
$sheet1File = $xlDir . '/worksheets/sheet1.xml';

if (!file_exists($sharedStringsFile) || !file_exists($sheet1File)) {
    die("Excel files not found.");
}

$strings = [];
$xmlStrings = simplexml_load_file($sharedStringsFile);
foreach ($xmlStrings->si as $si) {
    $strings[] = (string)($si->t ?? $si->r->t ?? '');
}

$xmlSheet = simplexml_load_file($sheet1File);
$rows = $xmlSheet->sheetData->row;

$sources = [];
foreach ($rows as $index => $row) {
    if ($index == 0) continue;
    foreach ($row->c as $cell) {
        $r = (string)$cell['r'];
        $col = preg_replace('/[0-9]+/', '', $r);
        if ($col == 'A') {
            $type = (string)$cell['t'];
            $val = (string)$cell->v;
            $finalVal = ($type == 's') ? ($strings[(int)$val] ?? '') : $val;
            if ($finalVal !== '') $sources[] = $finalVal;
            break;
        }
    }
}

$uniqueSources = array_unique($sources);
sort($uniqueSources);
foreach ($uniqueSources as $s) {
    echo $s . PHP_EOL;
}
