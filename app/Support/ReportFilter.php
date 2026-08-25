<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class ReportFilter
{
    public static function presets(): array
    {
        return [
            'today' => translate('today'),
            'yesterday' => translate('yesterday'),
            'last_7_days' => translate('last_7_days'),
            'last_30_days' => translate('last_30_days'),
            'this_week' => translate('This_Week'),
            'last_week' => translate('Last_Week'),
            'this_month' => translate('This_Month'),
            'last_month' => translate('Last_Month'),
            'this_year' => translate('This_Year'),
            'last_year' => translate('Last_Year'),
            'last_15_days' => translate('Last_15_Days'),
            'last_6_month' => translate('Last_6_Month'),
            'all_time' => translate('All_Time'),
            'custom_date' => translate('Custom_Date'),
        ];
    }

    public static function allowed(): array
    {
        return array_merge(array_keys(self::presets()), [
            'this_year_1st_quarter', 'this_year_2nd_quarter', 'this_year_3rd_quarter', 'this_year_4th_quarter',
        ]);
    }

    public static function bounds(Request $request, string $key = 'date_range'): array
    {
        $range = (string) $request->input($key, 'all_time');
        $now = Carbon::now();

        if ($range === 'custom_date' && $request->filled('from') && $request->filled('to')) {
            return [Carbon::parse($request->input('from'))->startOfDay(), Carbon::parse($request->input('to'))->endOfDay()];
        }

        return match ($range) {
            'today' => [$now->copy()->startOfDay(), $now->copy()->endOfDay()],
            'yesterday' => [$now->copy()->subDay()->startOfDay(), $now->copy()->subDay()->endOfDay()],
            'last_7_days' => [$now->copy()->subDays(6)->startOfDay(), $now->copy()->endOfDay()],
            'last_30_days' => [$now->copy()->subDays(29)->startOfDay(), $now->copy()->endOfDay()],
            'this_week' => [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()],
            'last_week' => [$now->copy()->subWeek()->startOfWeek(), $now->copy()->subWeek()->endOfWeek()],
            'this_month' => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()],
            'last_month' => [$now->copy()->subMonth()->startOfMonth(), $now->copy()->subMonth()->endOfMonth()],
            'last_15_days' => [$now->copy()->subDays(15)->startOfDay(), $now->copy()->endOfDay()],
            'this_year' => [$now->copy()->startOfYear(), $now->copy()->endOfYear()],
            'last_year' => [$now->copy()->subYear()->startOfYear(), $now->copy()->subYear()->endOfYear()],
            'last_6_month' => [$now->copy()->subMonths(6)->startOfDay(), $now->copy()->endOfDay()],
            'this_year_1st_quarter' => [$now->copy()->month(1)->startOfQuarter(), $now->copy()->month(1)->endOfQuarter()],
            'this_year_2nd_quarter' => [$now->copy()->month(4)->startOfQuarter(), $now->copy()->month(4)->endOfQuarter()],
            'this_year_3rd_quarter' => [$now->copy()->month(7)->startOfQuarter(), $now->copy()->month(7)->endOfQuarter()],
            'this_year_4th_quarter' => [$now->copy()->month(10)->startOfQuarter(), $now->copy()->month(10)->endOfQuarter()],
            default => [null, null],
        };
    }

    public static function apply(Builder $query, Request $request, string $column = 'created_at', string $key = 'date_range'): Builder
    {
        [$from, $to] = self::bounds($request, $key);
        if ($from && $to) {
            $query->whereBetween($column, [$from, $to]);
        }

        return $query;
    }

    public static function granularity(Request $request, string $key = 'date_range'): string
    {
        $forced = $request->input('granularity');
        if (in_array($forced, ['day', 'week', 'month', 'year'], true)) {
            return $forced;
        }

        [$from, $to] = self::bounds($request, $key);
        if (!$from || !$to) {
            return 'month';
        }

        $days = $from->diffInDays($to);
        if ($days <= 1) {
            return 'day';
        }
        if ($days <= 45) {
            return 'day';
        }
        if ($days <= 180) {
            return 'week';
        }
        if ($days <= 800) {
            return 'month';
        }

        return 'year';
    }

    public static function groupExpression(string $granularity, string $column = 'created_at'): string
    {
        return match ($granularity) {
            'year' => "DATE_FORMAT($column, '%Y')",
            'month' => "DATE_FORMAT($column, '%Y-%m')",
            'week' => "DATE_FORMAT($column, '%x-W%v')",
            default => "DATE($column)",
        };
    }
}
