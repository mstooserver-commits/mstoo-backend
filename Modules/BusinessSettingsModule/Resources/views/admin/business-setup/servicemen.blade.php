<div class="card mstoo-notify-card">
    <div class="card-header"><h4 class="mb-0">{{ translate('servicemen_settings') }}</h4></div>
    <div class="card-body">
        <div class="row g-3">
            @include('businesssettingsmodule::admin.business-setup._toggle', ['key' => 'service_man_can_cancel_booking', 'type' => 'service_setup', 'label' => 'service_man_can_cancel_booking', 'info' => 'servicemen_may_cancel_assigned_bookings'])
        </div>
        <p class="text-muted mt-3 mb-0">{{ translate('serviceman_app_versions_are_managed_in_app_settings') }}
            <a href="{{ route('admin.configuration.get-app-settings') }}">{{ translate('app_settings') }}</a>
        </p>
    </div>
</div>
