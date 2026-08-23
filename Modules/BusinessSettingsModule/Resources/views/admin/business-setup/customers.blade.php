<div class="card mstoo-notify-card mb-3">
    <div class="card-header"><h4 class="mb-0">{{ translate('customer_settings') }}</h4></div>
    <div class="card-body">
        <div class="row g-3">
            @include('businesssettingsmodule::admin.business-setup._toggle', ['key' => 'customer_self_registration', 'type' => 'customer_config', 'label' => 'customer_self_registration', 'info' => 'allow_new_customers_to_register'])
            @include('businesssettingsmodule::admin.business-setup._toggle', ['key' => 'phone_verification', 'type' => 'service_setup', 'label' => 'phone_verification', 'info' => 'During registration & Login Customers have to verify via phone'])
            @include('businesssettingsmodule::admin.business-setup._toggle', ['key' => 'email_verification', 'type' => 'service_setup', 'label' => 'email_verification', 'info' => 'During registration & Login Customers have to verify via email'])
            @include('businesssettingsmodule::admin.business-setup._toggle', ['key' => 'customer_wallet', 'type' => 'customer_config', 'label' => 'customer_wallet'])
            @include('businesssettingsmodule::admin.business-setup._toggle', ['key' => 'customer_loyalty_point', 'type' => 'customer_config', 'label' => 'customer_loyalty_point'])
            @include('businesssettingsmodule::admin.business-setup._toggle', ['key' => 'customer_referral_earning', 'type' => 'customer_config', 'label' => 'customer_referral_earning'])
            @include('businesssettingsmodule::admin.business-setup._toggle', ['key' => 'phone_number_visibility_for_chatting', 'type' => 'business_information', 'label' => 'phone_number_visibility_for_chatting'])
        </div>
    </div>
</div>

<div class="card mstoo-notify-card mb-3">
    <div class="card-header"><h4 class="mb-0">{{ translate('loyalty_point') }}</h4></div>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.customer.settings', ['web_page' => 'loyalty_point']) }}" class="row g-3">
            @csrf
            @method('PUT')
            <input type="hidden" name="customer_loyalty_point" value="{{ setting_flag(setting_live($data_values, 'customer_loyalty_point', 0)) ? 1 : 0 }}">
            <div class="col-md-4">
                <label class="form-label">{{ translate('Percentage Of Loyalty Point per Booking Amount') }}</label>
                <input type="number" min="0" max="100" step="any" name="loyalty_point_percentage_per_booking" class="form-control" required value="{{ setting_live($data_values, 'loyalty_point_percentage_per_booking', 0) }}" {{ $can_edit ? '' : 'readonly' }}>
            </div>
            <div class="col-md-4">
                <label class="form-label">1 {{ currency_code() }} {{ translate('equal to how many loyalty points') }}</label>
                <input type="number" min="0" step="any" name="loyalty_point_value_per_currency_unit" class="form-control" required value="{{ setting_live($data_values, 'loyalty_point_value_per_currency_unit', 0) }}" {{ $can_edit ? '' : 'readonly' }}>
            </div>
            <div class="col-md-4">
                <label class="form-label">{{ translate('Minimum Loyalty Points To Transfer Into Wallet') }}</label>
                <input type="number" min="0" step="any" name="min_loyalty_point_to_transfer" class="form-control" required value="{{ setting_live($data_values, 'min_loyalty_point_to_transfer', 0) }}" {{ $can_edit ? '' : 'readonly' }}>
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

<div class="card mstoo-notify-card mb-3">
    <div class="card-header"><h4 class="mb-0">{{ translate('referral_earning') }}</h4></div>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.customer.settings', ['web_page' => 'referral_earning']) }}" class="row g-3">
            @csrf
            @method('PUT')
            <input type="hidden" name="customer_referral_earning" value="{{ setting_flag(setting_live($data_values, 'customer_referral_earning', 0)) ? 1 : 0 }}">
            <div class="col-md-6">
                <label class="form-label">{{ translate('referral_value_per_currency_unit') }}</label>
                <input type="number" min="0" step="any" name="referral_value_per_currency_unit" class="form-control" required value="{{ setting_live($data_values, 'referral_value_per_currency_unit', 0) }}" {{ $can_edit ? '' : 'readonly' }}>
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

<div class="card mstoo-notify-card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h4 class="mb-0">{{ translate('otp_and_login_setup') }}</h4>
        <a href="{{ route('admin.system-setup.login') }}" class="small">{{ translate('open_login_setup') }}</a>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.business-settings.set-otp-login-information') }}" class="row g-3">
            @csrf
            @method('PUT')
            <div class="col-md-4">
                <label class="form-label">{{ translate('Temporary Login Block Time') }} ({{ translate('In Second') }})</label>
                <input type="number" min="60" name="temporary_login_block_time" class="form-control" required value="{{ setting_live($data_values, 'temporary_login_block_time', 600) }}" {{ $can_edit ? '' : 'readonly' }}>
            </div>
            <div class="col-md-4">
                <label class="form-label">{{ translate('Maximum Login Hit') }}</label>
                <input type="number" min="3" name="maximum_login_hit" class="form-control" required value="{{ setting_live($data_values, 'maximum_login_hit', 5) }}" {{ $can_edit ? '' : 'readonly' }}>
            </div>
            <div class="col-md-4">
                <label class="form-label">{{ translate('Temporary OTP Block Time') }} ({{ translate('In Second') }})</label>
                <input type="number" min="60" name="temporary_otp_block_time" class="form-control" required value="{{ setting_live($data_values, 'temporary_otp_block_time', 600) }}" {{ $can_edit ? '' : 'readonly' }}>
            </div>
            <div class="col-md-4">
                <label class="form-label">{{ translate('Maximum OTP Hit') }}</label>
                <input type="number" min="3" name="maximum_otp_hit" class="form-control" required value="{{ setting_live($data_values, 'maximum_otp_hit', 5) }}" {{ $can_edit ? '' : 'readonly' }}>
            </div>
            <div class="col-md-4">
                <label class="form-label">{{ translate('OTP Resend Time') }} ({{ translate('In Second') }})</label>
                <input type="number" min="30" name="otp_resend_time" class="form-control" required value="{{ setting_live($data_values, 'otp_resend_time', 60) }}" {{ $can_edit ? '' : 'readonly' }}>
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
