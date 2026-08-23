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
                                <th>{{translate('transaction_id')}}</th>
                                <th>{{translate('type')}}</th>
                                <th>{{translate('amount')}}</th>
                                <th>{{translate('date')}}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($transactions as $item)
                                <tr>
                                    <td>{{ \Illuminate\Support\Str::limit($item->id, 12, '') }}</td>
                                    <td>{{ $item->trx_type }}</td>
                                    <td>
                                        @if($item->credit > 0)
                                            + {{ with_currency_symbol($item->credit) }}
                                        @else
                                            - {{ with_currency_symbol($item->debit) }}
                                        @endif
                                    </td>
                                    <td>{{ optional($item->created_at)->format('d M Y H:i') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-muted py-4">{{translate('No_data_found')}}</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="d-flex justify-content-end">{!! $transactions->links() !!}</div>
                </div>
            </div>
        </div>
    </div>
@endsection
