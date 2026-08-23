@extends('adminmodule::layouts.master')

@section('title', translate('pro_member_benefits_setup'))

@section('content')
    @php($b = $config['benefits'])
    <div class="main-content">
        <div class="container-fluid">
            <div class="page-title-wrap mb-3">
                <h2 class="page-title mb-1">{{translate('pro_member_benefits_setup')}}</h2>
                <p class="text-muted mb-0">{{translate('configure_discounts_coupons_and_service_fee_benefits_for_active_pro_members')}}</p>
            </div>

            <form method="POST" action="{{route('admin.pro-member.benefits.store')}}">
                @csrf
                <div class="card mstoo-notify-card mb-30">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div>
                            <h4 class="mb-1">{{translate('discount')}}</h4>
                            <div class="text-muted small">{{translate('pro_members_receive_a_discount_on_eligible_bookings._not_applied_when_a_coupon_is_already_used.')}}</div>
                        </div>
                        <input type="hidden" name="discount_enabled" value="0">
                        <label class="switcher mb-0">
                            <input class="switcher_input" type="checkbox" name="discount_enabled" value="1" {{($b['discount']['enabled']??0)?'checked':''}}>
                            <span class="switcher_control"></span>
                        </label>
                    </div>
                    <div class="card-body p-30">
                        <div class="row">
                            <div class="col-md-4">
                                <label class="fw-medium mb-2">{{translate('discount_percentage')}}</label>
                                <input type="number" step="0.01" min="0" max="100" class="form-control" name="discount_percent" value="{{old('discount_percent', $b['discount']['percent'])}}" required>
                            </div>
                            <div class="col-md-4">
                                <label class="fw-medium mb-2">{{translate('maximum_discount_amount')}} ({{currency_symbol()}})</label>
                                <input type="number" step="0.01" min="0" class="form-control" name="discount_max_amount" value="{{old('discount_max_amount', $b['discount']['max_amount'])}}" required>
                            </div>
                            <div class="col-md-4">
                                <label class="fw-medium mb-2">{{translate('minimum_order_amount')}} ({{currency_symbol()}})</label>
                                <input type="number" step="0.01" min="0" class="form-control" name="discount_min_order" value="{{old('discount_min_order', $b['discount']['min_order'])}}" required>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mstoo-notify-card mb-30">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div>
                            <h4 class="mb-1">{{translate('coupon')}}</h4>
                            <div class="text-muted small">{{translate('allow_special_coupons_with_type_pro_member._managed_in_existing_coupon_setup.')}}</div>
                        </div>
                        <input type="hidden" name="coupon_enabled" value="0">
                        <label class="switcher mb-0">
                            <input class="switcher_input" type="checkbox" name="coupon_enabled" value="1" {{($b['coupon']['enabled']??0)?'checked':''}}>
                            <span class="switcher_control"></span>
                        </label>
                    </div>
                </div>

                <div class="card mstoo-notify-card mb-30">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div>
                            <h4 class="mb-1">{{translate('service_fee')}}</h4>
                            <div class="text-muted small">{{translate('when_enabled_active_pro_members_do_not_pay_the_configured_default_service_fee.')}}</div>
                        </div>
                        <input type="hidden" name="service_fee_enabled" value="0">
                        <label class="switcher mb-0">
                            <input class="switcher_input" type="checkbox" name="service_fee_enabled" value="1" {{($b['service_fee']['enabled']??0)?'checked':''}}>
                            <span class="switcher_control"></span>
                        </label>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-3 mb-4">
                    <a href="{{route('admin.pro-member.benefits')}}" class="btn btn--secondary">{{translate('reset')}}</a>
                    <button class="btn btn--primary" type="submit">{{translate('save_information')}}</button>
                </div>
            </form>
        </div>
    </div>
@endsection
