@extends('adminmodule::layouts.master')

@section('title', translate('customer_search'))

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="page-title-wrap d-flex justify-content-between align-items-center mb-3">
                <h2 class="page-title">{{$customer->first_name}} {{$customer->last_name}}</h2>
                <a class="btn btn--secondary" href="{{route('admin.analytics.search.customer')}}">{{translate('back')}}</a>
            </div>

            <div class="card mstoo-notify-card mb-3">
                <div class="card-body row g-3">
                    <div class="col-md-3"><span class="text-muted">{{translate('email')}}</span><div>{{$customer->email}}</div></div>
                    <div class="col-md-3"><span class="text-muted">{{translate('phone')}}</span><div>{{$customer->phone}}</div></div>
                    <div class="col-md-3"><span class="text-muted">{{translate('status')}}</span><div>{{$customer->is_active ? translate('active') : translate('inactive')}}</div></div>
                    <div class="col-md-3"><span class="text-muted">{{translate('registration_date')}}</span><div>{{optional($customer->created_at)->format('Y-m-d')}}</div></div>
                </div>
            </div>

            <div class="row g-3 mb-3">
                @foreach([
                    ['Total spent', with_currency_symbol($detail['spent'] ?? 0)],
                    ['Lifetime value', with_currency_symbol($detail['lifetime_value'] ?? 0)],
                    ['Refunds', with_currency_symbol($detail['refunds'] ?? 0)],
                    ['Discounts', with_currency_symbol($detail['discounts'] ?? 0)],
                    ['Wallet', with_currency_symbol($detail['wallet'] ?? 0)],
                ] as $card)
                    <div class="col-xl col-sm-6"><div class="mstoo-kpi"><div class="kpi-label">{{$card[0]}}</div><div class="kpi-value">{{$card[1]}}</div></div></div>
                @endforeach
            </div>

            <div class="card mb-3">
                <div class="card-body">
                    <h5>{{translate('spending_over_time')}}</h5>
                    <div id="customer-spend-chart"></div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header"><h4 class="mb-0">{{translate('booking_history')}}</h4></div>
                <div class="card-body table-responsive">
                    <table class="table">
                        <thead>
                        <tr>
                            <th>{{translate('booking')}}</th>
                            <th>{{translate('amount')}}</th>
                            <th>{{translate('status')}}</th>
                            <th>{{translate('date')}}</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($detail['bookings'] as $booking)
                            <tr>
                                <td><a href="{{route('admin.booking.details', [$booking->id, 'web_page'=>'details'])}}">{{$booking->readable_id}}</a></td>
                                <td>{{with_currency_symbol($booking->total_booking_amount)}}</td>
                                <td>{{translate($booking->booking_status)}}</td>
                                <td>{{optional($booking->created_at)->format('Y-m-d H:i')}}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center">{{translate('Data_not_available')}}</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                    {{$detail['bookings']->appends(request()->query())->links()}}
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header"><h4 class="mb-0">{{translate('transaction_history')}}</h4></div>
                <div class="card-body table-responsive">
                    <table class="table">
                        <thead>
                        <tr>
                            <th>{{translate('transaction_id')}}</th>
                            <th>{{translate('type')}}</th>
                            <th>{{translate('debit')}}</th>
                            <th>{{translate('credit')}}</th>
                            <th>{{translate('date')}}</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($detail['transactions'] as $trx)
                            <tr>
                                <td><a href="{{route('admin.transaction.show', $trx->id)}}">{{$trx->id}}</a></td>
                                <td>{{translate($trx->trx_type)}}</td>
                                <td>{{with_currency_symbol($trx->debit)}}</td>
                                <td>{{with_currency_symbol($trx->credit)}}</td>
                                <td>{{optional($trx->created_at)->format('Y-m-d H:i')}}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center">{{translate('Data_not_available')}}</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                    {{$detail['transactions']->appends(request()->query())->links()}}
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h4 class="mb-0">{{translate('search_activity')}}</h4></div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0">
                        @forelse($detail['searches'] as $search)
                            <li class="d-flex justify-content-between py-1">
                                <span>{{$search->keyword}}</span>
                                <span class="text-muted">{{optional($search->created_at)->format('Y-m-d H:i')}}</span>
                            </li>
                        @empty
                            <li>{{translate('Data_not_available')}}</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')
    <script src="{{asset('assets/admin-module')}}/plugins/apex/apexcharts.min.js"></script>
    <script>
        if (window.ApexCharts && document.querySelector('#customer-spend-chart')) {
            new ApexCharts(document.querySelector('#customer-spend-chart'), {
                chart: { type: 'line', height: 300, toolbar: { show: false } },
                series: [
                    { name: 'Spend', data: @json(collect($detail['trend'])->pluck('amount')->map(fn ($v) => (float) $v)->values()) },
                    { name: 'Bookings', data: @json(collect($detail['trend'])->pluck('volume')->map(fn ($v) => (int) $v)->values()) }
                ],
                xaxis: { categories: @json(collect($detail['trend'])->pluck('bucket')->values()) }
            }).render();
        }
    </script>
@endpush
