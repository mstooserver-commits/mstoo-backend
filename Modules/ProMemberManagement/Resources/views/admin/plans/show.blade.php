@extends('adminmodule::layouts.master')

@section('title', $plan->name)

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="page-title-wrap d-flex justify-content-between mb-3">
                <h2 class="page-title">{{$plan->name}}</h2>
                <a href="{{route('admin.pro-member.plans.edit',[$plan->id])}}" class="btn btn--primary">{{translate('edit')}}</a>
            </div>
            <div class="card mstoo-notify-card">
                <div class="card-body p-30">
                    <div class="mstoo-stat-row"><span>{{translate('price')}}</span><strong>{{with_currency_symbol($plan->payablePrice())}}</strong></div>
                    <div class="mstoo-stat-row"><span>{{translate('duration')}}</span><strong>{{$plan->duration_days}} {{translate('days')}}</strong></div>
                    <div class="mstoo-stat-row"><span>{{translate('active_members')}}</span><strong>{{$plan->active_members_count}}</strong></div>
                    <div class="mstoo-stat-row"><span>{{translate('total_memberships')}}</span><strong>{{$plan->memberships_count}}</strong></div>
                    <div class="mstoo-stat-row"><span>{{translate('status')}}</span><strong>{{$plan->is_active ? translate('active') : translate('inactive')}}</strong></div>
                    <p class="mt-3 mb-0">{{$plan->description}}</p>
                </div>
            </div>
        </div>
    </div>
@endsection
