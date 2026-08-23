<div class="row g-3">
    <div class="col-lg-6">
        <div class="card mstoo-notify-card h-100">
            <div class="card-header"><h4 class="mb-0">{{ translate('pro_member_management') }}</h4></div>
            <div class="card-body">
                @if($pro_config)
                    <p class="mb-2"><strong>{{ translate('status') }}:</strong> {{ setting_flag($pro_config['enabled'] ?? 0) ? translate('active') : translate('not_active') }}</p>
                    <p class="mb-2"><strong>{{ translate('allow_customers_to_purchase_plans') }}:</strong> {{ setting_flag($pro_config['additional']['purchase_enabled'] ?? 0) ? translate('on') : translate('off') }}</p>
                    <p class="text-muted">{{ translate('plans_and_benefits_are_managed_in_pro_member_management') }}</p>
                    @if(access_checker('pro_member_management', 'manage_settings'))
                        <a class="btn btn--primary" href="{{ route('admin.pro-member.settings') }}">{{ translate('additional_setup') }}</a>
                    @endif
                    @if(access_checker('pro_member_management', 'manage_plans'))
                        <a class="btn btn--secondary" href="{{ route('admin.pro-member.plans.index') }}">{{ translate('plan_setup') }}</a>
                    @endif
                @else
                    <p class="text-muted mb-0">{{ translate('pro_member_management') }} {{ translate('not_available') }}</p>
                @endif
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card mstoo-notify-card h-100">
            <div class="card-header"><h4 class="mb-0">{{ translate('bidding_system') }}</h4></div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.business-settings.set-bidding-system') }}">
                    @csrf
                    @method('PUT')
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="switcher">
                                <input class="switcher_input" type="checkbox" name="bidding_status" value="1" {{ setting_flag(setting_live($data_values, 'bidding_status', 0)) ? 'checked' : '' }} {{ $can_edit ? '' : 'disabled' }}>
                                <span class="switcher_control"></span>
                            </label>
                            <span class="ms-2">{{ translate('Bidding Option') }}</span>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">{{ translate('Post Validation (days)') }}</label>
                            <input type="number" min="1" name="bidding_post_validity" class="form-control" required value="{{ setting_live($data_values, 'bidding_post_validity', 1) }}" {{ $can_edit ? '' : 'readonly' }}>
                        </div>
                        <div class="col-12">
                            <label class="switcher">
                                <input class="switcher_input" type="checkbox" name="bid_offers_visibility_for_providers" value="1" {{ setting_flag(setting_live($data_values, 'bid_offers_visibility_for_providers', 0)) ? 'checked' : '' }} {{ $can_edit ? '' : 'disabled' }}>
                                <span class="switcher_control"></span>
                            </label>
                            <span class="ms-2">{{ translate('See Other Provider Offers') }}</span>
                        </div>
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
    </div>
</div>
