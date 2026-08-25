@extends('adminmodule::layouts.master')

@section('title', translate('transaction_details'))

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="page-title-wrap d-flex justify-content-between align-items-center gap-3 mb-3">
                <h2 class="page-title">{{translate('transaction_details')}}</h2>
                <div class="d-flex gap-2">
                    <a class="btn btn--secondary" href="{{route('admin.transaction.list')}}">{{translate('back')}}</a>
                    <a class="btn btn--primary" target="_blank" href="{{route('admin.transaction.print', $transaction->id)}}">{{translate('print')}}</a>
                </div>
            </div>
            <div class="card mstoo-notify-card">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4"><span class="text-muted">{{translate('transaction_id')}}</span><div class="fw-semibold">{{$transaction->id}}</div></div>
                        <div class="col-md-4"><span class="text-muted">{{translate('reference')}}</span><div>{{$transaction->ref_trx_id ?: '-'}}</div></div>
                        <div class="col-md-4"><span class="text-muted">{{translate('type')}}</span><div>{{$transaction->trx_type}}</div></div>
                        <div class="col-md-4"><span class="text-muted">{{translate('debit')}}</span><div>{{with_currency_symbol($transaction->debit)}}</div></div>
                        <div class="col-md-4"><span class="text-muted">{{translate('credit')}}</span><div>{{with_currency_symbol($transaction->credit)}}</div></div>
                        <div class="col-md-4"><span class="text-muted">{{translate('balance')}}</span><div>{{with_currency_symbol($transaction->balance)}}</div></div>
                        <div class="col-md-4"><span class="text-muted">{{translate('from')}}</span><div>{{trim(($transaction->from_user->first_name ?? '').' '.($transaction->from_user->last_name ?? '')) ?: ($transaction->from_user->email ?? '-')}}</div></div>
                        <div class="col-md-4"><span class="text-muted">{{translate('to')}}</span><div>{{optional(optional($transaction->to_user)->provider)->company_name ?: trim(($transaction->to_user->first_name ?? '').' '.($transaction->to_user->last_name ?? ''))}}</div></div>
                        <div class="col-md-4"><span class="text-muted">{{translate('booking')}}</span><div>{{optional($transaction->booking)->readable_id ?: '-'}}</div></div>
                        <div class="col-md-4"><span class="text-muted">{{translate('payment_method')}}</span><div>{{optional($transaction->booking)->payment_method ?: '-'}}</div></div>
                        <div class="col-md-4"><span class="text-muted">{{translate('note')}}</span><div>{{$transaction->reference_note ?: '-'}}</div></div>
                        <div class="col-md-4"><span class="text-muted">{{translate('date')}}</span><div>{{optional($transaction->created_at)->toDateTimeString()}}</div></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
