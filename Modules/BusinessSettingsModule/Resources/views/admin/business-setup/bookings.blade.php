<div class="card mstoo-notify-card mb-3">
    <div class="card-header"><h4 class="mb-0">{{ translate('booking_settings') }}</h4></div>
    <div class="card-body">
        <div class="row g-3">
            @include('businesssettingsmodule::admin.business-setup._toggle', ['key' => 'provider_can_cancel_booking', 'type' => 'service_setup', 'label' => 'provider_can_cancel_booking', 'info' => 'providers_may_cancel_assigned_bookings'])
            @include('businesssettingsmodule::admin.business-setup._toggle', ['key' => 'service_man_can_cancel_booking', 'type' => 'service_setup', 'label' => 'service_man_can_cancel_booking', 'info' => 'servicemen_may_cancel_assigned_bookings'])
            @include('businesssettingsmodule::admin.business-setup._toggle', ['key' => 'customer_can_cancel_booking', 'type' => 'service_setup', 'label' => 'customer_can_cancel_booking', 'info' => 'customers_may_cancel_their_bookings'])
            @include('businesssettingsmodule::admin.business-setup._toggle', ['key' => 'cash_after_service', 'type' => 'service_setup', 'label' => 'cash_after_service', 'info' => 'Customer can pay with cash after receiving the service'])
            @include('businesssettingsmodule::admin.business-setup._toggle', ['key' => 'digital_payment', 'type' => 'service_setup', 'label' => 'digital_payment', 'info' => 'Customer can pay with digital payments'])
            @include('businesssettingsmodule::admin.business-setup._toggle', ['key' => 'wallet_payment', 'type' => 'service_setup', 'label' => 'wallet_payment', 'info' => 'Customer can pay with wallet balance'])
        </div>
    </div>
</div>
