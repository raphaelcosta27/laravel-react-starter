<?php

namespace Tests\Unit;

use App\Support\Tables\TableExporter;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;
use ZipArchive;

class TableExporterTest extends TestCase
{
    /** @param array<int, array<int, string>> $rows */
    private function csv(array $rows, array $headers = ['Nome']): string
    {
        $handle = fopen('php://memory', 'w+');

        (new TableExporter)->writeCsv($handle, $headers, $rows);

        rewind($handle);

        return stream_get_contents($handle);
    }

    /** @param array<int, array<int, string>> $rows */
    private function xlsxSheet(array $rows): string
    {
        $path = tempnam(sys_get_temp_dir(), 'xlsx');

        (new TableExporter)->writeXlsx($path, ['Nome'], $rows);

        $zip = new ZipArchive;
        $zip->open($path);
        $sheet = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();
        unlink($path);

        return $sheet;
    }

    public function test_csv_starts_with_bom_to_keep_accents_in_spreadsheets(): void
    {
        $this->assertStringStartsWith("\xEF\xBB\xBF", $this->csv([['João']]));
        $this->assertStringContainsString('João', $this->csv([['João']]));
    }

    public function test_csv_uses_the_configured_delimiter(): void
    {
        config(['template.admin.csv_delimiter' => '|']);

        $this->assertStringContainsString('Nome|E-mail', $this->csv([], ['Nome', 'E-mail']));
    }

    public function test_csv_default_delimiter_is_a_semicolon(): void
    {
        $this->assertStringContainsString('Nome;E-mail', $this->csv([], ['Nome', 'E-mail']));
    }

    /** @return array<string, array{0: string}> */
    public static function formulaPrefixes(): array
    {
        return [
            'igual' => ['=1+1'],
            'mais' => ['+1+1'],
            'menos' => ['-1+1'],
            'arroba' => ['@SOMA(1)'],
            'tabulação' => ["\tvalor"],
            'retorno' => ["\rvalor"],
        ];
    }

    #[DataProvider('formulaPrefixes')]
    public function test_csv_neutralizes_formulas(string $value): void
    {
        $handle = fopen('php://memory', 'w+');
        (new TableExporter)->writeCsv($handle, ['Nome'], [[$value]]);
        rewind($handle);

        fread($handle, 3);
        fgetcsv($handle, null, ';', '"', '');

        $this->assertSame("'".$value, fgetcsv($handle, null, ';', '"', '')[0]);
    }

    public function test_csv_keeps_common_values(): void
    {
        $this->assertStringContainsString('Ana Lima', $this->csv([['Ana Lima']]));
        $this->assertStringNotContainsString("'Ana", $this->csv([['Ana Lima']]));
    }

    public function test_xlsx_has_zero_formula_cells(): void
    {
        $sheet = $this->xlsxSheet([['=1+1'], ['=SOMA(A1:A2)']]);

        $this->assertStringNotContainsString('<f>', $sheet);
        $this->assertStringContainsString('=1+1', $sheet);
    }

    public function test_xlsx_keeps_accents(): void
    {
        $this->assertStringContainsString('João', $this->xlsxSheet([['João']]));
    }
}
