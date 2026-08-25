@extends('adminmodule::layouts.master')

@section('title', translate('all_transactions'))

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="page-title-wrap d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
                <div>
                    <h2 class="page-title mb-1">{{translate('all_transactions')}}</h2>
                    <p class="text-muted mb-0">{{translate('transaction_reports_analytics')}}</p>
                </div>
                @if(access_checker('transaction_management', 'export') || access_checker('transaction_management'))
                    <div class="dropdown">
                        <button class="btn btn--secondary dropdown-toggle" data-bs-toggle="dropdown">
                            <span class="material-icons">file_download</span> {{translate('export')}}
                        </button>
                        <ul class="dropdown-menu">
                            <a class="dropdown-item" href="{{route('admin.transaction.download', array_merge(request()->query(), ['format'=>'xlsx']))}}">Excel</a>
                            <a class="dropdown-item" href="{{route('admin.transaction.download', array_merge(request()->query(), ['format'=>'csv']))}}">CSV</a>
                        </ul>
                    </div>
                @endif
            </div>

            <div class="row g-3 mb-3">
                @foreach([
                    ['label' => 'Total Transactions', 'value' => $summary['total_transactions'] ?? 0, 'icon' => 'receipt_long'],
                    ['label' => 'Total Revenue', 'value' => with_currency_symbol($summary['total_revenue'] ?? 0), 'icon' => 'payments'],
                    ['label' => 'Commission', 'value' => with_currency_symbol($summary['total_commission'] ?? 0), 'icon' => 'account_balance'],
                    ['label' => 'Provider Earnings', 'value' => with_currency_symbol($summary['provider_earnings'] ?? 0), 'icon' => 'storefront'],
                ] as $card)
                    <div class="col-xl-3 col-sm-6">
                        <div class="mstoo-kpi">
                            <div class="kpi-icon services"><span class="material-icons">{{$card['icon']}}</span></div>
                            <div class="kpi-label">{{$card['label']}}</div>
                            <div class="kpi-value">{{$card['value']}}</div>
                        </div>
                    </div>
                @endforeach
            </div>

            @include('adminmodule::admin.report.partials._filters', [
                'action' => route('admin.transaction.list'),
                'filters' => $filters,
                'dropdowns' => $dropdowns,
                'showZones' => false,
                'showProviders' => true,
                'showTransactionFilters' => true,
            ])

            <div class="card">
                <div class="card-body table-responsive">
                    <table class="table align-middle">
                        <thead>
                        <tr>
                            <th>{{translate('transaction_id')}}</th>
                            <th>{{translate('reference')}}</th>
                            <th>{{translate('customer')}}</th>
                            <th>{{translate('provider')}}</th>
                            <th>{{translate('booking')}}</th>
                            <th>{{translate('type')}}</th>
                            <th>{{translate('payment_method')}}</th>
                            <th>{{translate('amount')}}</th>
                            <th>{{translate('commission')}}</th>
                            <th>{{translate('tax')}}</th>
                            <th>{{translate('discount')}}</th>
                            <th>{{translate('status')}}</th>
                            <th>{{translate('date')}}</th>
                            <th>{{translate('actions')}}</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($transactions as $item)
                            @php
                                $from = $item->from_user;
                                $to = $item->to_user;
                                $booking = $item->booking;
                            @endphp
                            <tr>
                                <td><span class="fw-semibold">{{Str::limit($item->id, 8)}}</span></td>
                                <td>{{Str::limit($item->ref_trx_id, 8) ?: '-'}}</td>
                                <td>{{trim(($from->first_name ?? '').' '.($from->last_name ?? '')) ?: ($from->email ?? '-')}}</td>
                                <td>{{optional(optional($to)->provider)->company_name ?: (trim(($to->first_name ?? '').' '.($to->last_name ?? '')) ?: '-')}}</td>
                                <td>
                                    @if($booking)
                                        <a href="{{route('admin.booking.details', $booking->id)}}">{{$booking->readable_id}}</a>
                                    @else
                                        {{$item->booking_id ?: '-'}}
                                    @endif
                                </td>
                                <td>{{translate($item->trx_type ?: 'other')}}</td>
                                <td>{{optional($booking)->payment_method ?: '-'}}</td>
                                <td>{{with_currency_symbol(($item->credit ?: 0) + ($item->debit ?: 0))}}</td>
                                <td>{{with_currency_symbol(optional($booking)->details_amounts?->sum('admin_commission') ?? 0)}}</td>
                                <td>{{with_currency_symbol(optional($booking)->total_tax_amount ?? 0)}}</td>
                                <td>{{with_currency_symbol((optional($booking)->total_discount_amount ?? 0) + (optional($booking)->total_coupon_discount_amount ?? 0))}}</td>
                                <td>{{optional($booking)->booking_status ?: translate('posted')}}</td>
                                <td>{{optional($item->created_at)->format('Y-m-d H:i')}}</td>
                                <td class="text-nowrap">
                                    <a class="btn btn-sm btn--secondary" href="{{route('admin.transaction.show', $item->id)}}">{{translate('view')}}</a>
                                    <a class="btn btn-sm btn--primary" target="_blank" href="{{route('admin.transaction.print', $item->id)}}">{{translate('print')}}</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="14" class="text-center text-muted">{{translate('no_data_found')}}</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                    {{$transactions->links()}}
                </div>
            </div>
        </div>
    </div>
@endsection
