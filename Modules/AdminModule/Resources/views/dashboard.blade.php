@extends('adminmodule::layouts.master')

@section('title',translate('dashboard'))

@php
    $topCards = $data[0]['top_cards'] ?? [];
    $bookingCards = $data[1]['booking_cards'] ?? [];
    $recentBookings = $data[4]['bookings'] ?? collect();
    $recentCustomers = $data[7]['recent_customers'] ?? collect();
@endphp

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            @if(access_checker('dashboard'))
                <div class="page-title-wrap d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <h2 class="page-title mb-1">{{translate('dashboard')}}</h2>
                        <p class="text-muted mb-0">{{translate('welcome_to_admin_panel')}}</p>
                    </div>
                </div>

                <div class="row mb-4 g-4">
                    <div class="col-xl-3 col-sm-6">
                        <div class="mstoo-kpi">
                            <div class="kpi-icon customers"><span class="material-icons">groups</span></div>
                            <div class="kpi-label">{{translate('customers')}}</div>
                            <div class="kpi-value">{{ $topCards['total_customer'] ?? 0 }}</div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-sm-6">
                        <div class="mstoo-kpi">
                            <div class="kpi-icon new"><span class="material-icons">person_add</span></div>
                            <div class="kpi-label">New customers this month</div>
                            <div class="kpi-value">{{ $bookingCards['new_customers'] ?? 0 }}</div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-sm-6">
                        <div class="mstoo-kpi">
                            <div class="kpi-icon services"><span class="material-icons">design_services</span></div>
                            <div class="kpi-label">{{translate('services')}}</div>
                            <div class="kpi-value">{{ $topCards['total_services'] ?? 0 }}</div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-sm-6">
                        <div class="mstoo-kpi">
                            <div class="kpi-icon upcoming"><span class="material-icons">event_available</span></div>
                            <div class="kpi-label">Upcoming bookings</div>
                            <div class="kpi-value">{{ $bookingCards['accepted'] ?? 0 }}</div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-sm-6">
                        <div class="mstoo-kpi">
                            <div class="kpi-icon completed"><span class="material-icons">task_alt</span></div>
                            <div class="kpi-label">{{translate('Completed')}}</div>
                            <div class="kpi-value">{{ $bookingCards['completed'] ?? 0 }}</div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-sm-6">
                        <div class="mstoo-kpi">
                            <div class="kpi-icon pending"><span class="material-icons">pending_actions</span></div>
                            <div class="kpi-label">{{translate('pending')}}</div>
                            <div class="kpi-value">{{ $bookingCards['pending'] ?? 0 }}</div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-sm-6">
                        <div class="mstoo-kpi">
                            <div class="kpi-icon upcoming"><span class="material-icons">autorenew</span></div>
                            <div class="kpi-label">{{translate('Ongoing')}}</div>
                            <div class="kpi-value">{{ $bookingCards['ongoing'] ?? 0 }}</div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-sm-6">
                        <div class="mstoo-kpi">
                            <div class="kpi-icon pending"><span class="material-icons">cancel</span></div>
                            <div class="kpi-label">{{translate('Canceled')}}</div>
                            <div class="kpi-value">{{ $bookingCards['canceled'] ?? 0 }}</div>
                        </div>
                    </div>
                </div>

                <div class="row g-4">
                    <div class="col-lg-8">
                        <div class="mstoo-panel">
                            <div class="panel-head">
                                <h5>{{translate('recent_bookings')}}</h5>
                                <a href="{{route('admin.booking.list', ['booking_status'=>'accepted'])}}" class="btn-link">{{translate('view_all')}}</a>
                            </div>
                            <div class="panel-body">
                                @forelse($recentBookings as $booking)
                                    <div class="mstoo-list-item"
                                         onclick="location.href='{{route('admin.booking.details',[$booking->id])}}?web_page=details'">
                                        <div class="media align-items-center gap-3">
                                            <div class="avatar avatar-lg">
                                                <img class="avatar-img rounded"
                                                     src="{{asset('storage/app/public/service')}}/{{$booking->detail[0]->service->thumbnail??''}}"
                                                     onerror="this.src='{{asset('assets/placeholder.png')}}'"
                                                     alt="">
                                            </div>
                                            <div class="media-body">
                                                <h6>Booking# {{$booking->readable_id}}</h6>
                                                <p>{{date('d M Y, h:i a',strtotime($booking->created_at))}}</p>
                                            </div>
                                        </div>
                                        <span class="mstoo-badge {{$booking->booking_status}}">{{$booking->booking_status}}</span>
                                    </div>
                                @empty
                                    @include('adminmodule::layouts.partials._empty', ['icon' => 'calendar_month', 'title' => translate('No Bookings Found')])
                                @endforelse
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="mstoo-panel">
                            <div class="panel-head">
                                <h5>Recent customers</h5>
                                <a href="{{route('admin.customer.index')}}" class="btn-link">{{translate('view_all')}}</a>
                            </div>
                            <div class="panel-body">
                                @forelse($recentCustomers as $customer)
                                    <div class="mstoo-list-item"
                                         onclick="location.href='{{route('admin.customer.detail',[$customer->id, 'web_page'=>'overview'])}}'">
                                        <div>
                                            <h6>{{ trim(($customer->first_name ?? '').' '.($customer->last_name ?? '')) ?: translate('Customer') }}</h6>
                                            <p>{{ $customer->phone ?: $customer->email }}</p>
                                        </div>
                                        <span class="mstoo-badge {{ $customer->is_active ? 'approved' : 'pending' }}">
                                            {{ $customer->is_active ? translate('active') : translate('inactive') }}
                                        </span>
                                    </div>
                                @empty
                                    @include('adminmodule::layouts.partials._empty', ['icon' => 'person_off', 'title' => translate('No_data_found')])
                                @endforelse
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-5">
                        <div class="mstoo-panel">
                            <div class="panel-head">
                                <h5>Booking mix</h5>
                            </div>
                            <div class="panel-body px-3 pb-3">
                                <div id="mstoo_booking_chart"></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-7">
                        <div class="mstoo-panel">
                            <div class="panel-head">
                                <h5>{{translate('booking_statistics')}} - {{date('M, Y')}}</h5>
                            </div>
                            <div class="panel-body">
                                @if(!empty($data[6]['zone_wise_bookings']) && count($data[6]['zone_wise_bookings']))
                                    <ul class="common-list after-none gap-10 d-flex flex-column px-3 pb-3">
                                        @foreach($data[6]['zone_wise_bookings'] as $booking)
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
        var bookingMix = new ApexCharts(document.querySelector("#mstoo_booking_chart"), {
            chart: { type: 'donut', height: 320 },
            labels: ['Upcoming', 'Completed', 'Pending', 'Ongoing', 'Canceled'],
            series: [
                {{ (int) ($bookingCards['accepted'] ?? 0) }},
                {{ (int) ($bookingCards['completed'] ?? 0) }},
                {{ (int) ($bookingCards['pending'] ?? 0) }},
                {{ (int) ($bookingCards['ongoing'] ?? 0) }},
                {{ (int) ($bookingCards['canceled'] ?? 0) }}
            ],
            colors: ['#d97706', '#16a34a', '#2563eb', '#7c3aed', '#dc2626'],
            legend: { position: 'bottom' },
            stroke: { width: 0 },
            dataLabels: { enabled: false }
        });
        if (document.querySelector("#mstoo_booking_chart")) {
            bookingMix.render();
        }
    </script>
@endpush
