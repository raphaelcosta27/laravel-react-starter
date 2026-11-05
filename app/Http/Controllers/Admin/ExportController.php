<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\Tables\Definitions\PermissionsTable;
use App\Support\Tables\Definitions\ProfilesTable;
use App\Support\Tables\Definitions\UsersTable;
use App\Support\Tables\TableDefinition;
use App\Support\Tables\TableExporter;
use App\Support\Tables\TableQuery;
use Generator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ExportController extends Controller
{
    public function __construct(private TableExporter $exporter) {}

    public function profiles(Request $request): Response
    {
        return $this->export($request, new ProfilesTable($request->user()), 'perfis');
    }

    public function permissions(Request $request): Response
    {
        return $this->export($request, new PermissionsTable($request->user()), 'permissoes');
    }

    public function users(Request $request): Response
    {
        return $this->export($request, new UsersTable($request->user()), 'usuarios');
    }

    /**
     * Usa a mesma consulta da listagem: abas restritas continuam restritas.
     */
    private function export(Request $request, TableDefinition $definition, string $name): Response
    {
        $query = (new TableQuery($definition, $request->query()))->builder();
        $ids = array_filter((array) $request->query('ids', []), 'is_numeric');

        if ($ids !== []) {
            $query->whereKey($ids);
        }

        if (! $query->clone()->exists()) {
            return back()->with('flash', ['type' => 'warning', 'message' => __('There are no records to export.')]);
        }

        $columns = $this->columns($request, $definition);
        $headers = array_values(array_map(fn (string $column) => $definition->exportColumns()[$column], $columns));
        $rows = $this->rows($query, $definition, $columns);
        $filename = $name.'-'.now()->format('Y-m-d-His');

        return $request->query('format') === 'xlsx'
            ? $this->exporter->xlsx("{$filename}.xlsx", $headers, $rows)
            : $this->exporter->csv("{$filename}.csv", $headers, $rows);
    }

    /**
     * Colunas pedidas, na ordem da tela, somente entre as permitidas.
     *
     * @return array<int, string>
     */
    private function columns(Request $request, TableDefinition $definition): array
    {
        $allowed = array_keys($definition->exportColumns());
        $requested = array_values(array_intersect((array) $request->query('columns', []), $allowed));

        return $requested === [] ? $allowed : $requested;
    }

    /**
     * @param  array<int, string>  $columns
     * @return Generator<array<int, string>>
     */
    private function rows(Builder $query, TableDefinition $definition, array $columns): Generator
    {
        foreach ($query->lazy(500) as $row) {
            yield array_map(fn (string $column) => $definition->exportValue($row, $column), $columns);
        }
    }
}
