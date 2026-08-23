@extends('adminmodule::layouts.master')

@section('title', translate('customer_details'))

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            @include('customermodule::admin.detail._header')
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="statistics-card statistics-card__style2 h-100">
                        <h2>{{with_currency_symbol($customer->wallet_balance)}}</h2>
                        <h3>{{translate('wallet_balance')}}</h3>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="statistics-card statistics-card__style2 h-100">
                        <h2>{{with_currency_symbol($credited)}}</h2>
                        <h3>{{translate('total_credited')}}</h3>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="statistics-card statistics-card__style2 h-100">
                        <h2>{{with_currency_symbol($debited)}}</h2>
                        <h3>{{translate('total_debited')}}</h3>
                    </div>
                </div>
            </div>

            @if(access_checker('customer_management', 'manage_wallet'))
                <div class="card mstoo-notify-card mb-4">
                    <div class="card-header"><h4 class="mb-0">{{translate('adjust_wallet')}}</h4></div>
                    <div class="card-body">
                        <form method="POST" action="{{route('admin.customer.wallet-adjust', [$customer->id])}}" class="row g-3">
                            @csrf
                            <div class="col-md-3">
                                <select name="type" class="form-control" required>
                                    <option value="credit">{{translate('credit')}}</option>
                                    <option value="debit">{{translate('debit')}}</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <input type="number" step="0.01" min="0.01" name="amount" class="form-control" placeholder="{{translate('amount')}}" required>
                            </div>
                            <div class="col-md-4">
                                <input type="text" name="reference" class="form-control" maxlength="80" placeholder="{{translate('reference')}}">
                            </div>
                            <div class="col-md-2">
                                <button class="btn btn--primary w-100" type="submit">{{translate('save')}}</button>
                            </div>
                        </form>
                    </div>
                </div>
            @endif

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
                            @forelse($walletTransactions as $item)
                                <tr>
                                    <td>{{ optional($item->created_at)->format('d M Y H:i') }}</td>
                                    <td>{{ $item->trx_type }}</td>
                                    <td>{{ with_currency_symbol($item->credit) }}</td>
                                    <td>{{ with_currency_symbol($item->debit) }}</td>
                                    <td>{{ with_currency_symbol($item->balance) }}</td>
                                    <td>{{ $item->reference_note }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center text-muted py-4">{{translate('No_data_found')}}</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="d-flex justify-content-end">{!! $walletTransactions->links() !!}</div>
                </div>
            </div>
        </div>
    </div>
@endsection
