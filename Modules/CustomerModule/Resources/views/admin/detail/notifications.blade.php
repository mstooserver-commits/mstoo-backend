@extends('adminmodule::layouts.master')

@section('title', translate('customer_details'))

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            @include('customermodule::admin.detail._header')

            <div class="card mstoo-notify-card mb-4">
                <div class="card-header"><h4 class="mb-0">{{translate('device')}}</h4></div>
                <div class="card-body">
                    <div class="mstoo-stat-row"><span>{{translate('push_token')}}</span><strong>{{ $customer->fcm_token ? translate('active') : translate('inactive') }}</strong></div>
                    <div class="mstoo-stat-row"><span>{{translate('last_login')}}</span><strong>{{ $customer->last_login_at ? $customer->last_login_at->format('d M Y H:i') : translate('never') }}</strong></div>
                </div>
            </div>

            <div class="card mstoo-notify-card">
                <div class="card-header"><h4 class="mb-0">{{translate('notifications')}}</h4></div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead>
                            <tr>
                                <th>{{translate('title')}}</th>
                                <th>{{translate('type')}}</th>
                                <th>{{translate('date')}}</th>
                                <th>{{translate('status')}}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($notifications as $item)
                                <tr>
                                    <td>{{ $item->title }}</td>
                                    <td>{{ $item->target_type ?: 'push' }}</td>
                                    <td>{{ optional($item->sent_at ?: $item->created_at)->format('d M Y H:i') }}</td>
                                    <td>{{ $item->status ?: ($item->is_active ? translate('active') : translate('inactive')) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-muted py-4">{{translate('No_data_found')}}</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
