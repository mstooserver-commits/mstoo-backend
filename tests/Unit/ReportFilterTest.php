<?php

namespace Tests\Unit;

use App\Support\ReportFilter;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Tests\TestCase;

class ReportFilterTest extends TestCase
{
    public function test_today_and_yesterday_bounds()
    {
        Carbon::setTestNow(Carbon::parse('2026-08-25 15:00:00'));

        [$from, $to] = ReportFilter::bounds(Request::create('/', 'GET', ['date_range' => 'today']));
        $this->assertSame('2026-08-25 00:00:00', $from->toDateTimeString());
        $this->assertSame('2026-08-25 23:59:59', $to->toDateTimeString());

        [$from, $to] = ReportFilter::bounds(Request::create('/', 'GET', ['date_range' => 'yesterday']));
        $this->assertSame('2026-08-24 00:00:00', $from->toDateTimeString());
        $this->assertSame('2026-08-24 23:59:59', $to->toDateTimeString());

        Carbon::setTestNow();
    }

    public function test_custom_date_and_last_seven_days()
    {
        Carbon::setTestNow(Carbon::parse('2026-08-25 12:00:00'));

        [$from, $to] = ReportFilter::bounds(Request::create('/', 'GET', [
            'date_range' => 'custom_date',
            'from' => '2026-08-01',
            'to' => '2026-08-10',
        ]));
        $this->assertSame('2026-08-01 00:00:00', $from->toDateTimeString());
        $this->assertSame('2026-08-10 23:59:59', $to->toDateTimeString());

        [$from, $to] = ReportFilter::bounds(Request::create('/', 'GET', ['date_range' => 'last_7_days']));
        $this->assertSame('2026-08-19 00:00:00', $from->toDateTimeString());
        $this->assertSame('2026-08-25 23:59:59', $to->toDateTimeString());

        Carbon::setTestNow();
    }

    public function test_granularity_follows_range_or_explicit_value()
    {
        $request = Request::create('/', 'GET', ['date_range' => 'today']);
        $this->assertSame('day', ReportFilter::granularity($request));

        $request = Request::create('/', 'GET', ['date_range' => 'this_year', 'granularity' => 'month']);
        $this->assertSame('month', ReportFilter::granularity($request));

        $this->assertStringContainsString('%Y-%m', ReportFilter::groupExpression('month'));
    }
}
