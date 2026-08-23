<div class="card mstoo-notify-card mb-3">
    <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div>
            <h4 class="mb-1">{{ translate('system_maintenance') }}</h4>
            <p class="text-muted mb-0">{{ translate('temporarily_disable_customer_and_provider_access') }}</p>
            @if(mstoo_under_maintenance())
                <span class="badge bg-warning text-dark mt-2">{{ translate('currently_active') }}</span>
            @endif
        </div>
        <span class="badge {{ setting_flag($maintenance['status'] ?? 0) ? 'bg-danger' : 'bg-secondary' }}">
            {{ setting_flag($maintenance['status'] ?? 0) ? translate('on') : translate('off') }}
        </span>
    </div>
    <div class="card-body border-top">
        <form method="POST" action="{{ route('admin.business-settings.set-maintenance') }}" class="row g-3">
            @csrf
            @method('PUT')
            <div class="col-md-3">
                <label class="form-label d-flex justify-content-between">{{ translate('maintenance_mode') }}</label>
                <label class="switcher">
                    <input class="switcher_input" type="checkbox" name="status" value="1" {{ setting_flag($maintenance['status'] ?? 0) ? 'checked' : '' }} {{ $can_edit ? '' : 'disabled' }}>
                    <span class="switcher_control"></span>
                </label>
            </div>
            <div class="col-md-9">
                <label class="form-label">{{ translate('maintenance_message') }}</label>
                <input type="text" name="message" class="form-control" maxlength="500" required value="{{ old('message', $maintenance['message'] ?? '') }}" {{ $can_edit ? '' : 'disabled' }}>
            </div>
            <div class="col-md-6">
                <label class="form-label">{{ translate('start_date') }}</label>
                <input type="datetime-local" name="start_at" class="form-control" value="{{ old('start_at', !empty($maintenance['start_at']) ? \Carbon\Carbon::parse($maintenance['start_at'])->format('Y-m-d\TH:i') : '') }}" {{ $can_edit ? '' : 'disabled' }}>
            </div>
            <div class="col-md-6">
                <label class="form-label">{{ translate('end_date') }}</label>
                <input type="datetime-local" name="end_at" class="form-control" value="{{ old('end_at', !empty($maintenance['end_at']) ? \Carbon\Carbon::parse($maintenance['end_at'])->format('Y-m-d\TH:i') : '') }}" {{ $can_edit ? '' : 'disabled' }}>
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
    <div class="card-header"><h4 class="mb-0">{{ translate('basic_information') }}</h4></div>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.business-settings.set-business-information') }}" enctype="multipart/form-data" id="business-info-update-form">
            @csrf
            @method('PUT')
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">{{ translate('business_name') }} *</label>
                    <input type="text" name="business_name" class="form-control" required value="{{ old('business_name', setting_live($data_values, 'business_name', '')) }}" {{ $can_edit ? '' : 'readonly' }}>
                </div>
                <div class="col-md-6">
                    <label class="form-label">{{ translate('email') }} *</label>
                    <input type="email" name="business_email" class="form-control" required value="{{ old('business_email', setting_live($data_values, 'business_email', '')) }}" {{ $can_edit ? '' : 'readonly' }}>
                </div>
                <div class="col-md-6">
                    <label class="form-label">{{ translate('phone') }} *</label>
                    <input type="text" name="business_phone" class="form-control" required value="{{ old('business_phone', setting_live($data_values, 'business_phone', '')) }}" {{ $can_edit ? '' : 'readonly' }}>
                </div>
                <div class="col-md-6">
                    <label class="form-label">{{ translate('country') }} *</label>
                    @php($country_code = old('country_code', setting_live($data_values, 'country_code', '')))
                    <select class="form-control" name="country_code" required {{ $can_edit ? '' : 'disabled' }}>
                        @foreach(COUNTRIES as $country)
                            <option value="{{ $country['code'] }}" {{ $country_code == $country['code'] ? 'selected' : '' }}>{{ $country['name'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">{{ translate('address') }} *</label>
                    <textarea name="business_address" class="form-control" rows="3" required {{ $can_edit ? '' : 'readonly' }}>{{ old('business_address', setting_live($data_values, 'business_address', '')) }}</textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label">{{ translate('latitude') }}</label>
                    <input type="text" name="business_latitude" class="form-control" value="{{ old('business_latitude', setting_live($data_values, 'business_latitude', '')) }}" {{ $can_edit ? '' : 'readonly' }}>
                </div>
                <div class="col-md-6">
                    <label class="form-label">{{ translate('longitude') }}</label>
                    <input type="text" name="business_longitude" class="form-control" value="{{ old('business_longitude', setting_live($data_values, 'business_longitude', '')) }}" {{ $can_edit ? '' : 'readonly' }}>
                </div>
                <div class="col-md-6">
                    <label class="form-label">{{ translate('logo') }}</label>
                    <input type="file" name="business_logo" class="form-control" accept="image/*" {{ $can_edit ? '' : 'disabled' }}>
                    <img class="mt-2 rounded" style="max-height:64px" src="{{ asset('storage/app/public/business') }}/{{ setting_live($data_values, 'business_logo', '') }}" onerror="this.src='{{ asset('assets/placeholder.png') }}'" alt="">
                    @if($can_edit)
                        <label class="d-block mt-2"><input type="checkbox" name="remove_business_logo" value="1"> {{ translate('remove') }}</label>
                    @endif
                </div>
                <div class="col-md-6">
                    <label class="form-label">{{ translate('favicon') }}</label>
                    <input type="file" name="business_favicon" class="form-control" accept="image/*" {{ $can_edit ? '' : 'disabled' }}>
                    <img class="mt-2 rounded" style="max-height:48px" src="{{ asset('storage/app/public/business') }}/{{ setting_live($data_values, 'business_favicon', '') }}" onerror="this.src='{{ asset('assets/placeholder.png') }}'" alt="">
                    @if($can_edit)
                        <label class="d-block mt-2"><input type="checkbox" name="remove_business_favicon" value="1"> {{ translate('remove') }}</label>
                    @endif
                </div>
                <div class="col-md-6">
                    <label class="form-label">{{ translate('currency') }} *</label>
                    @php($currency_code = old('currency_code', setting_live($data_values, 'currency_code', 'INR')))
                    <select class="form-control" name="currency_code" required {{ $can_edit ? '' : 'disabled' }}>
                        @foreach(CURRENCIES as $currency)
                            <option value="{{ $currency['code'] }}" {{ $currency_code == $currency['code'] ? 'selected' : '' }}>{{ $currency['name'] }} ({{ $currency['symbol'] }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">{{ translate('symbol_position') }} *</label>
                    @php($position = old('currency_symbol_position', setting_live($data_values, 'currency_symbol_position', 'left')))
                    <select class="form-control" name="currency_symbol_position" {{ $can_edit ? '' : 'disabled' }}>
                        <option value="left" {{ $position == 'left' ? 'selected' : '' }}>{{ translate('left') }}</option>
                        <option value="right" {{ $position == 'right' ? 'selected' : '' }}>{{ translate('right') }}</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">{{ translate('decimal_point') }} *</label>
                    <input type="number" min="0" max="8" name="currency_decimal_point" class="form-control" required value="{{ old('currency_decimal_point', setting_live($data_values, 'currency_decimal_point', 2)) }}" {{ $can_edit ? '' : 'readonly' }}>
                </div>
                <div class="col-md-6">
                    <label class="form-label">{{ translate('timezone') }} *</label>
                    @php($time_zone = old('time_zone', setting_live($data_values, 'time_zone', 'Asia/Kolkata')))
                    <select class="form-control" name="time_zone" required {{ $can_edit ? '' : 'disabled' }}>
                        @foreach(TIME_ZONES as $time)
                            <option value="{{ $time['tzCode'] }}" {{ $time_zone == $time['tzCode'] ? 'selected' : '' }}>{{ $time['tzCode'] }} UTC {{ $time['utc'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">{{ translate('pagination_limit') }} *</label>
                    <input type="number" min="1" max="200" name="pagination_limit" class="form-control" required value="{{ old('pagination_limit', setting_live($data_values, 'pagination_limit', 10)) }}" {{ $can_edit ? '' : 'readonly' }}>
                </div>
                <div class="col-md-6">
                    <label class="form-label">{{ translate('default_commission') }} (%)</label>
                    <input type="number" min="0" max="100" step="any" name="default_commission" class="form-control" required value="{{ old('default_commission', setting_live($data_values, 'default_commission', 0)) }}" {{ $can_edit ? '' : 'readonly' }}>
                </div>
                <div class="col-md-3">
                    <label class="form-label">{{ translate('minimum_withdraw_amount') }}</label>
                    <input type="number" min="0" step="any" name="minimum_withdraw_amount" class="form-control" required value="{{ old('minimum_withdraw_amount', setting_live($data_values, 'minimum_withdraw_amount', 0)) }}" {{ $can_edit ? '' : 'readonly' }}>
                </div>
                <div class="col-md-3">
                    <label class="form-label">{{ translate('maximum_withdraw_amount') }}</label>
                    <input type="number" min="0" step="any" name="maximum_withdraw_amount" class="form-control" required value="{{ old('maximum_withdraw_amount', setting_live($data_values, 'maximum_withdraw_amount', 0)) }}" {{ $can_edit ? '' : 'readonly' }}>
                </div>
                <div class="col-12">
                    <label class="form-label">{{ translate('languages') }}</label>
                    @php($selectedLangs = old('language_code', setting_live($data_values, 'language_code', ['en'])))
                    @php($selectedLangs = is_array($selectedLangs) ? $selectedLangs : ['en'])
                    <div class="d-flex flex-wrap gap-3">
                        @foreach(LANGUAGES as $language)
                            <label class="d-flex align-items-center gap-1">
                                <input type="checkbox" name="language_code[]" value="{{ $language['code'] }}" {{ in_array($language['code'], $selectedLangs, true) ? 'checked' : '' }} {{ $can_edit ? '' : 'disabled' }}>
                                {{ $language['name'] }}
                            </label>
                        @endforeach
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">{{ translate('footer_text') }} *</label>
                    <input type="text" name="footer_text" class="form-control" required value="{{ old('footer_text', setting_live($data_values, 'footer_text', '')) }}" {{ $can_edit ? '' : 'readonly' }}>
                </div>
                <div class="col-md-6">
                    <label class="form-label">{{ translate('cookies_text') }}</label>
                    <input type="text" name="cookies_text" class="form-control" value="{{ old('cookies_text', setting_live($data_values, 'cookies_text', '')) }}" {{ $can_edit ? '' : 'readonly' }}>
                </div>
                <div class="col-md-6">
                    <label class="form-label">{{ translate('Forgot Password Verification Method') }}</label>
                    @php($method = old('forget_password_verification_method', setting_live($data_values, 'forget_password_verification_method', 'phone')))
                    <select class="form-control" name="forget_password_verification_method" {{ $can_edit ? '' : 'disabled' }}>
                        <option value="phone" {{ $method == 'phone' ? 'selected' : '' }}>{{ translate('phone') }}</option>
                        <option value="email" {{ $method == 'email' ? 'selected' : '' }}>{{ translate('email') }}</option>
                    </select>
                </div>
                <input type="hidden" name="phone_number_visibility_for_chatting" value="{{ setting_flag(setting_live($data_values, 'phone_number_visibility_for_chatting', 0)) ? 1 : 0 }}">
                <input type="hidden" name="direct_provider_booking" value="{{ setting_flag(setting_live($data_values, 'direct_provider_booking', 0)) ? 1 : 0 }}">
                @if($can_edit)
                    <div class="col-12 d-flex justify-content-end gap-2">
                        <button type="reset" class="btn btn--secondary">{{ translate('reset') }}</button>
                        <button type="submit" class="btn btn--primary">{{ translate('save_information') }}</button>
                    </div>
                @endif
            </div>
        </form>
    </div>
</div>

<div class="card mstoo-notify-card">
    <div class="card-header"><h4 class="mb-0">{{ translate('danger_zone') }}</h4></div>
    <div class="card-body">
        <p class="text-muted">{{ translate('clear_application_caches_without_changing_data') }}</p>
        @if($can_edit)
            <form method="POST" action="{{ route('admin.business-settings.clear-cache') }}" class="d-flex flex-wrap gap-2" onsubmit="return confirm('{{ translate('are_you_sure') }}?');">
                @csrf
                <input type="hidden" name="target" value="optimize">
                <button class="btn btn--secondary" type="submit">{{ translate('clear_cache') }}</button>
            </form>
        @endif
    </div>
</div>
