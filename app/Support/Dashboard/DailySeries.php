<?php

namespace App\Support\Dashboard;

use Illuminate\Support\Collection;

/**
 * Dias de um gráfico, do mais antigo a hoje. A consulta só traz os dias com
 * movimento: os demais entram com zero.
 */
class DailySeries
{
    public static function start(int $days): mixed
    {
        return now()->subDays($days - 1)->startOfDay();
    }

    /**
     * @param  Collection<string, int|string>  $counts  total por dia, no formato Y-m-d
     * @return array{days: int, series: array<int, array{date: string, value: int}>, total: int}
     */
    public static function fill(int $days, Collection $counts): array
    {
        $series = collect(range($days - 1, 0))->map(function (int $ago) use ($counts) {
            $date = now()->subDays($ago)->toDateString();

            return ['date' => $date, 'value' => (int) ($counts[$date] ?? 0)];
        });

        return ['days' => $days, 'series' => $series->all(), 'total' => $series->sum('value')];
    }
}
