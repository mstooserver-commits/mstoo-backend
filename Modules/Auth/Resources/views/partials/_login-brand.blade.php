@php
    $brandTitle = $brandTitle ?? 'MSTOO Admin';
    $brandLead = $brandLead ?? 'Manage your marketplace, users, ads, bookings, providers and platform operations from one powerful dashboard.';
    $showPoints = $showPoints ?? true;
@endphp
<section class="login-brand">
    <div class="login-brand-art" aria-hidden="true">
        <span class="login-orb login-orb-a"></span>
        <span class="login-orb login-orb-b"></span>
        <span class="login-orb login-orb-c"></span>
        <span class="login-ring"></span>
    </div>
    <div class="login-brand-inner">
        <div class="login-logo-plate">
            <img class="login-logo-img" src="{{ asset('assets/admin-module/img/mstoo-logo.png') }}" alt="MSTOO">
        </div>
        <p class="login-kicker">Admin console</p>
        <h1>{{ $brandTitle }}</h1>
        <p class="login-lead">{{ $brandLead }}</p>
        @if($showPoints)
            <ul class="login-points">
                <li><span class="material-icons">groups</span>Manage Users & Providers</li>
                <li><span class="material-icons">storefront</span>Manage Ads & Services</li>
                <li><span class="material-icons">event_available</span>Manage Bookings & Transactions</li>
                <li><span class="material-icons">campaign</span>Manage Promotions & Campaigns</li>
                <li><span class="material-icons">verified_user</span>Manage Documents & Verification</li>
                <li><span class="material-icons">tune</span>Configure Notifications & Platform Settings</li>
            </ul>
        @endif
    </div>
    <p class="login-brand-foot">Professional operations console for the MSTOO marketplace.</p>
</section>
