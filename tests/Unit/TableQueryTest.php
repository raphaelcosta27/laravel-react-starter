<?php

namespace Tests\Unit;

use App\Models\User;
use App\Support\Tables\TableDefinition;
use App\Support\Tables\TableQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class TableQueryTest extends TestCase
{
    use RefreshDatabase;

    private function definition(): TableDefinition
    {
        return new class extends TableDefinition
        {
            public function query(): Builder
            {
                return User::query();
            }

            public function columns(): array
            {
                return [
                    'name' => ['sortable' => true, 'searchable' => true, 'filter' => 'text'],
                    'email' => ['sortable' => true, 'searchable' => true, 'filter' => 'text'],
                    'id' => ['sortable' => true, 'filter' => 'number'],
                    'is_active' => ['filter' => 'boolean'],
                    'profile_id' => ['filter' => 'select'],
                    'remember_token' => ['filter' => 'multiselect'],
                    'email_verified_at' => ['filter' => 'date'],
                    'created_at' => ['sortable' => true, 'filter' => 'datetime'],
                ];
            }

            public function defaultSort(): string
            {
                return 'name';
            }

            public function tabs(): array
            {
                return [
                    'all' => ['label' => 'Todos', 'apply' => fn (Builder $query) => $query],
                    'active' => ['label' => 'Ativos', 'apply' => fn (Builder $query) => $query->where('is_active', true)],
                    'hidden' => ['label' => 'Oculta', 'visible' => false, 'apply' => fn (Builder $query) => $query],
                ];
            }

            public function summaries(): array
            {
                return ['id' => ['sum', 'count', 'avg', 'min', 'max']];
            }

            public function transform(Model $row): array
            {
                return ['id' => $row->id, 'name' => $row->name];
            }

            public function rowAbilities(User $actor, Model $row): array
            {
                return [];
            }
        };
    }

    private function table(array $params = []): TableQuery
    {
        return new TableQuery($this->definition(), $params);
    }

    /** @return array<int, string> */
    private function names(array $params = []): array
    {
        return $this->table($params)->paginate()->pluck('name')->all();
    }

    public function test_per_page_is_limited_to_the_allowed_values(): void
    {
        User::factory()->count(12)->create();

        $this->assertCount(10, $this->table(['per_page' => 10])->paginate());
        $this->assertSame(25, $this->table(['per_page' => 7])->paginate()->perPage());
        $this->assertSame(25, $this->table(['per_page' => 1000])->paginate()->perPage());
        $this->assertSame(25, $this->table()->paginate()->perPage());
    }

    public function test_multiple_sort_with_descending_prefix(): void
    {
        User::factory()->create(['name' => 'Bia', 'email' => 'a@example.com']);
        User::factory()->create(['name' => 'Ana', 'email' => 'b@example.com']);
        User::factory()->create(['name' => 'Ana', 'email' => 'c@example.com']);

        $rows = $this->table(['sort' => 'name,-email'])->paginate();

        $this->assertSame(['c@example.com', 'b@example.com', 'a@example.com'], $rows->pluck('email')->all());
    }

    public function test_column_outside_the_allowed_list_is_ignored(): void
    {
        User::factory()->create(['name' => 'Bia']);
        User::factory()->create(['name' => 'Ana']);

        $this->assertSame(['Ana', 'Bia'], $this->names(['sort' => 'password']));
        $this->assertSame(['Ana', 'Bia'], $this->names(['filter' => ['password' => 'x']]));
    }

    public function test_general_search_uses_searchable_columns(): void
    {
        User::factory()->create(['name' => 'Ana Lima', 'email' => 'a@example.com']);
        User::factory()->create(['name' => 'Bia', 'email' => 'lima@example.com']);
        User::factory()->create(['name' => 'Caio', 'email' => 'c@example.com']);

        $this->assertSame(['Ana Lima', 'Bia'], $this->names(['search' => 'lima']));
    }

    public function test_search_treats_wildcards_as_text(): void
    {
        User::factory()->create(['name' => '100% certo']);
        User::factory()->create(['name' => 'Outro']);

        $this->assertSame(['100% certo'], $this->names(['search' => '%']));
    }

    /** @return array<string, array{0: array<string, string>, 1: array<int, string>}> */
    public static function textOperators(): array
    {
        return [
            'contains' => [['op' => 'contains', 'value' => 'an'], ['Ana', 'Mariana']],
            'not_contains' => [['op' => 'not_contains', 'value' => 'an'], ['', 'Bia']],
            'is' => [['op' => 'is', 'value' => 'Ana'], ['Ana']],
            'is_not' => [['op' => 'is_not', 'value' => 'Ana'], ['', 'Bia', 'Mariana']],
            'starts_with' => [['op' => 'starts_with', 'value' => 'Ma'], ['Mariana']],
            'ends_with' => [['op' => 'ends_with', 'value' => 'ia'], ['Bia']],
            'is_empty' => [['op' => 'is_empty'], ['']],
            'is_not_empty' => [['op' => 'is_not_empty'], ['Ana', 'Bia', 'Mariana']],
        ];
    }

    #[DataProvider('textOperators')]
    public function test_text_filter(array $filter, array $expected): void
    {
        foreach (['Ana', 'Bia', 'Mariana', ''] as $name) {
            User::factory()->create(['name' => $name]);
        }

        $this->assertSame($expected, $this->names(['filter' => ['name' => $filter]]));
    }

    public function test_number_filter_uses_a_range(): void
    {
        $users = User::factory()->count(3)->create();

        $rows = $this->table(['filter' => ['id' => ['min' => $users[1]->id, 'max' => $users[1]->id]]])->paginate();

        $this->assertSame([$users[1]->id], $rows->pluck('id')->all());
    }

    public function test_boolean_filter(): void
    {
        User::factory()->create(['name' => 'Ana']);
        User::factory()->inactive()->create(['name' => 'Bia']);

        $this->assertSame(['Ana'], $this->names(['filter' => ['is_active' => '1']]));
        $this->assertSame(['Bia'], $this->names(['filter' => ['is_active' => '0']]));
        $this->assertSame(['Ana', 'Bia'], $this->names(['filter' => ['is_active' => '']]));
    }

    public function test_multiselect_filter(): void
    {
        User::factory()->create(['name' => 'Ana', 'remember_token' => 'a']);
        User::factory()->create(['name' => 'Bia', 'remember_token' => 'b']);
        User::factory()->create(['name' => 'Caio', 'remember_token' => 'c']);

        $this->assertSame(['Ana', 'Bia'], $this->names(['filter' => ['remember_token' => ['a', 'b']]]));
    }

    public function test_date_and_datetime_filters_use_a_range(): void
    {
        User::factory()->create(['name' => 'Ana', 'created_at' => '2026-01-10 08:00:00', 'email_verified_at' => '2026-01-10 08:00:00']);
        User::factory()->create(['name' => 'Bia', 'created_at' => '2026-03-10 08:00:00', 'email_verified_at' => '2026-03-10 23:00:00']);

        $this->assertSame(['Bia'], $this->names(['filter' => ['created_at' => ['from' => '2026-02-01 00:00:00']]]));
        $this->assertSame(['Ana'], $this->names(['filter' => ['created_at' => ['to' => '2026-02-01 00:00:00']]]));
        $this->assertSame(['Bia'], $this->names(['filter' => ['email_verified_at' => ['from' => '2026-03-10', 'to' => '2026-03-10']]]));
    }

    public function test_filter_values_are_prepared_parameters(): void
    {
        User::factory()->create(['name' => 'Ana']);

        $this->assertSame([], $this->names(['filter' => ['name' => ['op' => 'is', 'value' => "' OR 1=1 --"]]]));
    }

    public function test_page_above_the_limit_becomes_the_last_page(): void
    {
        User::factory()->count(12)->create();

        $rows = $this->table(['per_page' => 10, 'page' => 99])->paginate();

        $this->assertSame(2, $rows->currentPage());
        $this->assertCount(2, $rows);
    }

    public function test_tab_applies_its_rule_and_unknown_tab_uses_the_default(): void
    {
        User::factory()->create(['name' => 'Ana']);
        User::factory()->inactive()->create(['name' => 'Bia']);

        $this->assertSame(['Ana'], $this->names(['tab' => 'active']));
        $this->assertSame(['Ana', 'Bia'], $this->names(['tab' => 'nao_existe']));
        $this->assertSame('all', $this->table(['tab' => 'hidden'])->tab());
    }

    public function test_tabs_bring_the_count_of_the_current_filter_and_skip_hidden_ones(): void
    {
        User::factory()->create(['name' => 'Ana', 'email' => 'a@example.com']);
        User::factory()->inactive()->create(['name' => 'Ana Bia', 'email' => 'b@example.com']);
        User::factory()->create(['name' => 'Caio', 'email' => 'c@example.com']);

        $this->assertSame([
            ['key' => 'all', 'label' => 'Todos', 'count' => 2],
            ['key' => 'active', 'label' => 'Ativos', 'count' => 1],
        ], $this->table(['search' => 'ana'])->tabs());
    }

    public function test_summaries_use_every_record_of_the_filter(): void
    {
        $users = User::factory()->count(12)->create();

        $summary = $this->table(['per_page' => 10])->summaries()['id'];

        $this->assertEquals($users->sum('id'), $summary['sum']);
        $this->assertEquals(12, $summary['count']);
        $this->assertEquals($users->min('id'), $summary['min']);
        $this->assertEquals($users->max('id'), $summary['max']);
        $this->assertEquals($users->avg('id'), $summary['avg']);
    }

    public function test_response_follows_the_contract(): void
    {
        $actor = User::factory()->create(['name' => 'Ana']);

        $response = $this->table(['per_page' => 10, 'search' => 'ana', 'sort' => '-name'])->response($actor);

        $this->assertSame(['currentPage' => 1, 'lastPage' => 1, 'perPage' => 10, 'total' => 1], $response['rows']['meta']);
        $this->assertSame('Ana', $response['rows']['data'][0]['name']);
        $this->assertSame([], $response['rows']['data'][0]['can']);
        $this->assertSame([
            'sort' => '-name', 'search' => 'ana', 'filter' => [], 'tab' => 'all', 'per_page' => 10,
        ], $response['query']);
    }
}
