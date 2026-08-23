@extends('adminmodule::layouts.master')

@section('title', translate('customer_details'))

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            @include('customermodule::admin.detail._header')
            <div class="card mstoo-notify-card">
                <div class="card-body">
                    <form method="GET" class="search-form search-form_style-two mb-4" action="{{route('admin.customer.detail', [$customer->id])}}">
                        <input type="hidden" name="web_page" value="bookings">
                        <div class="input-group search-form__input_group">
                            <span class="search-form__icon"><span class="material-icons">search</span></span>
                            <input type="search" class="theme-input-style search-form__input" name="search" value="{{$search??''}}" placeholder="{{translate('search_here')}}">
                        </div>
                        <button class="btn btn--primary" type="submit">{{translate('search')}}</button>
                    </form>
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead>
                            <tr>
                                <th>{{translate('Booking_ID')}}</th>
                                <th>{{translate('service')}}</th>
                                <th>{{translate('Provider_Info')}}</th>
                                <th>{{translate('Total_Amount')}}</th>
                                <th>{{translate('Booking_Status')}}</th>
                                <th>{{translate('Payment_Status')}}</th>
                                <th>{{translate('Booking_Date')}}</th>
                                <th>{{translate('action')}}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($bookings as $booking)
                                <tr>
                                    <td>{{$booking->readable_id}}</td>
                                    <td>{{ optional($booking->detail->first())->service_name ?? '-' }}</td>
                                    <td>{{ trim(($booking->provider->first_name ?? '').' '.($booking->provider->last_name ?? '')) ?: '-' }}</td>
                                    <td>{{with_currency_symbol($booking->total_booking_amount)}}</td>
                                    <td>{{translate($booking->booking_status)}}</td>
                                    <td>
                                        <span class="badge badge-{{$booking->is_paid ? 'success' : 'danger'}}">
                                            {{ $booking->is_paid ? translate('Paid') : translate('Unpaid') }}
                                        </span>
                                    </td>
                                    <td>{{ optional($booking->created_at)->format('d M Y H:i') }}</td>
                                    <td>
                                        @if(access_checker('booking_management', 'view') || auth()->user()->user_type === 'super-admin')
                                            <a href="{{route('admin.booking.details', [$booking->id,'web_page'=>'details'])}}" class="btn btn--light">
                                                <span class="material-symbols-outlined">visibility</span>
                                                {{translate('View_Details')}}
                                            </a>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="8" class="text-center text-muted py-4">{{translate('No_data_found')}}</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="d-flex justify-content-end">{!! $bookings->links() !!}</div>
                </div>
            </div>
        </div>
    </div>
@endsection
