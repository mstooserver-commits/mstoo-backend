@php
    $headerNotifications = $header_notifications ?? collect();
@endphp
<header class="header fixed-top">
    <div class="container-fluid">
        <div class="row align-items-center justify-content-between">
            <div class="col-auto">
                <div class="header-toogle-menu d-flex align-items-center gap-2">
                    <button class="toggle-menu-button aside-toggle border-0 bg-transparent p-0 dark-color" type="button" aria-label="{{translate('toggle_menu')}}">
                        <span class="material-icons">menu</span>
                    </button>
                </div>
            </div>
            <div class="col">
                <div class="header-right">
                    <ul class="nav justify-content-end align-items-center gap-2 gap-md-3">
                        <li class="flex-grow-1 flex-md-grow-0">
                            <button class="toggle-search-btn px-0 d-sm-none" type="button" aria-label="{{translate('search')}}">
                                <span class="material-icons">search</span>
                            </button>
                            <form action="#" class="search-form" autocomplete="off" role="search">
                                <div class="input-group position-relative search-form__input_group">
                                    <span class="search-form__icon">
                                        <span class="material-icons">search</span>
                                    </span>
                                    <input type="search" class="theme-input-style search-form__input"
                                           id="search-form__input"
                                           placeholder="{{translate('search_customers_bookings_transactions')}}"
                                           aria-label="{{translate('search')}}"/>
                                    <div class="dropdown-menu rounded header-dropdown">
                                        <div class="show-search-result">
                                            @foreach(get_routes('admin') as $route)
                                                <a href="{{url('/')}}/{{$route}}" class="dropdown-item-text title-color hover-color-c2 text-capitalize">
                                                    {{str_replace('admin','',implode(' ',explode('/',$route)))}}
                                                </a>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </li>
                        <li>
                            <div class="dropdown">
                                <a href="#" class="header-icon count-btn" data-bs-toggle="dropdown" aria-expanded="false" aria-label="{{translate('notifications')}}">
                                    <span class="material-icons">notifications</span>
                                    @if(($headerNotifications->count() ?? 0) > 0)
                                        <span class="count">{{ $headerNotifications->count() }}</span>
                                    @endif
                                </a>
                                <div class="dropdown-menu dropdown-menu-end header-dropdown">
                                    <div class="px-3 py-2 d-flex justify-content-between align-items-center">
                                        <strong>{{translate('notifications')}}</strong>
                                        @if(access_checker('promotion_management'))
                                            <a href="{{route('admin.push-notification.list')}}" class="small">{{translate('view_all')}}</a>
                                        @endif
                                    </div>
                                    @forelse($headerNotifications as $notice)
                                        <a class="header-notify-item" href="{{ access_checker('promotion_management') ? route('admin.push-notification.list') : '#' }}">
                                            <strong>{{ \Illuminate\Support\Str::limit($notice->title, 42) }}</strong>
                                            <span>{{ optional($notice->created_at)->diffForHumans() }}</span>
                                        </a>
                                    @empty
                                        <div class="px-3 py-3 text-muted small">{{translate('no_notifications_found')}}</div>
                                    @endforelse
                                </div>
                            </div>
                        </li>
                        <li>
                            <div class="messages">
                                <a href="{{route('admin.chat.index')}}" class="header-icon count-btn" aria-label="{{translate('messages')}}">
                                    <span class="material-icons">sms</span>
                                    <span class="count" id="message_count">0</span>
                                </a>
                            </div>
                        </li>
                        <li>
                            <div class="user mt-n1 dropdown">
                                <a href="#" class="header-profile" data-bs-toggle="dropdown" aria-expanded="false">
                                    <img
                                         src="{{asset('storage/app/public/user/profile_image')}}/{{ auth()->user()->profile_image }}"
                                         onerror="this.src='{{asset('assets/admin-module')}}/img/user2x.png'"
                                         class="rounded-circle" alt="{{ auth()->user()->first_name }}">
                                    <span class="meta d-none d-md-block">
                                        <strong>{{ Str::limit(trim((auth()->user()->first_name ?? '').' '.(auth()->user()->last_name ?? '')) ?: auth()->user()->email, 18) }}</strong>
                                        <span>{{ Str::limit(str_replace('-', ' ', auth()->user()->user_type), 18) }}</span>
                                    </span>
                                    <span class="material-icons d-none d-md-inline">expand_more</span>
                                </a>
                                <div class="dropdown-menu dropdown-menu-end header-dropdown">
                                    <a href="{{route('admin.profile_update')}}"
                                       class="dropdown-item-text media gap-3 align-items-center">
                                        <div class="avatar">
                                            <img class="avatar-img rounded-circle" width="44" height="44"
                                                 src="{{asset('storage/app/public/user/profile_image')}}/{{ auth()->user()->profile_image }}"
                                                 onerror="this.src='{{asset('assets/admin-module')}}/img/user2x.png'"
                                                 alt="">
                                        </div>
                                        <div class="media-body">
                                            <h5 class="card-title mb-0">{{ Str::limit(auth()->user()->first_name, 20) }}</h5>
                                            <span class="card-text">{{ Str::limit(auth()->user()->email, 22) }}</span>
                                        </div>
                                    </a>
                                    <a class="dropdown-item" href="{{route('admin.profile_update')}}">{{translate('profile')}}</a>
                                    @if(access_checker('system_management'))
                                        <a class="dropdown-item" href="{{route('admin.business-settings.get-business-information')}}">{{translate('Settings')}}</a>
                                    @endif
                                    <button type="button" class="dropdown-item" onclick="form_alert('admin-logout-form','{{translate('want_to_sign_out')}}?')">
                                        {{translate('Sign_Out')}}
                                    </button>
                                </div>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</header>
