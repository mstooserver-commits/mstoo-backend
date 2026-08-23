<div class="mb-3 overflow-auto">
    <ul class="nav nav--tabs nav--tabs__style2 flex-nowrap">
        @foreach([
            'business_info' => 'business_info',
            'payment' => 'payment',
            'bookings' => 'bookings',
            'providers' => 'providers',
            'customers' => 'customers',
            'servicemen' => 'servicemen',
            'promotions' => 'promotions',
            'business_plan' => 'business_plan',
        ] as $tab => $label)
            <li class="nav-item">
                <a href="{{ route('admin.business-settings.get-business-information', ['web_page' => $tab]) }}"
                   class="nav-link {{ $web_page === $tab ? 'active' : '' }}">
                    {{ translate($label) }}
                </a>
            </li>
        @endforeach
    </ul>
</div>
