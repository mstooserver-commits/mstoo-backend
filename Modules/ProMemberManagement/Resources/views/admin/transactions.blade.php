@extends('adminmodule::layouts.master')

@section('title', translate('pro_membership_transactions'))

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="page-title-wrap mb-3">
                <h2 class="page-title">{{translate('pro_membership_transactions')}}</h2>
            </div>
            <div class="card mstoo-notify-card">
                <div class="card-body">
                    <form method="GET" class="row g-3 mb-4">
                        <div class="col-md-4"><input class="form-control" type="search" name="search" value="{{$search}}" placeholder="{{translate('search')}}"></div>
                        <div class="col-md-3">
                            <select name="status" class="form-control">
                                <option value="all">{{translate('all')}}</option>
                                @foreach(['pending','paid','failed','refunded','cancelled'] as $st)
                                    <option value="{{$st}}" {{$status==$st?'selected':''}}>{{translate($st)}}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select name="plan_id" class="form-control">
                                <option value="">{{translate('all_plans')}}</option>
                                @foreach($plans as $plan)
                                    <option value="{{$plan->id}}" {{$planId==$plan->id?'selected':''}}>{{$plan->name}}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2"><button class="btn btn--primary w-100">{{translate('search')}}</button></div>
                    </form>
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead>
                            <tr>
                                <th>{{translate('SL')}}</th>
                                <th>{{translate('transaction_id')}}</th>
                                <th>{{translate('customer')}}</th>
                                <th>{{translate('plan')}}</th>
                                <th>{{translate('amount')}}</th>
                                <th>{{translate('gateway')}}</th>
                                <th>{{translate('status')}}</th>
                                <th>{{translate('membership_status')}}</th>
                                <th>{{translate('date')}}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($transactions as $item)
                                <tr>
                                    <td>{{$transactions->firstItem() + $loop->index}}</td>
                                    <td>{{ \Illuminate\Support\Str::limit($item->gateway_transaction_id ?: $item->id, 16) }}</td>
                                    <td>{{ trim(($item->customer->first_name ?? '').' '.($item->customer->last_name ?? '')) ?: ($item->customer->email ?? '-') }}</td>
                                    <td>{{$item->plan->name ?? '-'}}</td>
                                    <td>{{with_currency_symbol($item->amount)}} {{$item->currency}}</td>
                                    <td>{{$item->payment_gateway}}</td>
                                    <td>{{translate($item->payment_status)}}</td>
                                    <td>{{translate($item->membership->status ?? '-')}}</td>
                                    <td>{{optional($item->created_at)->format('d M Y H:i')}}</td>
                                </tr>
                            @empty
                                <tr><td colspan="9" class="text-center py-5 text-muted">{{translate('no_transactions_found')}}</td></tr>
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
