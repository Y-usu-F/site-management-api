<?php

namespace App\Services\Common;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ExcelExportService
{
    /**
     * @param list<string> $headers
     * @param list<array<string, mixed>> $rows
     */
    public function buildXlsxBinary(array $headers, array $rows): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $column = 1;
        foreach ($headers as $header) {
            $cell = Coordinate::stringFromColumnIndex($column) . '1';
            $sheet->setCellValue($cell, $header);
            $column++;
        }

        $rowIndex = 2;
        foreach ($rows as $row) {
            $column = 1;
            foreach ($headers as $header) {
                $value = $row[$header] ?? null;
                $cell = Coordinate::stringFromColumnIndex($column) . (string) $rowIndex;
                $sheet->setCellValue($cell, $value);
                $column++;
            }
            $rowIndex++;
        }

        $writer = new Xlsx($spreadsheet);
        ob_start();
        $writer->save('php://output');
        $binary = (string) ob_get_clean();
        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);

        return $binary;
    }
}
