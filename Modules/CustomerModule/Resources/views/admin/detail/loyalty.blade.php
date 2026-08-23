@extends('adminmodule::layouts.master')

@section('title', translate('customer_details'))

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            @include('customermodule::admin.detail._header')
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="statistics-card statistics-card__style2 h-100">
                        <h2>{{ $customer->loyalty_point }}</h2>
                        <h3>{{translate('current_points')}}</h3>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="statistics-card statistics-card__style2 h-100">
                        <h2>{{ $earned }}</h2>
                        <h3>{{translate('earned_points')}}</h3>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="statistics-card statistics-card__style2 h-100">
                        <h2>{{ $redeemed }}</h2>
                        <h3>{{translate('redeemed_points')}}</h3>
                    </div>
                </div>
            </div>
            <div class="card mstoo-notify-card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead>
                            <tr>
                                <th>{{translate('date')}}</th>
                                <th>{{translate('type')}}</th>
                                <th>{{translate('credit')}}</th>
                                <th>{{translate('debit')}}</th>
                                <th>{{translate('balance')}}</th>
                                <th>{{translate('reference')}}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($loyaltyTransactions as $item)
                                <tr>
                                    <td>{{ optional($item->created_at)->format('d M Y H:i') }}</td>
                                    <td>{{ $item->transaction_type }}</td>
                                    <td>{{ $item->credit }}</td>
                                    <td>{{ $item->debit }}</td>
                                    <td>{{ $item->balance }}</td>
                                    <td>{{ $item->reference }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center text-muted py-4">{{translate('No_data_found')}}</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="d-flex justify-content-end">{!! $loyaltyTransactions->links() !!}</div>
                </div>
            </div>
        </div>
    </div>
@endsection
