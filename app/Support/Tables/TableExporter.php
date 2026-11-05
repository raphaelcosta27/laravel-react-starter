<?php

namespace App\Support\Tables;

use OpenSpout\Common\Entity\Cell\StringCell;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Toda célula sai como texto. Um valor digitado por terceiros, como `=1+1`,
 * nunca vira fórmula ao abrir o arquivo em uma planilha.
 */
class TableExporter
{
    private const FORMULA_PREFIXES = ['=', '+', '-', '@', "\t", "\r"];

    /**
     * @param  array<int, string>  $headers
     * @param  iterable<array<int, string>>  $rows
     */
    public function csv(string $filename, array $headers, iterable $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $rows) {
            $this->writeCsv(fopen('php://output', 'w'), $headers, $rows);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * @param  array<int, string>  $headers
     * @param  iterable<array<int, string>>  $rows
     */
    public function xlsx(string $filename, array $headers, iterable $rows): BinaryFileResponse
    {
        $path = tempnam(sys_get_temp_dir(), 'export');

        $this->writeXlsx($path, $headers, $rows);

        return response()->download($path, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend();
    }

    /**
     * @param  resource  $handle
     * @param  array<int, string>  $headers
     * @param  iterable<array<int, string>>  $rows
     */
    public function writeCsv($handle, array $headers, iterable $rows): void
    {
        $delimiter = config('template.admin.csv_delimiter');

        fwrite($handle, "\xEF\xBB\xBF");
        fputcsv($handle, $headers, $delimiter, '"', '');

        foreach ($rows as $row) {
            fputcsv($handle, array_map($this->neutralize(...), $row), $delimiter, '"', '');
        }
    }

    /**
     * @param  array<int, string>  $headers
     * @param  iterable<array<int, string>>  $rows
     */
    public function writeXlsx(string $path, array $headers, iterable $rows): void
    {
        $writer = new Writer;
        $writer->openToFile($path);
        $writer->addRow($this->textRow($headers));

        foreach ($rows as $row) {
            $writer->addRow($this->textRow($row));
        }

        $writer->close();
    }

    /** @param array<int, string> $values */
    private function textRow(array $values): Row
    {
        return new Row(array_map(fn (string $value) => new StringCell($value), $values));
    }

    private function neutralize(string $value): string
    {
        return in_array(substr($value, 0, 1), self::FORMULA_PREFIXES, true) ? "'".$value : $value;
    }
}
