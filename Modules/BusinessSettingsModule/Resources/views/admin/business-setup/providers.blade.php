<div class="card mstoo-notify-card mb-3">
    <div class="card-header"><h4 class="mb-0">{{ translate('provider_settings') }}</h4></div>
    <div class="card-body">
        <div class="row g-3 mb-4">
            @include('businesssettingsmodule::admin.business-setup._toggle', ['key' => 'provider_self_registration', 'type' => 'service_setup', 'label' => 'provider_self_registration', 'info' => 'allow_providers_to_register_themselves'])
            @include('businesssettingsmodule::admin.business-setup._toggle', ['key' => 'direct_provider_booking', 'type' => 'business_information', 'label' => 'direct_provider_booking', 'info' => 'Customers can directly book any provider'])
        </div>
        <form method="POST" action="{{ route('admin.business-settings.set-provider-settings') }}" class="row g-3">
            @csrf
            @method('PUT')
            <div class="col-md-4">
                <label class="form-label">{{ translate('default_commission') }} (%)</label>
                <input type="number" min="0" max="100" step="any" name="default_commission" class="form-control" required value="{{ setting_live($data_values, 'default_commission', 0) }}" {{ $can_edit ? '' : 'readonly' }}>
            </div>
            <div class="col-md-4">
                <label class="form-label">{{ translate('minimum_withdraw_amount') }}</label>
                <input type="number" min="0" step="any" name="minimum_withdraw_amount" class="form-control" required value="{{ setting_live($data_values, 'minimum_withdraw_amount', 0) }}" {{ $can_edit ? '' : 'readonly' }}>
            </div>
            <div class="col-md-4">
                <label class="form-label">{{ translate('maximum_withdraw_amount') }}</label>
                <input type="number" min="0" step="any" name="maximum_withdraw_amount" class="form-control" required value="{{ setting_live($data_values, 'maximum_withdraw_amount', 0) }}" {{ $can_edit ? '' : 'readonly' }}>
            </div>
            @if($can_edit)
                <div class="col-12 d-flex justify-content-end gap-2">
                    <button type="reset" class="btn btn--secondary">{{ translate('reset') }}</button>
                    <button type="submit" class="btn btn--primary">{{ translate('save_information') }}</button>
                </div>
            @endif
        </form>
    </div>
</div>
