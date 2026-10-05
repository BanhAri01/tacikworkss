<?php

namespace App\Actions\Reports;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/** Sums a column per calendar month for the last N months (zero-filled), for bar charts. */
class MonthlyTotals
{
    /**
     * @return list<array{label: string, value: int}>
     */
    public function handle(Builder $query, string $sumColumn, string $dateColumn, int $months = 12): array
    {
        $start = now()->startOfMonth()->subMonths($months - 1);
        $table = $query->getModel()->getTable();
        $date = str_contains($dateColumn, '.') ? $dateColumn : "{$table}.{$dateColumn}";

        $bucket = DB::connection($query->getModel()->getConnectionName())->getDriverName() === 'sqlite'
            ? "strftime('%Y-%m', {$date})"
            : "DATE_FORMAT({$date}, '%Y-%m')";

        $totals = (clone $query)
            ->where($date, '>=', $start)
            ->selectRaw("{$bucket} as ym, SUM({$sumColumn}) as total")
            ->groupBy('ym')
            ->pluck('total', 'ym');

        $points = [];
        for ($i = 0; $i < $months; $i++) {
            $month = Carbon::parse($start)->addMonths($i);
            $points[] = ['label' => $month->format('M y'), 'value' => (int) ($totals[$month->format('Y-m')] ?? 0)];
        }

        return $points;
    }
}
