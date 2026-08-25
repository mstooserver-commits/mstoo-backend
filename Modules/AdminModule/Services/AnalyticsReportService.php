<?php

namespace Modules\AdminModule\Services;

use App\Support\ReportFilter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Modules\BookingModule\Entities\Booking;
use Modules\BookingModule\Entities\BookingDetailsAmount;
use Modules\CategoryManagement\Entities\Category;
use Modules\CustomerModule\Entities\SearchedData;
use Modules\ProviderManagement\Entities\Provider;
use Modules\ServiceManagement\Entities\RecentSearch;
use Modules\ServiceManagement\Entities\Service;
use Modules\TransactionModule\Entities\Transaction;
use Modules\UserManagement\Entities\User;
use Modules\ZoneManagement\Entities\Zone;

class AnalyticsReportService
{
    public const COMMISSION_TYPES = [
        'received_commission', 'receivable_commission', 'paid_commission', 'payable_commission',
    ];

    public const REFUND_TYPES = ['wallet_refund'];

    public const PROVIDER_EARNING_TYPES = ['received_amount', 'withdrawable_amount', 'receivable_amount'];

    public function cacheKey(string $name, Request $request): string
    {
        return 'mstoo_report_' . $name . '_' . md5(json_encode($request->except(['_token', 'page'])));
    }

    public function transactionQuery(Request $request)
    {
        $query = Transaction::query()->with([
            'booking:id,readable_id,customer_id,provider_id,payment_method,is_paid,total_booking_amount,total_tax_amount,total_discount_amount,total_coupon_discount_amount,booking_status',
            'booking.details_amounts',
            'from_user:id,first_name,last_name,email,phone,user_type',
            'to_user:id,first_name,last_name,email,phone,user_type',
            'from_user.provider:id,user_id,company_name',
            'to_user.provider:id,user_id,company_name',
        ]);

        ReportFilter::apply($query, $request);

        if ($request->filled('search')) {
            $term = '%' . str_replace(['%', '_'], ['\%', '\_'], $request->input('search')) . '%';
            $query->where(function ($inner) use ($term) {
                $inner->where('id', 'like', $term)
                    ->orWhere('ref_trx_id', 'like', $term)
                    ->orWhere('booking_id', 'like', $term)
                    ->orWhere('trx_type', 'like', $term)
                    ->orWhere('reference_note', 'like', $term);
            });
        }

        if ($request->filled('transaction_id')) {
            $query->where('id', $request->input('transaction_id'));
        }
        if ($request->filled('booking_id')) {
            $id = $request->input('booking_id');
            $query->where(function ($inner) use ($id) {
                $inner->where('booking_id', $id)
                    ->orWhereHas('booking', fn ($booking) => $booking->where('readable_id', $id)->orWhere('id', $id));
            });
        }
        if ($request->filled('customer_id')) {
            $customerId = $request->input('customer_id');
            $query->where(function ($inner) use ($customerId) {
                $inner->where('from_user_id', $customerId)
                    ->orWhere('to_user_id', $customerId)
                    ->orWhereHas('booking', fn ($booking) => $booking->where('customer_id', $customerId));
            });
        }
        if ($request->filled('provider_id')) {
            $providerId = $request->input('provider_id');
            $query->where(function ($inner) use ($providerId) {
                $inner->whereHas('to_user.provider', fn ($provider) => $provider->where('id', $providerId))
                    ->orWhereHas('from_user.provider', fn ($provider) => $provider->where('id', $providerId))
                    ->orWhereHas('booking', fn ($booking) => $booking->where('provider_id', $providerId));
            });
        }
        if ($request->filled('trx_type') && $request->input('trx_type') !== 'all') {
            $type = $request->input('trx_type');
            if ($type === 'debit') {
                $query->where('debit', '!=', 0);
            } elseif ($type === 'credit') {
                $query->where('credit', '!=', 0);
            } else {
                $query->where('trx_type', $type);
            }
        }
        if ($request->filled('payment_method') && $request->input('payment_method') !== 'all') {
            $method = $request->input('payment_method');
            $query->whereHas('booking', fn ($booking) => $booking->where('payment_method', $method));
        }
        if ($request->filled('status') && $request->input('status') !== 'all') {
            $status = $request->input('status');
            $query->whereHas('booking', fn ($booking) => $booking->where('booking_status', $status));
        }
        if ($request->filled('min_amount')) {
            $min = (float) $request->input('min_amount');
            $query->whereRaw('(debit + credit) >= ?', [$min]);
        }
        if ($request->filled('max_amount')) {
            $max = (float) $request->input('max_amount');
            $query->whereRaw('(debit + credit) <= ?', [$max]);
        }

        return $query->latest();
    }

    public function transactionSummary(Request $request): array
    {
        return Cache::remember($this->cacheKey('trx_summary', $request), 60, function () use ($request) {
            $base = $this->withoutOrder($this->transactionQuery($request))->getQuery();
            $clone = fn () => Transaction::query()->setQuery(clone $base);

            $credit = (float) (clone $clone())->sum('credit');
            $debit = (float) (clone $clone())->sum('debit');
            $commission = (float) (clone $clone())->whereIn('trx_type', self::COMMISSION_TYPES)->sum('credit');
            $provider = (float) (clone $clone())->whereIn('trx_type', self::PROVIDER_EARNING_TYPES)->sum('credit');
            $refund = (float) (clone $clone())->whereIn('trx_type', self::REFUND_TYPES)->sum('credit');
            $wallet = (float) (clone $clone())->whereIn('trx_type', array_values(WALLET_TRX_TYPE))->sum('credit');

            $bookingBase = Booking::query();
            ReportFilter::apply($bookingBase, $request);
            $tax = (float) (clone $bookingBase)->sum('total_tax_amount');
            $discount = (float) (clone $bookingBase)->sum('total_discount_amount')
                + (float) (clone $bookingBase)->sum('total_coupon_discount_amount')
                + (float) (clone $bookingBase)->sum('total_campaign_discount_amount');

            return [
                'total_transactions' => (int) (clone $clone())->count(),
                'total_revenue' => $credit,
                'total_debit' => $debit,
                'total_commission' => $commission,
                'provider_earnings' => $provider,
                'admin_earnings' => $commission,
                'total_refund' => $refund,
                'total_discount' => $discount,
                'total_tax' => $tax,
                'wallet_amount' => $wallet,
            ];
        });
    }

    public function transactionCharts(Request $request): array
    {
        $granularity = ReportFilter::granularity($request);
        $expr = ReportFilter::groupExpression($granularity, 'transactions.created_at');
        $query = $this->withoutOrder($this->transactionQuery($request))->getQuery();

        $rows = Transaction::query()->setQuery(clone $query);
        $rows->getQuery()->columns = null;
        $rows = $rows
            ->selectRaw("$expr as bucket")
            ->selectRaw('COUNT(*) as volume')
            ->selectRaw('SUM(credit) as revenue')
            ->selectRaw('SUM(CASE WHEN trx_type IN (' . $this->quoted(self::COMMISSION_TYPES) . ') THEN credit ELSE 0 END) as commission')
            ->selectRaw('SUM(CASE WHEN trx_type IN (' . $this->quoted(self::PROVIDER_EARNING_TYPES) . ') THEN credit ELSE 0 END) as provider_earning')
            ->selectRaw('SUM(CASE WHEN trx_type IN (' . $this->quoted(self::REFUND_TYPES) . ') THEN credit ELSE 0 END) as refund')
            ->groupBy('bucket')
            ->orderBy('bucket')
            ->get();

        $methods = Booking::query();
        ReportFilter::apply($methods, $request);
        $methodRows = $methods->select('payment_method', DB::raw('COUNT(*) as total'), DB::raw('SUM(total_booking_amount) as amount'))
            ->groupBy('payment_method')
            ->get();

        return [
            'timeline' => $rows->pluck('bucket')->all(),
            'volume' => $rows->pluck('volume')->map(fn ($v) => (int) $v)->all(),
            'revenue' => $rows->pluck('revenue')->map(fn ($v) => (float) $v)->all(),
            'commission' => $rows->pluck('commission')->map(fn ($v) => (float) $v)->all(),
            'provider_earning' => $rows->pluck('provider_earning')->map(fn ($v) => (float) $v)->all(),
            'refund' => $rows->pluck('refund')->map(fn ($v) => (float) $v)->all(),
            'methods' => [
                'labels' => $methodRows->pluck('payment_method')->map(fn ($v) => $v ?: translate('other'))->all(),
                'series' => $methodRows->pluck('total')->map(fn ($v) => (int) $v)->all(),
            ],
        ];
    }

    public function transactionDailyRows(Request $request)
    {
        $expr = ReportFilter::groupExpression(ReportFilter::granularity($request), 'transactions.created_at');
        $query = $this->withoutOrder($this->transactionQuery($request))->getQuery();

        $rows = Transaction::query()->setQuery(clone $query);
        $rows->getQuery()->columns = null;

        return $rows
            ->leftJoin('bookings', 'bookings.id', '=', 'transactions.booking_id')
            ->selectRaw("$expr as report_date")
            ->selectRaw('COUNT(transactions.id) as transaction_count')
            ->selectRaw('SUM(transactions.credit) as gross_amount')
            ->selectRaw('SUM(COALESCE(bookings.total_discount_amount, 0) + COALESCE(bookings.total_coupon_discount_amount, 0)) as discount')
            ->selectRaw('SUM(COALESCE(bookings.total_tax_amount, 0)) as tax')
            ->selectRaw('SUM(CASE WHEN transactions.trx_type IN (' . $this->quoted(self::COMMISSION_TYPES) . ') THEN transactions.credit ELSE 0 END) as commission')
            ->selectRaw('SUM(CASE WHEN transactions.trx_type IN (' . $this->quoted(self::PROVIDER_EARNING_TYPES) . ') THEN transactions.credit ELSE 0 END) as provider_earning')
            ->selectRaw('SUM(CASE WHEN transactions.trx_type IN (' . $this->quoted(self::COMMISSION_TYPES) . ') THEN transactions.credit ELSE 0 END) as admin_earning')
            ->selectRaw('SUM(CASE WHEN transactions.trx_type IN (' . $this->quoted(self::REFUND_TYPES) . ') THEN transactions.credit ELSE 0 END) as refund')
            ->selectRaw('SUM(transactions.credit) - SUM(CASE WHEN transactions.trx_type IN (' . $this->quoted(self::REFUND_TYPES) . ') THEN transactions.credit ELSE 0 END) as net_revenue')
            ->groupBy('report_date')
            ->orderByDesc('report_date')
            ->paginate(pagination_limit())
            ->appends($request->query());
    }

    public function bookingQuery(Request $request)
    {
        $query = Booking::query()->with([
            'customer:id,first_name,last_name,email,phone',
            'provider:id,first_name,last_name,email,phone',
            'zone:id,name',
            'details_amounts',
            'detail',
        ]);
        ReportFilter::apply($query, $request);

        if ($request->filled('search')) {
            $term = '%' . str_replace(['%', '_'], ['\%', '\_'], $request->input('search')) . '%';
            $query->where(function ($inner) use ($term) {
                $inner->where('id', 'like', $term)
                    ->orWhere('readable_id', 'like', $term)
                    ->orWhere('transaction_id', 'like', $term);
            });
        }
        if ($request->filled('zone_id')) {
            $query->where('zone_id', $request->input('zone_id'));
        }
        if ($request->filled('zone_ids')) {
            $query->whereIn('zone_id', (array) $request->input('zone_ids'));
        }
        if ($request->filled('provider_id')) {
            $query->where('provider_id', $request->input('provider_id'));
        }
        if ($request->filled('provider_ids')) {
            $query->whereIn('provider_id', (array) $request->input('provider_ids'));
        }
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }
        if ($request->filled('category_ids')) {
            $query->whereIn('category_id', (array) $request->input('category_ids'));
        }
        if ($request->filled('service_id')) {
            $serviceId = $request->input('service_id');
            $query->whereHas('detail', fn ($detail) => $detail->where('service_id', $serviceId));
        }
        if ($request->filled('booking_status') && $request->input('booking_status') !== 'all') {
            $query->where('booking_status', $request->input('booking_status'));
        }

        return $query->latest();
    }

    public function bookingSummary(Request $request): array
    {
        return Cache::remember($this->cacheKey('booking_summary', $request), 60, function () use ($request) {
            $base = $this->withoutOrder($this->bookingQuery($request));
            $base->getQuery()->columns = null;
            $counts = (clone $base)->selectRaw('booking_status, COUNT(*) as total')
                ->groupBy('booking_status')
                ->pluck('total', 'booking_status');

            $completed = (clone $base)->where('booking_status', 'completed');
            $revenue = (float) (clone $completed)->sum('total_booking_amount');
            $commission = (float) BookingDetailsAmount::query()
                ->whereIn('booking_id', (clone $completed)->select('id'))
                ->sum('admin_commission');
            $provider = (float) BookingDetailsAmount::query()
                ->whereIn('booking_id', (clone $completed)->select('id'))
                ->sum('provider_earning');

            $canceledPaid = (clone $base)->where('booking_status', 'canceled')->where('is_paid', 1);
            $refund = (float) (clone $canceledPaid)->sum('total_booking_amount');

            return [
                'total' => (int) (clone $base)->count(),
                'completed' => (int) ($counts['completed'] ?? 0),
                'pending' => (int) ($counts['pending'] ?? 0),
                'accepted' => (int) ($counts['accepted'] ?? 0),
                'canceled' => (int) ($counts['canceled'] ?? 0),
                'ongoing' => (int) ($counts['ongoing'] ?? 0),
                'rejected' => (int) ($counts['rejected'] ?? 0),
                'revenue' => $revenue,
                'provider_earnings' => $provider,
                'admin_commission' => $commission,
                'refund' => $refund,
                'average_value' => (clone $completed)->count() ? $revenue / max((clone $completed)->count(), 1) : 0,
            ];
        });
    }

    public function bookingCharts(Request $request): array
    {
        $granularity = ReportFilter::granularity($request);
        $expr = ReportFilter::groupExpression($granularity, 'bookings.created_at');
        $rows = $this->withoutOrder($this->bookingQuery($request));
        $rows->getQuery()->columns = null;
        $rows = $rows
            ->selectRaw("$expr as bucket")
            ->selectRaw('COUNT(*) as volume')
            ->selectRaw('SUM(total_booking_amount) as revenue')
            ->selectRaw("SUM(CASE WHEN booking_status = 'canceled' THEN 1 ELSE 0 END) as canceled")
            ->groupBy('bucket')
            ->orderBy('bucket')
            ->get();

        $status = $this->withoutOrder($this->bookingQuery($request));
        $status->getQuery()->columns = null;
        $status = $status
            ->selectRaw('booking_status, COUNT(*) as total')
            ->groupBy('booking_status')
            ->get();

        $topServices = DB::table('booking_details')
            ->join('bookings', 'bookings.id', '=', 'booking_details.booking_id')
            ->leftJoin('services', 'services.id', '=', 'booking_details.service_id')
            ->when(($bounds = ReportFilter::bounds($request)) && $bounds[0], function ($query) use ($bounds) {
                $query->whereBetween('bookings.created_at', $bounds);
            })
            ->select('services.name', DB::raw('COUNT(*) as total'), DB::raw('SUM(bookings.total_booking_amount) as revenue'))
            ->groupBy('services.name')
            ->orderByDesc('total')
            ->limit(8)
            ->get();

        $topCategories = Booking::query();
        ReportFilter::apply($topCategories, $request);
        $topCategories = $topCategories
            ->select('category_id', DB::raw('COUNT(*) as total'))
            ->groupBy('category_id')
            ->orderByDesc('total')
            ->limit(8)
            ->get();
        $categoryNames = Category::query()->whereIn('id', $topCategories->pluck('category_id')->filter())->pluck('name', 'id');

        $topProviders = Booking::query();
        ReportFilter::apply($topProviders, $request);
        $topProviders = $topProviders
            ->select('provider_id', DB::raw('COUNT(*) as total'), DB::raw('SUM(total_booking_amount) as revenue'))
            ->groupBy('provider_id')
            ->orderByDesc('revenue')
            ->limit(8)
            ->get();
        $providerNames = Provider::query()->whereIn('id', $topProviders->pluck('provider_id')->filter())->pluck('company_name', 'id');

        return [
            'timeline' => $rows->pluck('bucket')->all(),
            'volume' => $rows->pluck('volume')->map(fn ($v) => (int) $v)->all(),
            'revenue' => $rows->pluck('revenue')->map(fn ($v) => (float) $v)->all(),
            'canceled' => $rows->pluck('canceled')->map(fn ($v) => (int) $v)->all(),
            'status' => [
                'labels' => $status->pluck('booking_status')->all(),
                'series' => $status->pluck('total')->map(fn ($v) => (int) $v)->all(),
            ],
            'top_services' => $topServices,
            'top_categories' => $topCategories->map(fn ($row) => [
                'name' => $categoryNames[$row->category_id] ?? translate('unknown'),
                'total' => (int) $row->total,
            ]),
            'top_providers' => $topProviders->map(fn ($row) => [
                'name' => $providerNames[$row->provider_id] ?? translate('unknown'),
                'total' => (int) $row->total,
                'revenue' => (float) $row->revenue,
            ]),
        ];
    }

    public function businessSummary(Request $request): array
    {
        return Cache::remember($this->cacheKey('business_summary', $request), 60, function () use ($request) {
            $bookings = Booking::query();
            ReportFilter::apply($bookings, $request);
            $this->applyBusinessFilters($bookings, $request);

            $customers = User::query()->whereIn('user_type', defined('CUSTOMER_USER_TYPES') ? CUSTOMER_USER_TYPES : ['customer']);
            ReportFilter::apply($customers, $request);
            $providers = Provider::query();
            ReportFilter::apply($providers, $request);

            $completed = (clone $bookings)->where('booking_status', 'completed');
            $gross = (float) (clone $completed)->sum('total_booking_amount');
            $discount = (float) (clone $completed)->sum('total_discount_amount')
                + (float) (clone $completed)->sum('total_coupon_discount_amount')
                + (float) (clone $completed)->sum('total_campaign_discount_amount');
            $commission = (float) BookingDetailsAmount::query()
                ->whereIn('booking_id', (clone $completed)->select('id'))
                ->sum('admin_commission');
            $refund = (float) (clone $bookings)->where('booking_status', 'canceled')->where('is_paid', 1)->sum('total_booking_amount');
            $count = (int) (clone $completed)->count();
            $previous = $this->previousPeriodGrowth($request, $gross, (int) (clone $bookings)->count(), (int) (clone $customers)->count(), (int) (clone $providers)->count());

            return [
                'total_orders' => (int) (clone $bookings)->count(),
                'total_bookings' => (int) (clone $bookings)->count(),
                'total_customers' => (int) (clone $customers)->count(),
                'total_providers' => (int) (clone $providers)->count(),
                'gross_revenue' => $gross,
                'net_revenue' => max($gross - $refund - $discount, 0),
                'commission' => $commission,
                'refunds' => $refund,
                'discounts' => $discount,
                'average_order_value' => $count ? $gross / $count : 0,
                'average_booking_value' => $count ? $gross / $count : 0,
                'growth' => $previous,
            ];
        });
    }

    public function businessBreakdowns(Request $request): array
    {
        $bookings = Booking::query()->where('booking_status', 'completed');
        ReportFilter::apply($bookings, $request);
        $this->applyBusinessFilters($bookings, $request);

        $byCategory = (clone $bookings)->select('category_id', DB::raw('SUM(total_booking_amount) as revenue'), DB::raw('COUNT(*) as total'))
            ->groupBy('category_id')->orderByDesc('revenue')->limit(10)->get();
        $names = Category::query()->whereIn('id', $byCategory->pluck('category_id')->filter())->pluck('name', 'id');

        $byZone = (clone $bookings)->select('zone_id', DB::raw('SUM(total_booking_amount) as revenue'), DB::raw('COUNT(*) as total'))
            ->groupBy('zone_id')->orderByDesc('revenue')->limit(10)->get();
        $zones = Zone::query()->whereIn('id', $byZone->pluck('zone_id')->filter())->pluck('name', 'id');

        $byProvider = (clone $bookings)->select('provider_id', DB::raw('SUM(total_booking_amount) as revenue'), DB::raw('COUNT(*) as total'))
            ->groupBy('provider_id')->orderByDesc('revenue')->limit(10)->get();
        $providers = Provider::query()->whereIn('id', $byProvider->pluck('provider_id')->filter())->pluck('company_name', 'id');

        $byMethod = (clone $bookings)->select('payment_method', DB::raw('SUM(total_booking_amount) as revenue'), DB::raw('COUNT(*) as total'))
            ->groupBy('payment_method')->orderByDesc('revenue')->get();

        $byService = DB::table('booking_details')
            ->join('bookings', 'bookings.id', '=', 'booking_details.booking_id')
            ->leftJoin('services', 'services.id', '=', 'booking_details.service_id')
            ->where('bookings.booking_status', 'completed')
            ->when(($bounds = ReportFilter::bounds($request)) && $bounds[0], fn ($q) => $q->whereBetween('bookings.created_at', $bounds))
            ->select('services.name', DB::raw('SUM(bookings.total_booking_amount) as revenue'), DB::raw('COUNT(*) as total'))
            ->groupBy('services.name')
            ->orderByDesc('revenue')
            ->limit(10)
            ->get();

        $granularity = ReportFilter::granularity($request);
        $expr = ReportFilter::groupExpression($granularity);
        $trend = clone $bookings;
        $trend->getQuery()->columns = null;
        $trend = $trend->selectRaw("$expr as bucket")
            ->selectRaw('SUM(total_booking_amount) as revenue')
            ->selectRaw('COUNT(*) as volume')
            ->groupBy('bucket')->orderBy('bucket')->get();

        return [
            'by_category' => $byCategory->map(fn ($row) => ['name' => $names[$row->category_id] ?? '-', 'revenue' => (float) $row->revenue, 'total' => (int) $row->total]),
            'by_zone' => $byZone->map(fn ($row) => ['name' => $zones[$row->zone_id] ?? '-', 'revenue' => (float) $row->revenue, 'total' => (int) $row->total]),
            'by_provider' => $byProvider->map(fn ($row) => ['name' => $providers[$row->provider_id] ?? '-', 'revenue' => (float) $row->revenue, 'total' => (int) $row->total]),
            'by_method' => $byMethod,
            'by_service' => $byService,
            'trend' => $trend,
        ];
    }

    public function providerPerformance(Request $request)
    {
        $from = ReportFilter::bounds($request)[0];
        $to = ReportFilter::bounds($request)[1];

        $bookingAgg = Booking::query()
            ->when($from && $to, fn ($q) => $q->whereBetween('created_at', [$from, $to]))
            ->when($request->filled('zone_id'), fn ($q) => $q->where('zone_id', $request->input('zone_id')))
            ->when($request->filled('zone_ids'), fn ($q) => $q->whereIn('zone_id', (array) $request->input('zone_ids')))
            ->when($request->filled('category_id'), fn ($q) => $q->where('category_id', $request->input('category_id')))
            ->when($request->filled('service_id'), fn ($q) => $q->whereHas('detail', fn ($d) => $d->where('service_id', $request->input('service_id'))))
            ->selectRaw('provider_id')
            ->selectRaw('COUNT(*) as total_bookings')
            ->selectRaw("SUM(CASE WHEN booking_status = 'completed' THEN 1 ELSE 0 END) as completed")
            ->selectRaw("SUM(CASE WHEN booking_status = 'canceled' THEN 1 ELSE 0 END) as canceled")
            ->selectRaw('SUM(total_booking_amount) as revenue')
            ->groupBy('provider_id');

        $earningAgg = BookingDetailsAmount::query()
            ->join('bookings', 'bookings.id', '=', 'booking_details_amounts.booking_id')
            ->when($from && $to, fn ($q) => $q->whereBetween('bookings.created_at', [$from, $to]))
            ->selectRaw('bookings.provider_id')
            ->selectRaw('SUM(provider_earning) as provider_earning')
            ->selectRaw('SUM(admin_commission) as admin_commission')
            ->groupBy('bookings.provider_id');

        $query = Provider::query()
            ->leftJoinSub($bookingAgg, 'b', 'b.provider_id', '=', 'providers.id')
            ->leftJoinSub($earningAgg, 'e', 'e.provider_id', '=', 'providers.id')
            ->select('providers.*')
            ->selectRaw('COALESCE(b.total_bookings, 0) as total_bookings')
            ->selectRaw('COALESCE(b.completed, 0) as completed_bookings')
            ->selectRaw('COALESCE(b.canceled, 0) as canceled_bookings')
            ->selectRaw('COALESCE(b.revenue, 0) as revenue_generated')
            ->selectRaw('COALESCE(e.provider_earning, 0) as provider_earning')
            ->selectRaw('COALESCE(e.admin_commission, 0) as admin_commission');

        if ($request->filled('provider_id')) {
            $query->where('providers.id', $request->input('provider_id'));
        }
        if ($request->filled('provider_ids')) {
            $query->whereIn('providers.id', (array) $request->input('provider_ids'));
        }
        if ($request->filled('zone_id')) {
            $query->where('providers.zone_id', $request->input('zone_id'));
        }
        if ($request->filled('zone_ids')) {
            $query->whereIn('providers.zone_id', (array) $request->input('zone_ids'));
        }
        if ($request->filled('status') && $request->input('status') !== 'all') {
            $query->where('providers.is_active', $request->input('status') === 'active' ? 1 : 0);
        }
        if ($request->filled('search')) {
            $term = '%' . $request->input('search') . '%';
            $query->where(function ($inner) use ($term) {
                $inner->where('company_name', 'like', $term)
                    ->orWhere('company_email', 'like', $term)
                    ->orWhere('company_phone', 'like', $term);
            });
        }

        return $query->orderByDesc('revenue_generated')->paginate(pagination_limit())->appends($request->query());
    }

    public function providerSummary(Request $request): array
    {
        $providers = Provider::query();
        $from = ReportFilter::bounds($request)[0];
        $to = ReportFilter::bounds($request)[1];
        $bookings = Booking::query()->when($from && $to, fn ($q) => $q->whereBetween('created_at', [$from, $to]));
        $earnings = BookingDetailsAmount::query()
            ->join('bookings', 'bookings.id', '=', 'booking_details_amounts.booking_id')
            ->when($from && $to, fn ($q) => $q->whereBetween('bookings.created_at', [$from, $to]));

        return [
            'total' => (int) (clone $providers)->count(),
            'active' => (int) (clone $providers)->where('is_active', 1)->count(),
            'inactive' => (int) (clone $providers)->where('is_active', 0)->count(),
            'earnings' => (float) (clone $earnings)->sum('provider_earning'),
            'commission' => (float) (clone $earnings)->sum('admin_commission'),
            'bookings' => (int) (clone $bookings)->count(),
            'avg_rating' => (float) (clone $providers)->avg('avg_rating'),
        ];
    }

    public function keywordAnalytics(Request $request): array
    {
        $searches = RecentSearch::query()->withTrashed();
        ReportFilter::apply($searches, $request);
        if ($request->filled('keyword')) {
            $searches->where('keyword', 'like', '%' . $request->input('keyword') . '%');
        }

        $data = SearchedData::query();
        ReportFilter::apply($data, $request);
        if ($request->filled('zone_id')) {
            $data->where('zone_id', $request->input('zone_id'));
        }

        $table = RecentSearch::query()->withTrashed();
        ReportFilter::apply($table, $request);
        if ($request->filled('keyword')) {
            $table->where('keyword', 'like', '%' . $request->input('keyword') . '%');
        }

        $keywordRows = $table
            ->select('keyword')
            ->selectRaw('COUNT(*) as search_count')
            ->selectRaw('COUNT(DISTINCT user_id) as unique_users')
            ->selectRaw('MAX(created_at) as last_searched')
            ->groupBy('keyword')
            ->orderByDesc('search_count')
            ->paginate(pagination_limit())
            ->appends($request->query());

        $zero = (clone $data)->where('response_data_count', 0)->count();
        $today = RecentSearch::query()->withTrashed()->whereDate('created_at', now()->toDateString())->count();
        $month = RecentSearch::query()->withTrashed()->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count();
        $top = (clone $searches)->select('keyword', DB::raw('COUNT(*) as total'))->groupBy('keyword')->orderByDesc('total')->limit(10)->get();

        $granularity = ReportFilter::granularity($request);
        $expr = ReportFilter::groupExpression($granularity);
        $volume = RecentSearch::query()->withTrashed();
        ReportFilter::apply($volume, $request);
        $volume->getQuery()->columns = null;
        $volume = $volume->selectRaw("$expr as bucket")->selectRaw('COUNT(*) as total')->groupBy('bucket')->orderBy('bucket')->get();

        return [
            'total_searches' => (int) (clone $searches)->count(),
            'unique_users' => (int) (clone $searches)->whereNotNull('user_id')->selectRaw('COUNT(DISTINCT user_id) as aggregate')->value('aggregate'),
            'today' => $today,
            'this_month' => $month,
            'top_keyword' => optional($top->first())->keyword,
            'zero_results' => $zero,
            'top' => $top,
            'volume' => $volume,
            'rows' => $keywordRows,
        ];
    }

    public function customerAnalyticsQuery(Request $request)
    {
        $query = User::query()->whereIn('user_type', defined('CUSTOMER_USER_TYPES') ? CUSTOMER_USER_TYPES : ['customer'])
            ->select('users.*')
            ->withCount('bookings')
            ->withCount(['bookings as completed_bookings_count' => fn ($booking) => $booking->where('booking_status', 'completed')])
            ->with(['account'])
            ->addSelect([
                'total_spent' => Booking::query()->selectRaw('COALESCE(SUM(total_booking_amount), 0)')
                    ->whereColumn('customer_id', 'users.id')
                    ->where('booking_status', 'completed'),
                'total_refunds' => Booking::query()->selectRaw('COALESCE(SUM(total_booking_amount), 0)')
                    ->whereColumn('customer_id', 'users.id')
                    ->where('booking_status', 'canceled')
                    ->where('is_paid', 1),
                'total_discounts' => Booking::query()->selectRaw('COALESCE(SUM(total_discount_amount + total_coupon_discount_amount), 0)')
                    ->whereColumn('customer_id', 'users.id'),
                'last_booking_at' => Booking::query()->selectRaw('MAX(created_at)')
                    ->whereColumn('customer_id', 'users.id'),
            ]);

        if ($request->filled('search')) {
            $term = '%' . $request->input('search') . '%';
            $query->where(function ($inner) use ($term, $request) {
                $inner->where('first_name', 'like', $term)
                    ->orWhere('last_name', 'like', $term)
                    ->orWhere('email', 'like', $term)
                    ->orWhere('phone', 'like', $term)
                    ->orWhere('id', $request->input('search'));
            });
        }
        if ($request->filled('customer_id')) {
            $query->where('id', $request->input('customer_id'));
        }
        if ($request->filled('booking_id')) {
            $bookingId = $request->input('booking_id');
            $query->whereHas('bookings', fn ($b) => $b->where('id', $bookingId)->orWhere('readable_id', $bookingId));
        }
        if ($request->filled('order_id')) {
            $orderId = $request->input('order_id');
            $query->whereHas('bookings', fn ($b) => $b->where('id', $orderId)->orWhere('readable_id', $orderId));
        }

        return $query->orderByDesc('bookings_count');
    }

    public function customerDetail(User $customer, Request $request): array
    {
        $bookings = Booking::query()->where('customer_id', $customer->id);
        ReportFilter::apply($bookings, $request);
        $transactions = Transaction::query()->where(function ($q) use ($customer) {
            $q->where('from_user_id', $customer->id)->orWhere('to_user_id', $customer->id);
        });
        ReportFilter::apply($transactions, $request);

        $spent = (float) (clone $bookings)->where('booking_status', 'completed')->sum('total_booking_amount');
        $refunds = (float) (clone $bookings)->where('booking_status', 'canceled')->where('is_paid', 1)->sum('total_booking_amount');
        $discounts = (float) (clone $bookings)->sum('total_discount_amount');

        $granularity = ReportFilter::granularity($request);
        $expr = ReportFilter::groupExpression($granularity);
        $spendTrend = $this->withoutOrder(clone $bookings);
        $spendTrend->getQuery()->columns = null;
        $spendTrend = $spendTrend->selectRaw("$expr as bucket")
            ->selectRaw('SUM(total_booking_amount) as amount')
            ->selectRaw('COUNT(*) as volume')
            ->groupBy('bucket')->orderBy('bucket')->get();

        return [
            'spent' => $spent,
            'refunds' => $refunds,
            'discounts' => $discounts,
            'lifetime_value' => max($spent - $refunds, 0),
            'bookings' => (clone $bookings)->with(['zone', 'details_amounts'])->latest()->paginate(10, ['*'], 'booking_page'),
            'transactions' => (clone $transactions)->latest()->paginate(10, ['*'], 'trx_page'),
            'searches' => RecentSearch::query()->withTrashed()->where('user_id', $customer->id)->latest()->limit(20)->get(),
            'trend' => $spendTrend,
            'wallet' => optional($customer->account)->received_balance
                ?? optional($customer->account)->balance_pending
                ?? $customer->wallet_balance,
        ];
    }

    public function dropdowns(): array
    {
        return [
            'zones' => Zone::query()->select('id', 'name')->orderBy('name')->get(),
            'providers' => Provider::query()->select('id', 'company_name', 'company_phone')->orderBy('company_name')->get(),
            'categories' => Category::query()->ofType('main')->select('id', 'name')->orderBy('name')->get(),
            'services' => Service::query()->select('id', 'name')->orderBy('name')->limit(200)->get(),
            'trx_types' => array_values(array_unique(array_merge(array_values(TRX_TYPE), array_values(WALLET_TRX_TYPE)))),
        ];
    }

    private function applyBusinessFilters($query, Request $request): void
    {
        if ($request->filled('zone_id')) {
            $query->where('zone_id', $request->input('zone_id'));
        }
        if ($request->filled('zone_ids')) {
            $query->whereIn('zone_id', (array) $request->input('zone_ids'));
        }
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }
        if ($request->filled('provider_id')) {
            $query->where('provider_id', $request->input('provider_id'));
        }
        if ($request->filled('service_id')) {
            $query->whereHas('detail', fn ($d) => $d->where('service_id', $request->input('service_id')));
        }
    }

    private function previousPeriodGrowth(Request $request, float $revenue, int $bookings, int $customers, int $providers): array
    {
        [$from, $to] = ReportFilter::bounds($request);
        if (!$from || !$to) {
            return ['revenue' => 0, 'orders' => 0, 'bookings' => 0, 'customers' => 0, 'providers' => 0];
        }
        $span = $from->diffInSeconds($to);
        $prevFrom = $from->copy()->subSeconds($span + 1);
        $prevTo = $from->copy()->subSecond();

        $prevBookings = Booking::query()->whereBetween('created_at', [$prevFrom, $prevTo]);
        $prevRevenue = (float) (clone $prevBookings)->where('booking_status', 'completed')->sum('total_booking_amount');
        $prevCount = (int) (clone $prevBookings)->count();
        $prevCustomers = (int) User::query()->whereIn('user_type', defined('CUSTOMER_USER_TYPES') ? CUSTOMER_USER_TYPES : ['customer'])->whereBetween('created_at', [$prevFrom, $prevTo])->count();
        $prevProviders = (int) Provider::query()->whereBetween('created_at', [$prevFrom, $prevTo])->count();

        $pct = function ($now, $prev) {
            if ($prev <= 0) {
                return $now > 0 ? 100 : 0;
            }
            return round((($now - $prev) / $prev) * 100, 1);
        };

        return [
            'revenue' => $pct($revenue, $prevRevenue),
            'orders' => $pct($bookings, $prevCount),
            'bookings' => $pct($bookings, $prevCount),
            'customers' => $pct($customers, $prevCustomers),
            'providers' => $pct($providers, $prevProviders),
        ];
    }

    private function quoted(array $values): string
    {
        return collect($values)->map(fn ($v) => "'" . str_replace("'", "''", $v) . "'")->implode(',');
    }

    private function withoutOrder($builder)
    {
        $clone = clone $builder;
        $clone->getQuery()->orders = null;

        return $clone;
    }
}
