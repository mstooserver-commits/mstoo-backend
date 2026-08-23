<div class="mb-3 overflow-auto">
    <ul class="nav nav--tabs nav--tabs__style2 flex-nowrap">
        <li class="nav-item">
            <a href="{{ route('admin.system-setup.login') }}" class="nav-link {{ request()->routeIs('admin.system-setup.login') ? 'active' : '' }}">
                {{ translate('login_setup') }}
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('admin.system-setup.language') }}" class="nav-link {{ request()->routeIs('admin.system-setup.language') ? 'active' : '' }}">
                {{ translate('language_setup') }}
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('admin.system-setup.gallery') }}" class="nav-link {{ request()->routeIs('admin.system-setup.gallery') ? 'active' : '' }}">
                {{ translate('gallery') }}
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('admin.system-setup.backup') }}" class="nav-link {{ request()->routeIs('admin.system-setup.backup*') ? 'active' : '' }}">
                {{ translate('backup_database') }}
            </a>
        </li>
    </ul>
</div>
