<?php

namespace App\Services\Common;

use App\Exceptions\ValidationApiException;
use CodeIgniter\HTTP\Files\UploadedFile;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ExcelImportService
{
    public function assertXlsxFile(?UploadedFile $file): UploadedFile
    {
        if (! $file instanceof UploadedFile || ! $file->isValid()) {
            throw new ValidationApiException('Gecerli bir Excel dosyasi yuklenmeli', ['file' => 'Gecerli bir dosya gerekli.']);
        }

        $extension = strtolower((string) $file->getExtension());
        if ($extension !== 'xlsx') {
            throw new ValidationApiException('Sadece .xlsx dosyasi kabul edilir', ['file' => 'Dosya uzantisi .xlsx olmali.']);
        }

        return $file;
    }

    /**
     * @return array{headers:list<string>,rows:list<array<string,string>>,header_index:array<string,string>}
     */
    public function parseFirstSheet(UploadedFile $file): array
    {
        $spreadsheet = IOFactory::load($file->getTempName());
        $sheet = $spreadsheet->getSheet(0);
        $raw = $sheet->toArray(null, true, true, true);
        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);

        if ($raw === []) {
            throw new ValidationApiException('Excel bos', ['file' => 'Excel dosyasi bos olamaz.']);
        }

        $headerRow = array_shift($raw);
        if (! is_array($headerRow)) {
            throw new ValidationApiException('Header satiri bulunamadi', ['file' => 'Ilk satir header olmali.']);
        }

        $headers = [];
        $headerIndex = [];
        foreach ($headerRow as $column => $header) {
            $normalized = strtolower(trim((string) $header));
            if ($normalized === '') {
                continue;
            }
            $headers[] = $normalized;
            $headerIndex[$normalized] = (string) $column;
        }

        if ($headers === []) {
            throw new ValidationApiException('Header satiri bos olamaz', ['file' => 'Ilk satirda kolon adlari olmali.']);
        }

        $rows = [];
        foreach ($raw as $excelRow) {
            if (! is_array($excelRow)) {
                continue;
            }

            $isEmpty = true;
            $mapped = [];
            foreach ($headerIndex as $header => $column) {
                $value = trim((string) ($excelRow[$column] ?? ''));
                if ($value !== '') {
                    $isEmpty = false;
                }
                $mapped[$header] = $value;
            }

            if (! $isEmpty) {
                $rows[] = $mapped;
            }
        }

        return [
            'headers' => $headers,
            'rows' => $rows,
            'header_index' => $headerIndex,
        ];
    }

    /**
     * @param list<string> $required
     * @param list<string> $headers
     */
    public function assertRequiredHeaders(array $required, array $headers): void
    {
        $missing = [];
        foreach ($required as $column) {
            if (! in_array(strtolower($column), $headers, true)) {
                $missing[] = $column;
            }
        }

        if ($missing !== []) {
            throw new ValidationApiException(
                'Excel header kolonlari eksik',
                ['headers' => 'Eksik kolonlar: ' . implode(', ', $missing)]
            );
        }
    }
}
