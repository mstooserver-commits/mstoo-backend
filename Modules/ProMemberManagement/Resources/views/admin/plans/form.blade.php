@php($isEdit = isset($plan) && $plan)
@extends('adminmodule::layouts.master')

@section('title', $isEdit ? translate('edit_plan') : translate('add_plan'))

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="page-title-wrap d-flex justify-content-between align-items-center mb-3">
                <h2 class="page-title">{{ $isEdit ? translate('edit_plan') : translate('add_plan') }}</h2>
                <a href="{{route('admin.pro-member.plans.index')}}" class="btn btn--secondary">{{translate('back')}}</a>
            </div>
            <form method="POST" action="{{ $isEdit ? route('admin.pro-member.plans.update',[$plan->id]) : route('admin.pro-member.plans.store') }}">
                @csrf
                @if($isEdit) @method('PUT') @endif
                <div class="card mstoo-notify-card mb-30">
                    <div class="card-header"><h4 class="mb-0">{{translate('plan_information')}}</h4></div>
                    <div class="card-body p-30">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-floating mb-30">
                                    <input class="form-control" name="name" value="{{old('name', $plan->name ?? '')}}" required>
                                    <label>{{translate('plan_name')}} *</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-floating mb-30">
                                    <select class="form-select" name="duration_unit">
                                        @foreach(['day'=>'Day','week'=>'Week','month'=>'Month','year'=>'Year'] as $unit=>$label)
                                            <option value="{{$unit}}" {{old('duration_unit', $plan->duration_unit ?? 'day')===$unit?'selected':''}}>{{translate($label)}}</option>
                                        @endforeach
                                    </select>
                                    <label>{{translate('duration_unit')}}</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-floating mb-30">
                                    <input type="number" min="1" class="form-control" name="duration_value" value="{{old('duration_value', $plan->duration_value ?? $plan->duration_days ?? 30)}}" required>
                                    <label>{{translate('duration')}} *</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-floating mb-30">
                                    <input type="number" min="0" class="form-control" name="trial_days" value="{{old('trial_days', $plan->trial_days ?? 0)}}">
                                    <label>{{translate('trial_days')}}</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating mb-30">
                                    <input type="number" step="0.01" min="0" class="form-control" name="price" value="{{old('price', $plan->price ?? 0)}}" required>
                                    <label>{{translate('price')}} ({{currency_symbol()}}) *</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating mb-30">
                                    <input type="number" step="0.01" min="0" class="form-control" name="discounted_price" value="{{old('discounted_price', $plan->discounted_price ?? '')}}">
                                    <label>{{translate('discounted_price')}} ({{currency_symbol()}})</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating mb-30">
                                    <input type="number" step="0.01" min="0" class="form-control" name="wallet_bonus" value="{{old('wallet_bonus', $plan->wallet_bonus ?? 0)}}">
                                    <label>{{translate('wallet_bonus')}} ({{currency_symbol()}})</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating mb-30">
                                    <input type="number" step="0.01" min="0" class="form-control" name="loyalty_multiplier" value="{{old('loyalty_multiplier', $plan->loyalty_multiplier ?? 1)}}">
                                    <label>{{translate('loyalty_multiplier')}}</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating mb-30">
                                    <input type="number" min="0" class="form-control" name="sort_order" value="{{old('sort_order', $plan->sort_order ?? 0)}}">
                                    <label>{{translate('sort_order')}}</label>
                                </div>
                            </div>
                            <div class="col-md-6 mb-30">
                                <label class="fw-medium mb-2 d-block">{{translate('status')}}</label>
                                <input type="hidden" name="is_active" value="0">
                                <label class="switcher">
                                    <input class="switcher_input" type="checkbox" name="is_active" value="1" {{old('is_active', $isEdit ? $plan->is_active : 1) ? 'checked' : ''}}>
                                    <span class="switcher_control"></span>
                                </label>
                            </div>
                            <div class="col-12">
                                <div class="form-floating mb-30">
                                    <textarea class="form-control" name="description" style="height:90px">{{old('description', $plan->description ?? '')}}</textarea>
                                    <label>{{translate('description')}}</label>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-floating mb-30">
                                    <textarea class="form-control" name="features_text" style="height:90px">{{old('features_text', isset($plan) && is_array($plan->features) ? implode("\n", $plan->features) : '')}}</textarea>
                                    <label>{{translate('features')}} ({{translate('one_per_line')}})</label>
                                </div>
                            </div>
                            @php($selected = old('benefits', $plan->benefits ?? ['discount','coupon','service_fee']))
                            <div class="col-12">
                                @foreach(['discount'=>'Discount','coupon'=>'Pro coupons','service_fee'=>'Free service fee','wallet_bonus'=>'Wallet bonus','loyalty'=>'Loyalty multiplier'] as $key=>$label)
                                    <label class="me-4">
                                        <input type="checkbox" name="benefits[]" value="{{$key}}" {{in_array($key, (array)$selected, true)?'checked':''}}>
                                        {{translate($key === 'service_fee' ? 'service_fee' : $key)}}
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
                <div class="d-flex justify-content-end gap-3">
                    <a href="{{route('admin.pro-member.plans.index')}}" class="btn btn--secondary">{{translate('cancel')}}</a>
                    <button class="btn btn--primary" type="submit">{{translate('save')}}</button>
                </div>
            </form>
        </div>
    </div>
@endsection
