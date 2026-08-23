@extends('adminmodule::layouts.master')

@section('title', translate('customer_details'))

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            @include('customermodule::admin.detail._header')
            <div class="card mstoo-notify-card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead>
                            <tr>
                                <th>{{translate('Booking_ID')}}</th>
                                <th>{{translate('service')}}</th>
                                <th>{{translate('Ratings')}}</th>
                                <th>{{translate('Reviews')}}</th>
                                <th>{{translate('date')}}</th>
                                <th>{{translate('status')}}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($reviews as $review)
                                <tr>
                                    <td>
                                        @if($review->booking)
                                            <a href="{{route('admin.booking.details', [$review->booking->id,'web_page'=>'details'])}}">{{$review->booking->readable_id}}</a>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>{{ $review->service->name ?? '-' }}</td>
                                    <td>{{ $review->review_rating }}</td>
                                    <td>{{ \Illuminate\Support\Str::limit($review->review_comment, 120) }}</td>
                                    <td>{{ optional($review->created_at)->format('d M Y') }}</td>
                                    <td>{{ $review->is_active ? translate('active') : translate('inactive') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center text-muted py-4">{{translate('No_data_found')}}</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="d-flex justify-content-end">{!! $reviews->links() !!}</div>
                </div>
            </div>
        </div>
    </div>
@endsection
