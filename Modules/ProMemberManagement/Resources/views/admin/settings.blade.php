@extends('adminmodule::layouts.master')

@section('title', translate('pro_member_additional_setup'))

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="page-title-wrap mb-3">
                <h2 class="page-title mb-1">{{translate('additional_setup')}}</h2>
                <p class="text-muted mb-0">{{translate('enable_pro_membership_and_configure_checkout_service_fee')}}</p>
            </div>

            <form method="POST" action="{{route('admin.pro-member.settings.store')}}">
                @csrf
                <div class="card mstoo-notify-card mb-30">
                    <div class="card-body p-30">
                        <div class="row g-4">
                            <div class="col-md-6">
                                <label class="fw-medium mb-2 d-block">{{translate('pro_membership_enabled')}}</label>
                                <input type="hidden" name="enabled" value="0">
                                <label class="switcher">
                                    <input class="switcher_input" type="checkbox" name="enabled" value="1" {{($config['enabled']??0)?'checked':''}}>
                                    <span class="switcher_control"></span>
                                </label>
                            </div>
                            <div class="col-md-6">
                                <label class="fw-medium mb-2 d-block">{{translate('allow_customers_to_purchase_plans')}}</label>
                                <input type="hidden" name="purchase_enabled" value="0">
                                <label class="switcher">
                                    <input class="switcher_input" type="checkbox" name="purchase_enabled" value="1" {{($config['additional']['purchase_enabled']??0)?'checked':''}}>
                                    <span class="switcher_control"></span>
                                </label>
                            </div>
                            <div class="col-md-6">
                                <label class="fw-medium mb-2">{{translate('default_service_fee')}} ({{currency_symbol()}})</label>
                                <input type="number" step="0.01" min="0" class="form-control" name="default_service_fee" value="{{old('default_service_fee', $config['additional']['default_service_fee'])}}" required>
                                <small class="text-muted">{{translate('charged_on_bookings_for_normal_customers._waived_for_pro_members_when_the_service_fee_benefit_is_on.')}}</small>
                            </div>
                            <div class="col-md-6">
                                <label class="fw-medium mb-2">{{translate('expiry_reminder_days')}}</label>
                                <input type="number" min="1" max="30" class="form-control" name="reminder_days" value="{{old('reminder_days', $config['additional']['reminder_days'])}}" required>
                            </div>
                            <div class="col-12">
                                <div class="alert alert-light border mb-0">
                                    <strong>{{translate('discount_stacking')}}:</strong>
                                    {{translate('pro_automatic_discount_is_not_applied_when_a_coupon_is_already_on_the_cart._existing_service_and_campaign_discounts_still_use_the_greater_of_the_two_rule.')}}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="d-flex justify-content-end gap-3">
                    <button class="btn btn--primary" type="submit">{{translate('save_information')}}</button>
                </div>
            </form>
        </div>
    </div>
@endsection
