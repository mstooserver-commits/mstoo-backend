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
                                <th>{{translate('plan')}}</th>
                                <th>{{translate('status')}}</th>
                                <th>{{translate('start_date')}}</th>
                                <th>{{translate('expiry_date')}}</th>
                                <th>{{translate('amount_paid')}}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($memberships as $item)
                                <tr>
                                    <td>{{ $item->plan->name ?? '-' }}</td>
                                    <td>{{ translate($item->status) }}</td>
                                    <td>{{ optional($item->starts_at)->format('d M Y') ?: '-' }}</td>
                                    <td>{{ optional($item->expires_at)->format('d M Y') ?: '-' }}</td>
                                    <td>{{ with_currency_symbol($item->amount_paid) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-muted py-4">{{translate('not_active')}}</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
