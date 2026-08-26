@extends('adminmodule::layouts.master')

@section('title',translate('dashboard'))

@php
    $topCards = $data[0]['top_cards'] ?? [];
    $bookingCards = $data[1]['booking_cards'] ?? [];
    $adminEarning = $data[2]['admin_total_earning'] ?? 0;
    $recentTransactions = $data[3]['recent_transactions'] ?? collect();
    $recentBookings = $data[4]['bookings'] ?? collect();
    $topProviders = $data[5]['top_providers'] ?? collect();
    $zoneWise = $data[6]['zone_wise_bookings'] ?? collect();
    $recentCustomers = $data[7]['recent_customers'] ?? collect();
    $totalBookings = (int) ($bookingCards['pending'] ?? 0)
        + (int) ($bookingCards['accepted'] ?? 0)
        + (int) ($bookingCards['ongoing'] ?? 0)
        + (int) ($bookingCards['completed'] ?? 0)
        + (int) ($bookingCards['canceled'] ?? 0);
    $chartYear = session('dashboard_earning_graph_year', date('Y'));
@endphp

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            @if(access_checker('dashboard'))
                <div class="page-title-wrap d-flex justify-content-between align-items-start flex-wrap gap-3">
                    <div>
                        <h2 class="page-title mb-1">{{translate('dashboard')}}</h2>
                        <p class="text-muted mb-0">
                            {{translate('welcome_back')}}, {{ auth()->user()->first_name ?: translate('Admin') }}.
                            {{translate('mstoo_dashboard_subtitle')}}
                        </p>
                    </div>
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <span class="text-muted small">{{ now()->format('d M Y') }}</span>
                        <a href="{{ route('admin.dashboard') }}" class="btn btn--secondary" title="{{translate('refresh')}}">
                            <span class="material-icons">refresh</span>
                            {{translate('refresh')}}
                        </a>
                    </div>
                </div>

                <div class="row mb-4 g-4">
                    <div class="col-xl-3 col-sm-6">
                        <div class="mstoo-kpi">
                            <div class="kpi-top">
                                <div class="kpi-icon customers"><span class="material-icons">groups</span></div>
                            </div>
                            <div class="kpi-label">{{translate('customers')}}</div>
                            <div class="kpi-value">{{ number_format((int) ($topCards['total_customer'] ?? 0)) }}</div>
                            <div class="kpi-meta">{{ (int) ($bookingCards['new_customers'] ?? 0) }} {{translate('new_this_month')}}</div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-sm-6">
                        <div class="mstoo-kpi">
                            <div class="kpi-top">
                                <div class="kpi-icon upcoming"><span class="material-icons">calendar_month</span></div>
                            </div>
                            <div class="kpi-label">{{translate('bookings')}}</div>
                            <div class="kpi-value">{{ number_format($totalBookings) }}</div>
                            <div class="kpi-meta">{{ (int) ($bookingCards['accepted'] ?? 0) }} {{translate('upcoming')}}</div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-sm-6">
                        <div class="mstoo-kpi">
                            <div class="kpi-top">
                                <div class="kpi-icon revenue"><span class="material-icons">payments</span></div>
                            </div>
                            <div class="kpi-label">{{translate('revenue')}}</div>
                            <div class="kpi-value">{{ function_exists('with_currency_symbol') ? with_currency_symbol($adminEarning) : number_format((float) $adminEarning, 2) }}</div>
                            <div class="kpi-meta">{{translate('completed_bookings')}}</div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-sm-6">
                        <div class="mstoo-kpi">
                            <div class="kpi-top">
                                <div class="kpi-icon services"><span class="material-icons">design_services</span></div>
                            </div>
                            <div class="kpi-label">{{translate('services')}}</div>
                            <div class="kpi-value">{{ number_format((int) ($topCards['total_services'] ?? 0)) }}</div>
                            <div class="kpi-meta">{{ number_format((int) ($topCards['total_provider'] ?? 0)) }} {{translate('providers')}}</div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-sm-6">
                        <div class="mstoo-kpi">
                            <div class="kpi-top"><div class="kpi-icon completed"><span class="material-icons">task_alt</span></div></div>
                            <div class="kpi-label">{{translate('Completed')}}</div>
                            <div class="kpi-value">{{ number_format((int) ($bookingCards['completed'] ?? 0)) }}</div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-sm-6">
                        <div class="mstoo-kpi">
                            <div class="kpi-top"><div class="kpi-icon pending"><span class="material-icons">pending_actions</span></div></div>
                            <div class="kpi-label">{{translate('pending')}}</div>
                            <div class="kpi-value">{{ number_format((int) ($bookingCards['pending'] ?? 0)) }}</div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-sm-6">
                        <div class="mstoo-kpi">
                            <div class="kpi-top"><div class="kpi-icon upcoming"><span class="material-icons">autorenew</span></div></div>
                            <div class="kpi-label">{{translate('Ongoing')}}</div>
                            <div class="kpi-value">{{ number_format((int) ($bookingCards['ongoing'] ?? 0)) }}</div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-sm-6">
                        <div class="mstoo-kpi">
                            <div class="kpi-top"><div class="kpi-icon canceled"><span class="material-icons">cancel</span></div></div>
                            <div class="kpi-label">{{translate('Canceled')}}</div>
                            <div class="kpi-value">{{ number_format((int) ($bookingCards['canceled'] ?? 0)) }}</div>
                        </div>
                    </div>
                </div>

                <div class="row g-4 mb-4">
                    <div class="col-lg-7">
                        <div class="mstoo-panel">
                            <div class="panel-head">
                                <h5>{{translate('revenue_overview')}}</h5>
                                <select id="mstoo-earning-year" class="form-select form-select-sm" style="width:auto">
                                    @for($year = date('Y'); $year >= date('Y') - 4; $year--)
                                        <option value="{{$year}}" {{ (int) $chartYear === (int) $year ? 'selected' : '' }}>{{ $year }}</option>
                                    @endfor
                                </select>
                            </div>
                            <div class="panel-body px-3 pb-3">
                                <div id="mstoo_earning_chart"></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-5">
                        <div class="mstoo-panel">
                            <div class="panel-head">
                                <h5>{{translate('booking_overview')}}</h5>
                            </div>
                            <div class="panel-body px-3 pb-3">
                                <div id="mstoo_booking_chart"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row g-4 mb-4">
                    <div class="col-lg-8">
                        <div class="mstoo-panel">
                            <div class="panel-head">
                                <h5>{{translate('recent_bookings')}}</h5>
                                <a href="{{route('admin.booking.list', ['booking_status'=>'accepted'])}}" class="btn-link">{{translate('view_all')}}</a>
                            </div>
                            <div class="panel-body p-0">
                                <div class="table-responsive">
                                    <table class="table mstoo-table mb-0">
                                        <thead>
                                        <tr>
                                            <th>{{translate('booking')}}</th>
                                            <th>{{translate('date')}}</th>
                                            <th>{{translate('amount')}}</th>
                                            <th>{{translate('status')}}</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @forelse($recentBookings as $booking)
                                            <tr onclick="location.href='{{route('admin.booking.details',[$booking->id])}}?web_page=details'" style="cursor:pointer">
                                                <td>#{{ $booking->readable_id }}</td>
                                                <td>{{ date('d M Y', strtotime($booking->created_at)) }}</td>
                                                <td>{{ function_exists('with_currency_symbol') ? with_currency_symbol($booking->total_booking_amount ?? 0) : number_format((float) ($booking->total_booking_amount ?? 0), 2) }}</td>
                                                <td><span class="mstoo-badge {{$booking->booking_status}}">{{$booking->booking_status}}</span></td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="4">
                                                    @include('adminmodule::layouts.partials._empty', ['icon' => 'calendar_month', 'title' => translate('No Bookings Found'), 'text' => translate('bookings_will_appear_here')])
                                                </td>
                                            </tr>
                                        @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="mstoo-panel">
                            <div class="panel-head">
                                <h5>{{translate('recent_customers')}}</h5>
                                <a href="{{route('admin.customer.index')}}" class="btn-link">{{translate('view_all')}}</a>
                            </div>
                            <div class="panel-body">
                                @forelse($recentCustomers as $customer)
                                    <div class="mstoo-list-item"
                                         onclick="location.href='{{route('admin.customer.detail',[$customer->id, 'web_page'=>'overview'])}}'">
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="avatar">
                                                <img class="avatar-img rounded-circle" width="36" height="36"
                                                     src="{{asset('storage/app/public/user/profile_image')}}/{{$customer->profile_image ?? ''}}"
                                                     onerror="this.src='{{asset('assets/admin-module')}}/img/user2x.png'"
                                                     alt="">
                                            </div>
                                            <div>
                                                <h6>{{ trim(($customer->first_name ?? '').' '.($customer->last_name ?? '')) ?: translate('Customer') }}</h6>
                                                <p>{{ $customer->phone ?: $customer->email }}</p>
                                            </div>
                                        </div>
                                        <span class="mstoo-badge {{ $customer->is_active ? 'active' : 'inactive' }}">
                                            {{ $customer->is_active ? translate('active') : translate('inactive') }}
                                        </span>
                                    </div>
                                @empty
                                    @include('adminmodule::layouts.partials._empty', ['icon' => 'person_off', 'title' => translate('No_data_found')])
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row g-4">
                    <div class="col-lg-7">
                        <div class="mstoo-panel">
                            <div class="panel-head">
                                <h5>{{translate('recent_transactions')}}</h5>
                                @if(access_checker('transaction_management'))
                                    <a href="{{route('admin.transaction.list')}}" class="btn-link">{{translate('view_all')}}</a>
                                @endif
                            </div>
                            <div class="panel-body p-0">
                                <div class="table-responsive">
                                    <table class="table mstoo-table mb-0">
                                        <thead>
                                        <tr>
                                            <th>{{translate('type')}}</th>
                                            <th>{{translate('date')}}</th>
                                            <th>{{translate('amount')}}</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @forelse($recentTransactions as $trx)
                                            <tr>
                                                <td>{{ str_replace('_', ' ', $trx->trx_type) }}</td>
                                                <td>{{ optional($trx->created_at)->format('d M Y') }}</td>
                                                <td>{{ function_exists('with_currency_symbol') ? with_currency_symbol(($trx->credit ?: $trx->debit) ?? 0) : number_format((float) (($trx->credit ?: $trx->debit) ?? 0), 2) }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="3">
                                                    @include('adminmodule::layouts.partials._empty', ['icon' => 'receipt_long', 'title' => translate('No_data_found')])
                                                </td>
                                            </tr>
                                        @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-5">
                        <div class="mstoo-panel">
                            <div class="panel-head">
                                <h5>{{translate('top_providers')}}</h5>
                            </div>
                            <div class="panel-body">
                                @forelse($topProviders as $provider)
                                    <div class="mstoo-list-item">
                                        <div>
                                            <h6>{{ $provider->company_name ?? optional($provider->owner)->first_name ?? translate('provider') }}</h6>
                                            <p>{{ number_format((float) $provider->avg_rating, 1) }} ★ · {{ $provider->reviews_count ?? 0 }} {{translate('reviews')}}</p>
                                        </div>
                                    </div>
                                @empty
                                    @include('adminmodule::layouts.partials._empty', ['icon' => 'storefront', 'title' => translate('No_data_found')])
                                @endforelse
                            </div>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="mstoo-panel">
                            <div class="panel-head">
                                <h5>{{translate('booking_statistics')}} - {{date('M, Y')}}</h5>
                            </div>
                            <div class="panel-body">
                                @if(!empty($zoneWise) && count($zoneWise))
                                    <ul class="common-list after-none gap-10 d-flex flex-column px-3 pb-3">
                                        @foreach($zoneWise as $booking)
                                            <li>
                                                <div class="mb-2 d-flex align-items-center justify-content-between gap-10 flex-wrap">
                                                    <span class="zone-name">{{$booking->zone?$booking->zone->name:translate('zone_not_available')}}</span>
                                                    <span class="booking-count">{{$booking->total}} {{translate('bookings')}}</span>
                                                </div>
                                                <div class="progress">
                                                    <div class="progress-bar" role="progressbar" style="width: {{ min(100, (int) $booking->total) }}%" aria-valuenow="{{$booking->total}}" aria-valuemin="0" aria-valuemax="100"></div>
                                                </div>
                                            </li>
                                        @endforeach
                                    </ul>
                                @else
                                    @include('adminmodule::layouts.partials._empty', ['icon' => 'map', 'title' => translate('No Bookings Found')])
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @else
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <h3 class="text-center mb-0">
                                    {{translate('welcome_to_admin_panel')}}
                                </h3>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection

@push('script')
    <script src="{{asset('assets/admin-module')}}/plugins/apex/apexcharts.min.js"></script>
    <script>
        var mstooRed = '#D9232E';
        var mstooGray = '#9CA3AF';
        if (document.querySelector("#mstoo_booking_chart")) {
            new ApexCharts(document.querySelector("#mstoo_booking_chart"), {
                chart: { type: 'donut', height: 300, fontFamily: 'Inter, sans-serif' },
                labels: ['Upcoming', 'Completed', 'Pending', 'Ongoing', 'Canceled'],
                series: [
                    {{ (int) ($bookingCards['accepted'] ?? 0) }},
                    {{ (int) ($bookingCards['completed'] ?? 0) }},
                    {{ (int) ($bookingCards['pending'] ?? 0) }},
                    {{ (int) ($bookingCards['ongoing'] ?? 0) }},
                    {{ (int) ($bookingCards['canceled'] ?? 0) }}
                ],
                colors: [mstooRed, '#16A34A', '#F59E0B', '#6B7280', '#B91C1C'],
                legend: { position: 'bottom' },
                stroke: { width: 0 },
                dataLabels: { enabled: false }
            }).render();
        }

        var earningChart = null;
        var earningSeries = @json($chart_data['total_earning'] ?? array_fill(0, 12, 0));
        if (document.querySelector("#mstoo_earning_chart")) {
            earningChart = new ApexCharts(document.querySelector("#mstoo_earning_chart"), {
                chart: { type: 'area', height: 300, toolbar: { show: false }, fontFamily: 'Inter, sans-serif' },
                series: [{ name: '{{translate('revenue')}}', data: earningSeries }],
                xaxis: { categories: ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'] },
                colors: [mstooRed],
                stroke: { curve: 'smooth', width: 2 },
                fill: { type: 'gradient', gradient: { opacityFrom: 0.28, opacityTo: 0.04 } },
                dataLabels: { enabled: false },
                grid: { borderColor: '#E5E7EB' }
            });
            earningChart.render();
        }

        $('#mstoo-earning-year').on('change', function () {
            $.get('{{ route('admin.update-dashboard-earning-graph') }}', { year: $(this).val() }, function (res) {
                if (earningChart && res && res.total_earning) {
                    earningChart.updateSeries([{ name: '{{translate('revenue')}}', data: res.total_earning }]);
                }
            });
        });
    </script>
@endpush
