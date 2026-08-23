@extends('adminmodule::layouts.master')

@section('title', translate('customer_details'))

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            @include('customermodule::admin.detail._header')

            <div class="row g-3 mb-4">
                <div class="col-6 col-lg-3">
                    <div class="statistics-card statistics-card__style2 statistics-card__pending-withdraw h-100">
                        <h2>{{$metrics['total_bookings']}}</h2>
                        <h3>{{translate('total_bookings')}}</h3>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="statistics-card statistics-card__style2 statistics-card__already-withdraw h-100">
                        <h2>{{$metrics['completed_bookings']}}</h2>
                        <h3>{{translate('completed_bookings')}}</h3>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="statistics-card statistics-card__style2 statistics-card__canceled h-100">
                        <h2>{{$metrics['canceled_bookings']}}</h2>
                        <h3>{{translate('canceled')}}</h3>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="statistics-card statistics-card__style2 statistics-card__total-earning h-100">
                        <h2>{{with_currency_symbol($metrics['total_spent'])}}</h2>
                        <h3>{{translate('total_spent')}}</h3>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="statistics-card statistics-card__style2 h-100">
                        <h2>{{with_currency_symbol($metrics['wallet_balance'])}}</h2>
                        <h3>{{translate('wallet_balance')}}</h3>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="statistics-card statistics-card__style2 h-100">
                        <h2>{{$metrics['loyalty_points']}}</h2>
                        <h3>{{translate('loyalty_points')}}</h3>
                    </div>
                </div>
                <div class="col-12 col-lg-6">
                    <div class="statistics-card statistics-card__style2 h-100">
                        <h3 class="mb-2">{{translate('pro_membership')}}</h3>
                        @if(!empty($isPro) && $membership)
                            <div class="fw-semibold">{{translate('active')}} · {{ $membership->plan->name ?? '-' }}</div>
                            <div class="small text-muted">{{ optional($membership->starts_at)->format('d M Y') }} — {{ optional($membership->expires_at)->format('d M Y') }}</div>
                        @else
                            <div class="text-muted">{{translate('not_active')}}</div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-lg-7">
                    <div class="card mstoo-notify-card h-100">
                        <div class="card-header"><h4 class="mb-0">{{translate('profile')}}</h4></div>
                        <div class="card-body">
                            <div class="mstoo-stat-row"><span>{{translate('customer_id')}}</span><strong>{{$customer->id}}</strong></div>
                            <div class="mstoo-stat-row"><span>{{translate('name')}}</span><strong>{{ trim($customer->first_name.' '.$customer->last_name) }}</strong></div>
                            <div class="mstoo-stat-row"><span>{{translate('email')}}</span><strong>{{$customer->email ?: '-'}}</strong></div>
                            <div class="mstoo-stat-row"><span>{{translate('phone')}}</span><strong>{{$customer->phone ?: '-'}}</strong></div>
                            <div class="mstoo-stat-row"><span>{{translate('status')}}</span><strong>{{ $customer->is_active ? translate('active') : translate('inactive') }}</strong></div>
                            <div class="mstoo-stat-row"><span>{{translate('joined')}}</span><strong>{{ optional($customer->created_at)->format('d M Y H:i') }}</strong></div>
                            <div class="mstoo-stat-row"><span>{{translate('last_login')}}</span><strong>{{ $customer->last_login_at ? $customer->last_login_at->format('d M Y H:i') : translate('never') }}</strong></div>
                            <div class="mstoo-stat-row"><span>{{translate('phone_verified')}}</span><strong>{{ $customer->is_phone_verified ? translate('yes') : translate('no') }}</strong></div>
                            <div class="mstoo-stat-row"><span>{{translate('email_verified')}}</span><strong>{{ $customer->is_email_verified ? translate('yes') : translate('no') }}</strong></div>
                            @if(!empty($customer->document))
                                <div class="mt-3">
                                    <a class="btn btn--secondary" target="_blank" href="{{asset('storage/app/public/user/document')}}/{{$customer->document}}">{{translate('view_document')}}</a>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="col-lg-5">
                    <div class="card mstoo-notify-card h-100">
                        <div class="card-header"><h4 class="mb-0">{{translate('booking_overview')}}</h4></div>
                        <div class="card-body">
                            <div id="apex-pie-chart"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')
    <script src="{{asset('assets/admin-module')}}/plugins/apex/apexcharts.min.js"></script>
    <script>
        var chart = new ApexCharts(document.querySelector("#apex-pie-chart"), {
            labels: ['pending', 'accepted', 'ongoing', 'completed', 'canceled'],
            series: {!! json_encode($total) !!},
            chart: { type: 'donut', height: 260 },
            legend: { position: 'bottom' },
            dataLabels: { enabled: false }
        });
        chart.render();
    </script>
@endpush
