<?php

namespace App\Support\Tables;

use App\Models\User;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Descreve uma listagem. Somente o que está declarado aqui chega à consulta.
 */
abstract class TableDefinition
{
    /**
     * Quem consulta a listagem, usado para decidir as abas visíveis.
     */
    public function __construct(protected ?User $actor = null) {}

    abstract public function query(): Builder;

    /**
     * Colunas permitidas. Cada uma aceita:
     *
     * - sortable: permite ordenar;
     * - searchable: entra na busca geral;
     * - filter: text, number, boolean, select, multiselect, date ou datetime;
     * - sort: expressão de ordenação, quando não for a própria coluna;
     * - expression: subconsulta usada no filtro, para colunas calculadas;
     * - apply: função própria do filtro, que recebe a consulta e o valor;
     * - label: título na exportação; sem ele, a coluna não é exportada.
     *
     * @return array<string, array<string, mixed>>
     */
    abstract public function columns(): array;

    abstract public function defaultSort(): string;

    /**
     * Dados de um registro enviados à tela.
     *
     * @return array<string, mixed>
     */
    abstract public function transform(Model $row): array;

    /**
     * Ações disponíveis no registro: autorização E disponibilidade.
     *
     * @return array<string, bool>
     */
    abstract public function rowAbilities(User $actor, Model $row): array;

    /**
     * Abas da listagem, com label, apply e, opcionalmente, visible.
     *
     * @return array<string, array<string, mixed>>
     */
    public function tabs(): array
    {
        return [];
    }

    /**
     * Totais calculados sobre todo o filtro, como ['coluna' => ['sum', 'count']].
     *
     * @return array<string, array<int, string>>
     */
    public function summaries(): array
    {
        return [];
    }

    /**
     * Títulos das colunas que podem ser exportadas.
     *
     * @return array<string, string>
     */
    public function exportColumns(): array
    {
        return array_map(
            fn (array $column) => $column['label'],
            array_filter($this->columns(), fn (array $column) => isset($column['label'])),
        );
    }

    /**
     * Texto exportado de uma coluna do registro.
     */
    public function exportValue(Model $row, string $column): string
    {
        $value = $this->transform($row)[$column] ?? '';

        return match (true) {
            is_bool($value) => $value ? __('Yes') : __('No'),
            $value instanceof DateTimeInterface => $value->format('d/m/Y H:i'),
            default => (string) $value,
        };
    }
}
