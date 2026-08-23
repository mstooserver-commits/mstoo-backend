@extends('adminmodule::layouts.master')

@section('title', translate('login_setup'))

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="page-title-wrap mb-3">
                <h2 class="page-title mb-1">{{ translate('system_setup') }}</h2>
                <p class="text-muted mb-0">{{ translate('configure_authentication_languages_media_and_backups') }}</p>
            </div>

            @include('businesssettingsmodule::admin.system-setup._nav')

            <form method="POST" action="{{ route('admin.system-setup.login.save') }}">
                @csrf
                @method('PUT')

                <div class="card mstoo-notify-card mb-3">
                    <div class="card-header"><h4 class="mb-0">{{ translate('authentication') }}</h4></div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="border rounded p-3 h-100">
                                    <div class="fw-semibold mb-2">{{ translate('admin_login') }}</div>
                                    <p class="text-muted small mb-2">{{ translate('admins_can_sign_in_with_email_or_phone_and_password') }}</p>
                                    <span class="badge bg-success">{{ translate('email') }}</span>
                                    <span class="badge bg-success">{{ translate('phone') }}</span>
                                    <span class="badge bg-success">{{ translate('password') }}</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="border rounded p-3 h-100">
                                    <div class="fw-semibold mb-2">{{ translate('customer_login') }}</div>
                                    <p class="text-muted small mb-2">{{ translate('customers_sign_in_with_phone_otp') }}</p>
                                    <span class="badge bg-success">{{ translate('phone') }}</span>
                                    <span class="badge bg-success">{{ translate('otp') }}</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ translate('phone_verification') }}</label>
                                <label class="switcher">
                                    <input class="switcher_input" type="checkbox" name="phone_verification" value="1" {{ setting_flag(setting_live($data_values, 'phone_verification', 0)) ? 'checked' : '' }} {{ $can_edit ? '' : 'disabled' }}>
                                    <span class="switcher_control"></span>
                                </label>
                                <small class="text-muted d-block">{{ translate('During registration & Login Customers have to verify via phone') }}</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ translate('email_verification') }}</label>
                                <label class="switcher">
                                    <input class="switcher_input" type="checkbox" name="email_verification" value="1" {{ setting_flag(setting_live($data_values, 'email_verification', 0)) ? 'checked' : '' }} {{ $can_edit ? '' : 'disabled' }}>
                                    <span class="switcher_control"></span>
                                </label>
                                <small class="text-muted d-block">{{ translate('During registration & Login Customers have to verify via email') }}</small>
                            </div>
                            <div class="col-md-6">
                                @php($method = old('forget_password_verification_method', setting_live($data_values, 'forget_password_verification_method', 'phone')))
                                <label class="form-label">{{ translate('password_reset_method') }}</label>
                                <select name="forget_password_verification_method" class="form-control" {{ $can_edit ? '' : 'disabled' }}>
                                    <option value="phone" {{ $method === 'phone' ? 'selected' : '' }}>{{ translate('phone') }}</option>
                                    <option value="email" {{ $method === 'email' ? 'selected' : '' }}>{{ translate('email') }}</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mstoo-notify-card mb-3">
                    <div class="card-header"><h4 class="mb-0">{{ translate('otp') }}</h4></div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">{{ translate('otp_length') }}</label>
                                <input type="text" class="form-control" value="4" readonly>
                                <small class="text-muted">{{ translate('mstoo_otp_is_fixed_at_four_digits') }}</small>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">{{ translate('otp_expiry') }} ({{ translate('seconds') }})</label>
                                <input type="number" min="60" max="3600" name="otp_expiry_time" class="form-control" required value="{{ old('otp_expiry_time', setting_live($data_values, 'otp_expiry_time', 300)) }}" {{ $can_edit ? '' : 'readonly' }}>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">{{ translate('otp_resend_time') }} ({{ translate('seconds') }})</label>
                                <input type="number" min="30" max="600" name="otp_resend_time" class="form-control" required value="{{ old('otp_resend_time', setting_live($data_values, 'otp_resend_time', 60)) }}" {{ $can_edit ? '' : 'readonly' }}>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">{{ translate('maximum_otp_hit') }}</label>
                                <input type="number" min="3" max="20" name="maximum_otp_hit" class="form-control" required value="{{ old('maximum_otp_hit', setting_live($data_values, 'maximum_otp_hit', 5)) }}" {{ $can_edit ? '' : 'readonly' }}>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">{{ translate('temporary_otp_block_time') }} ({{ translate('seconds') }})</label>
                                <input type="number" min="60" max="86400" name="temporary_otp_block_time" class="form-control" required value="{{ old('temporary_otp_block_time', setting_live($data_values, 'temporary_otp_block_time', 600)) }}" {{ $can_edit ? '' : 'readonly' }}>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mstoo-notify-card mb-3">
                    <div class="card-header"><h4 class="mb-0">{{ translate('password') }}</h4></div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">{{ translate('minimum_password_length') }}</label>
                                <input type="number" min="8" max="32" name="min_password_length" class="form-control" required value="{{ old('min_password_length', setting_live($data_values, 'min_password_length', 8)) }}" {{ $can_edit ? '' : 'readonly' }}>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">{{ translate('maximum_login_hit') }}</label>
                                <input type="number" min="3" max="20" name="maximum_login_hit" class="form-control" required value="{{ old('maximum_login_hit', setting_live($data_values, 'maximum_login_hit', 5)) }}" {{ $can_edit ? '' : 'readonly' }}>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">{{ translate('temporary_login_block_time') }} ({{ translate('seconds') }})</label>
                                <input type="number" min="60" max="86400" name="temporary_login_block_time" class="form-control" required value="{{ old('temporary_login_block_time', setting_live($data_values, 'temporary_login_block_time', 600)) }}" {{ $can_edit ? '' : 'readonly' }}>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mstoo-notify-card mb-3">
                    <div class="card-header"><h4 class="mb-0">{{ translate('login_page') }}</h4></div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">{{ translate('login_title') }}</label>
                                <input type="text" name="login_title" maxlength="80" class="form-control" value="{{ old('login_title', setting_live($data_values, 'login_title', '')) }}" {{ $can_edit ? '' : 'readonly' }}>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ translate('login_subtitle') }}</label>
                                <input type="text" name="login_subtitle" maxlength="160" class="form-control" value="{{ old('login_subtitle', setting_live($data_values, 'login_subtitle', '')) }}" {{ $can_edit ? '' : 'readonly' }}>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ translate('logo') }}</label>
                                <div class="text-muted small">{{ translate('managed_in_business_setup') }} —
                                    <a href="{{ route('admin.business-settings.get-business-information') }}">{{ translate('business_setup') }}</a>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ translate('security') }}</label>
                                <div class="d-flex flex-wrap gap-2">
                                    <span class="badge bg-secondary">{{ translate('csrf') }}</span>
                                    <span class="badge bg-secondary">{{ translate('rate_limiting') }}</span>
                                    <span class="badge bg-secondary">{{ translate('password_hashing') }}</span>
                                    <span class="badge {{ isset($recaptcha) && $recaptcha->is_active ? 'bg-success' : 'bg-light text-dark' }}">reCAPTCHA</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                @if($can_edit)
                    <div class="d-flex justify-content-end gap-2 mb-4">
                        <button type="reset" class="btn btn--secondary">{{ translate('reset') }}</button>
                        <button type="submit" class="btn btn--primary">{{ translate('save_information') }}</button>
                    </div>
                @endif
            </form>
        </div>
    </div>
@endsection
