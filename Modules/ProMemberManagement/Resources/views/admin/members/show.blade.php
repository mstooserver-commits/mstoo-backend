@extends('adminmodule::layouts.master')

@section('title', translate('membership_details'))

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="page-title-wrap d-flex justify-content-between mb-3">
                <h2 class="page-title">{{translate('membership_details')}}</h2>
                @if(in_array($membership->status, ['active','pending'], true) && access_checker('pro_member_management','edit'))
                    <form method="POST" action="{{route('admin.pro-member.members.cancel',[$membership->id])}}" onsubmit="return confirm('{{translate('are_you_sure')}}')">
                        @csrf
                        <button class="btn btn--secondary">{{translate('cancel_membership')}}</button>
                    </form>
                @endif
            </div>
            <div class="row">
                <div class="col-lg-6 mb-30">
                    <div class="card mstoo-notify-card h-100">
                        <div class="card-header"><h4 class="mb-0">{{translate('customer')}}</h4></div>
                        <div class="card-body">
                            <div class="mstoo-stat-row"><span>{{translate('name')}}</span><strong>{{ trim(($membership->customer->first_name ?? '').' '.($membership->customer->last_name ?? '')) }}</strong></div>
                            <div class="mstoo-stat-row"><span>{{translate('email')}}</span><strong>{{$membership->customer->email ?? '-'}}</strong></div>
                            <div class="mstoo-stat-row"><span>{{translate('phone')}}</span><strong>{{$membership->customer->phone ?? '-'}}</strong></div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6 mb-30">
                    <div class="card mstoo-notify-card h-100">
                        <div class="card-header"><h4 class="mb-0">{{translate('membership')}}</h4></div>
                        <div class="card-body">
                            <div class="mstoo-stat-row"><span>{{translate('membership_id')}}</span><strong>{{$membership->id}}</strong></div>
                            <div class="mstoo-stat-row"><span>{{translate('plan')}}</span><strong>{{$membership->plan->name ?? '-'}}</strong></div>
                            <div class="mstoo-stat-row"><span>{{translate('status')}}</span><strong>{{translate($membership->status)}}</strong></div>
                            <div class="mstoo-stat-row"><span>{{translate('payment_status')}}</span><strong>{{translate($membership->payment_status)}}</strong></div>
                            <div class="mstoo-stat-row"><span>{{translate('start_date')}}</span><strong>{{optional($membership->starts_at)->format('d M Y H:i') ?: '-'}}</strong></div>
                            <div class="mstoo-stat-row"><span>{{translate('expiry_date')}}</span><strong>{{optional($membership->expires_at)->format('d M Y H:i') ?: '-'}}</strong></div>
                            <div class="mstoo-stat-row"><span>{{translate('amount_paid')}}</span><strong>{{with_currency_symbol($membership->amount_paid)}}</strong></div>
                            <div class="mstoo-stat-row"><span>{{translate('auto_renewal')}}</span><strong>{{ $membership->auto_renew ? translate('yes') : translate('no') }}</strong></div>
                            <div class="mstoo-stat-row"><span>{{translate('payment_method')}}</span><strong>{{ $membership->payment_method ?: '-' }}</strong></div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6 mb-30">
                    <div class="card mstoo-notify-card">
                        <div class="card-header"><h4 class="mb-0">{{translate('active_benefits')}}</h4></div>
                        <div class="card-body">
                            @php($benefits = $config['benefits'])
                            <div class="mstoo-stat-row"><span>{{translate('discount')}}</span><strong>{{ $isActive && ($benefits['discount']['enabled']??0) ? ($benefits['discount']['percent'].'%') : translate('inactive') }}</strong></div>
                            <div class="mstoo-stat-row"><span>{{translate('coupon')}}</span><strong>{{ $isActive && ($benefits['coupon']['enabled']??0) ? translate('active') : translate('inactive') }}</strong></div>
                            <div class="mstoo-stat-row"><span>{{translate('service_fee')}}</span><strong>{{ $isActive && ($benefits['service_fee']['enabled']??0) ? translate('waived') : translate('inactive') }}</strong></div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6 mb-30">
                    <div class="card mstoo-notify-card">
                        <div class="card-header"><h4 class="mb-0">{{translate('transactions')}}</h4></div>
                        <div class="card-body">
                            @forelse($membership->transactions as $trx)
                                <div class="mstoo-stat-row">
                                    <span>{{optional($trx->created_at)->format('d M Y')}} · {{$trx->payment_gateway}}</span>
                                    <strong>{{with_currency_symbol($trx->amount)}} ({{$trx->payment_status}})</strong>
                                </div>
                            @empty
                                <p class="text-muted mb-0">{{translate('no_transactions_found')}}</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
