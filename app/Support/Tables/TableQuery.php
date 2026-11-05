<?php

namespace App\Support\Tables;

use App\Models\User;
use Closure;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Aplica os parâmetros da listagem. Colunas vêm somente da lista permitida da
 * definição, e valores entram sempre como parâmetros preparados.
 */
class TableQuery
{
    private const PER_PAGE = [10, 25, 50, 100];

    private const DEFAULT_PER_PAGE = 25;

    private const LIKE_ESCAPE = '!';

    /** @param array<string, mixed> $params */
    public function __construct(private TableDefinition $definition, private array $params) {}

    public function tab(): ?string
    {
        $tabs = $this->visibleTabs();
        $requested = $this->params['tab'] ?? null;

        return is_string($requested) && isset($tabs[$requested]) ? $requested : array_key_first($tabs);
    }

    public function builder(): Builder
    {
        $query = $this->filtered();
        $tab = $this->tab();

        if ($tab !== null) {
            ($this->visibleTabs()[$tab]['apply'])($query);
        }

        return $this->applySort($query);
    }

    public function paginate(): LengthAwarePaginator
    {
        $perPage = $this->perPage();
        $page = max(1, (int) ($this->params['page'] ?? 1));
        $rows = $this->builder()->paginate($perPage, ['*'], 'page', $page);

        return $page > $rows->lastPage()
            ? $this->builder()->paginate($perPage, ['*'], 'page', $rows->lastPage())
            : $rows;
    }

    /** @return array<int, array{key: string, label: string, count: int}> */
    public function tabs(): array
    {
        $tabs = [];

        foreach ($this->visibleTabs() as $key => $tab) {
            $query = $this->filtered();
            ($tab['apply'])($query);

            $tabs[] = ['key' => $key, 'label' => $tab['label'], 'count' => $query->toBase()->getCountForPagination()];
        }

        return $tabs;
    }

    /** @return array<string, array<string, float|int|null>> */
    public function summaries(): array
    {
        $summaries = [];

        foreach ($this->definition->summaries() as $column => $functions) {
            $wrapped = DB::query()->fromSub($this->builder()->reorder(), 'filtered');

            foreach ($functions as $function) {
                // O MySQL devolve agregações como texto.
                $value = $wrapped->clone()->{$function}($column);

                $summaries[$column][$function] = is_numeric($value) ? $value + 0 : $value;
            }
        }

        return $summaries;
    }

    /** @return array<string, mixed> */
    public function response(User $actor): array
    {
        $rows = $this->paginate();

        return [
            'rows' => [
                'data' => collect($rows->items())->map(fn (Model $row) => [
                    ...$this->definition->transform($row),
                    'can' => $this->definition->rowAbilities($actor, $row),
                ])->all(),
                'meta' => [
                    'currentPage' => $rows->currentPage(),
                    'lastPage' => $rows->lastPage(),
                    'perPage' => $rows->perPage(),
                    'total' => $rows->total(),
                ],
            ],
            'query' => [
                'sort' => $this->sort(),
                'search' => $this->search(),
                'filter' => $this->filters(),
                'tab' => $this->tab(),
                'per_page' => $this->perPage(),
            ],
            'tabs' => $this->tabs(),
            'summaries' => $this->summaries(),
        ];
    }

    /**
     * A mesma resposta, calculada só quando alguma propriedade for pedida.
     *
     * @return array<string, Closure>
     */
    public function lazyResponse(User $actor): array
    {
        $response = null;

        $resolve = function () use (&$response, $actor) {
            return $response ??= $this->response($actor);
        };

        return [
            'rows' => fn () => $resolve()['rows'],
            'query' => fn () => $resolve()['query'],
            'tabs' => fn () => $resolve()['tabs'],
            'summaries' => fn () => $resolve()['summaries'],
        ];
    }

    /** @return array<string, array<string, mixed>> */
    private function visibleTabs(): array
    {
        return array_filter($this->definition->tabs(), fn (array $tab) => $tab['visible'] ?? true);
    }

    private function perPage(): int
    {
        $perPage = (int) ($this->params['per_page'] ?? self::DEFAULT_PER_PAGE);

        return in_array($perPage, self::PER_PAGE, true) ? $perPage : self::DEFAULT_PER_PAGE;
    }

    private function search(): string
    {
        return is_string($this->params['search'] ?? null) ? trim($this->params['search']) : '';
    }

    private function sort(): string
    {
        $columns = $this->definition->columns();

        $valid = array_filter(
            explode(',', is_string($this->params['sort'] ?? null) ? $this->params['sort'] : ''),
            fn (string $column) => $columns[ltrim($column, '-')]['sortable'] ?? false,
        );

        return $valid === [] ? $this->definition->defaultSort() : implode(',', $valid);
    }

    /** @return array<string, mixed> */
    private function filters(): array
    {
        $filters = is_array($this->params['filter'] ?? null) ? $this->params['filter'] : [];

        return array_filter(
            array_intersect_key($filters, array_filter($this->definition->columns(), fn (array $column) => isset($column['filter']))),
            fn (mixed $value) => $value !== '' && $value !== null && $value !== [],
        );
    }

    /**
     * Consulta com busca e filtros, sem aba nem ordenação.
     */
    private function filtered(): Builder
    {
        $query = $this->definition->query();
        $columns = $this->definition->columns();

        $this->applySearch($query, $columns);

        foreach ($this->filters() as $column => $value) {
            $this->applyFilter($query, $column, $columns[$column], $value);
        }

        return $query;
    }

    /** @param array<string, array<string, mixed>> $columns */
    private function applySearch(Builder $query, array $columns): void
    {
        $term = $this->search();

        if ($term === '') {
            return;
        }

        $query->where(function (Builder $query) use ($columns, $term) {
            foreach (array_keys(array_filter($columns, fn (array $column) => $column['searchable'] ?? false)) as $column) {
                $this->whereLike($query, $column, '%'.$this->escapeLike($term).'%', boolean: 'or');
            }
        });
    }

    /** @param array<string, mixed> $config */
    private function applyFilter(Builder $query, string $column, array $config, mixed $value): void
    {
        if (isset($config['apply'])) {
            ($config['apply'])($query, $value);

            return;
        }

        $target = $config['expression'] ?? $query->qualifyColumn($column);

        match ($config['filter']) {
            'text' => $this->applyTextFilter($query, $column, $value),
            'number' => $this->applyRange($query, $target, $value, 'min', 'max'),
            'boolean' => $query->where($target, (bool) $value),
            'select' => is_scalar($value) ? $query->where($target, $value) : null,
            'multiselect' => $query->whereIn($target, (array) $value),
            'date' => $this->applyDateRange($query, $target, $value),
            'datetime' => $this->applyRange($query, $target, $value, 'from', 'to'),
        };
    }

    private function applyTextFilter(Builder $query, string $column, mixed $filter): void
    {
        if (! is_array($filter)) {
            return;
        }

        $operator = $filter['op'] ?? 'contains';
        $value = is_string($filter['value'] ?? null) ? $filter['value'] : '';
        $escaped = $this->escapeLike($value);
        $qualified = $query->qualifyColumn($column);

        match ($operator) {
            'contains' => $this->whereLike($query, $column, "%{$escaped}%"),
            'not_contains' => $this->whereLike($query, $column, "%{$escaped}%", not: true),
            'is' => $query->where($qualified, $value),
            'is_not' => $query->where($qualified, '!=', $value),
            'starts_with' => $this->whereLike($query, $column, "{$escaped}%"),
            'ends_with' => $this->whereLike($query, $column, "%{$escaped}"),
            'is_empty' => $query->where(fn (Builder $query) => $query->whereNull($qualified)->orWhere($qualified, '')),
            'is_not_empty' => $query->whereNotNull($qualified)->where($qualified, '!=', ''),
            default => null,
        };
    }

    private function applyRange(Builder $query, mixed $target, mixed $range, string $from, string $to): void
    {
        if (! is_array($range)) {
            return;
        }

        if (($range[$from] ?? '') !== '') {
            $query->where($target, '>=', $range[$from]);
        }

        if (($range[$to] ?? '') !== '') {
            $query->where($target, '<=', $range[$to]);
        }
    }

    private function applyDateRange(Builder $query, mixed $target, mixed $range): void
    {
        if (! is_array($range)) {
            return;
        }

        if (($range['from'] ?? '') !== '') {
            $query->whereDate($target, '>=', $range['from']);
        }

        if (($range['to'] ?? '') !== '') {
            $query->whereDate($target, '<=', $range['to']);
        }
    }

    private function applySort(Builder $query): Builder
    {
        $columns = $this->definition->columns();

        foreach (explode(',', $this->sort()) as $column) {
            $name = ltrim($column, '-');

            $query->orderBy($columns[$name]['sort'] ?? $name, str_starts_with($column, '-') ? 'desc' : 'asc');
        }

        return $query->orderBy($query->qualifyColumn($query->getModel()->getKeyName()));
    }

    private function whereLike(Builder $query, string $column, string $pattern, bool $not = false, string $boolean = 'and'): void
    {
        $wrapped = $query->getQuery()->getGrammar()->wrap($query->qualifyColumn($column));
        $operator = $not ? 'NOT LIKE' : 'LIKE';

        $query->whereRaw("{$wrapped} {$operator} ? ESCAPE '".self::LIKE_ESCAPE."'", [$pattern], $boolean);
    }

    private function escapeLike(string $value): string
    {
        return str_replace(
            [self::LIKE_ESCAPE, '%', '_'],
            [self::LIKE_ESCAPE.self::LIKE_ESCAPE, self::LIKE_ESCAPE.'%', self::LIKE_ESCAPE.'_'],
            $value,
        );
    }
}
