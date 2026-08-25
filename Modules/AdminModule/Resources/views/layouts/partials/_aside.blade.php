<aside class="aside">
    <div class="aside-header">
        <a href="{{route('admin.dashboard')}}" class="logo d-flex gap-2">
            <img src="{{asset('storage/app/public/business')}}/{{$aside_logo->live_values??""}}"
                 onerror="this.src='{{asset('assets/placeholder.png')}}'"
                 alt="MSTOO" class="main-logo">
            <img src="{{asset('storage/app/public/business')}}/{{$aside_logo->live_values??""}}"
                 onerror="this.src='{{asset('assets/placeholder.png')}}'"
                 alt="MSTOO" class="mobile-logo">
            <span class="brand-text d-none d-xl-inline">
                MSTOO
                <small>ADMIN</small>
            </span>
        </a>
        <button class="toggle-menu-button aside-toggle border-0 bg-transparent p-0 dark-color" type="button">
            <span class="material-icons">menu</span>
        </button>
    </div>

    <div class="aside-body" data-trigger="scrollbar">
        <div class="user-profile media gap-3 align-items-center">
            <div class="avatar">
                <img class="avatar-img rounded-circle"
                     src="{{asset('storage/app/public/user/profile_image')}}/{{ auth()->user()->profile_image }}"
                     onerror="this.src='{{asset('assets/admin-module')}}/img/media/upload-file.png'"
                     alt="">
            </div>
            <div class="media-body">
                <h5 class="card-title">{{ \Illuminate\Support\Str::limit(auth()->user()->first_name ?: auth()->user()->email, 16) }}</h5>
                <span class="card-text">{{ auth()->user()->user_type }}</span>
            </div>
        </div>

        <ul class="nav">
            <li class="nav-category">{{translate('main')}}</li>
            <li>
                <a href="{{route('admin.dashboard')}}" class="{{request()->is('admin/dashboard')?'active-menu':''}}">
                    <span class="material-icons" title="{{translate('dashboard')}}">dashboard</span>
                    <span class="link-title">{{translate('dashboard')}}</span>
                </a>
            </li>

            @if(access_checker('customer_management'))
                <li class="nav-category">{{translate('customer_management')}}</li>
                <li class="has-sub-item {{request()->is('admin/customer/list')||request()->is('admin/customer/create')||request()->is('admin/customer/detail*')||request()->is('admin/customer/edit*')?'sub-menu-opened':''}}">
                    <a href="#" class="{{request()->is('admin/customer/*')?'active-menu':''}}">
                        <span class="material-icons" title="Customers">person_outline</span>
                        <span class="link-title">{{translate('customers')}}</span>
                    </a>
                    <ul class="nav sub-menu">
                        <li>
                            <a href="{{route('admin.customer.index')}}"
                               class="{{request()->is('admin/customer/list')?'active-menu':''}}">
                                {{translate('customer_list')}}
                            </a>
                        </li>
                        @if(access_checker('customer_management', 'create'))
                            <li>
                                <a href="{{route('admin.customer.create')}}"
                                   class="{{request()->is('admin/customer/create')?'active-menu':''}}">
                                    {{translate('add_new_customer')}}
                                </a>
                            </li>
                        @endif
                    </ul>
                </li>
            @endif

            @if(access_checker('employee_management'))
                <li class="nav-category">{{translate('employee_management')}}</li>
                <li class="has-sub-item {{request()->is('admin/role*') || request()->is('admin/employee*') ? 'sub-menu-opened' : ''}}">
                    <a href="#" class="{{request()->is('admin/role*') || request()->is('admin/employee*') ? 'active-menu' : ''}}">
                        <span class="material-icons" title="{{translate('employee_management')}}">badge</span>
                        <span class="link-title">{{translate('employee_management')}}</span>
                    </a>
                    <ul class="nav sub-menu">
                        @if(access_checker('employee_management', 'manage_roles'))
                            <li>
                                <a href="{{route('admin.role.index')}}"
                                   class="{{request()->is('admin/role*') ? 'active-menu' : ''}}">
                                    {{translate('employee_role_setup')}}
                                </a>
                            </li>
                        @endif
                        <li>
                            <a href="{{route('admin.employee.index')}}"
                               class="{{request()->is('admin/employee/list') || request()->is('admin/employee/edit*') ? 'active-menu' : ''}}">
                                {{translate('employee_list')}}
                            </a>
                        </li>
                        @if(access_checker('employee_management', 'create'))
                            <li>
                                <a href="{{route('admin.employee.create')}}"
                                   class="{{request()->is('admin/employee/create') ? 'active-menu' : ''}}">
                                    {{translate('add_new_employee')}}
                                </a>
                            </li>
                        @endif
                    </ul>
                </li>
            @endif

            @if(access_checker('pro_member_management')
                || access_checker('pro_member_management', 'manage_benefits')
                || access_checker('pro_member_management', 'manage_plans')
                || access_checker('pro_member_management', 'manage_settings')
                || access_checker('pro_member_management', 'view_transactions'))
                <li class="nav-category">{{translate('pro_member_management')}}</li>
                <li class="has-sub-item {{request()->is('admin/pro-member*') ? 'sub-menu-opened' : ''}}">
                    <a href="#" class="{{request()->is('admin/pro-member*') ? 'active-menu' : ''}}">
                        <span class="material-icons" title="{{translate('pro_member_management')}}">workspace_premium</span>
                        <span class="link-title">{{translate('pro_member_management')}}</span>
                    </a>
                    <ul class="nav sub-menu">
                        @if(access_checker('pro_member_management', 'manage_benefits'))
                            <li>
                                <a href="{{route('admin.pro-member.benefits')}}" class="{{request()->is('admin/pro-member/benefits')?'active-menu':''}}">
                                    {{translate('pro_member_benefits_setup')}}
                                </a>
                            </li>
                        @endif
                        @if(access_checker('pro_member_management', 'manage_plans'))
                            <li>
                                <a href="{{route('admin.pro-member.plans.index')}}" class="{{request()->is('admin/pro-member/plans*')?'active-menu':''}}">
                                    {{translate('plan_setup')}}
                                </a>
                            </li>
                        @endif
                        <li>
                            <a href="{{route('admin.pro-member.members.index')}}" class="{{request()->is('admin/pro-member/members*')?'active-menu':''}}">
                                {{translate('pro_member_list')}}
                            </a>
                        </li>
                        @if(access_checker('pro_member_management', 'manage_settings'))
                            <li>
                                <a href="{{route('admin.pro-member.settings')}}" class="{{request()->is('admin/pro-member/settings')?'active-menu':''}}">
                                    {{translate('additional_setup')}}
                                </a>
                            </li>
                        @endif
                        @if(access_checker('pro_member_management', 'view_transactions'))
                            <li>
                                <a href="{{route('admin.pro-member.transactions')}}" class="{{request()->is('admin/pro-member/transactions')?'active-menu':''}}">
                                    {{translate('transactions')}}
                                </a>
                            </li>
                        @endif
                    </ul>
                </li>
            @endif

            @if(access_checker('provider_management'))
                <li class="nav-category">{{translate('provider_management')}}</li>
                <li>
                    <a href="{{route('admin.withdraw.request.list', ['status'=>'all'])}}"
                       class="{{request()->is('admin/withdraw/request*')?'active-menu':''}}">
                        <span class="material-icons" title="{{translate('withdraw_requests')}}">payments</span>
                        <span class="link-title">{{translate('withdraw_requests')}}</span>
                    </a>
                </li>
            @endif

            @if(access_checker('service_management'))
                <li class="nav-category">{{translate('service_management')}}</li>
                <li class="has-sub-item {{(request()->is('admin/category/*') || request()->is('admin/sub-category/*'))?'sub-menu-opened':''}}">
                    <a href="#" class="{{(request()->is('admin/category/*') || request()->is('admin/sub-category/*'))?'active-menu':''}}">
                        <span class="material-icons" title="Ad Categories">category</span>
                        <span class="link-title">{{translate('service_categories')}}</span>
                    </a>
                    <ul class="nav sub-menu">
                        <li>
                            <a href="{{route('admin.category.create')}}"
                               class="{{request()->is('admin/category/*')?'active-menu':''}}">
                                {{translate('category_setup')}}
                            </a>
                        </li>
                        <li>
                            <a href="{{route('admin.sub-category.create')}}"
                               class="{{request()->is('admin/sub-category/*')?'active-menu':''}}">
                                {{translate('sub_category_setup')}}
                            </a>
                        </li>
                    </ul>
                </li>
                <li class="has-sub-item {{request()->is('admin/service/*')?'sub-menu-opened':''}}">
                    <a href="#" class="{{request()->is('admin/service/*')?'active-menu':''}}">
                        <span class="material-icons" title="Ads">design_services</span>
                        <span class="link-title">{{translate('services')}}</span>
                    </a>
                    <ul class="nav flex-column sub-menu">
                        <li>
                            <a href="{{route('admin.service.index')}}"
                               class="{{request()->is('admin/service/list')?'active-menu':''}}">
                                {{translate('posted_ads')}}
                            </a>
                        </li>
                        <li>
                            <a href="{{route('admin.service.create')}}"
                               class="{{request()->is('admin/service/create')?'active-menu':''}}">
                                {{translate('add_new_service')}}
                            </a>
                        </li>
                    </ul>
                </li>
            @endif

            @if(access_checker('promotion_management'))
                <li class="nav-category">{{translate('promotion_management')}}</li>
                <li>
                    <a href="{{route('admin.banner.create')}}"
                       class="{{request()->is('admin/banner/*')?'active-menu':''}}">
                        <span class="material-icons" title="{{translate('promotional_banners')}}">flag</span>
                        <span class="link-title">{{translate('promotional_banners')}}</span>
                    </a>
                </li>
                <li class="nav-category">{{translate('notification_management')}}</li>
                <li class="has-sub-item {{request()->is('admin/push-notification/*') || request()->is('admin/notifications*') ? 'sub-menu-opened' : ''}}">
                    <a href="#" class="{{request()->is('admin/push-notification/*') || request()->is('admin/notifications*') ? 'active-menu' : ''}}">
                        <span class="material-icons" title="{{translate('notification_management')}}">notifications</span>
                        <span class="link-title">{{translate('notification_management')}}</span>
                    </a>
                    <ul class="nav sub-menu">
                        @if(access_checker('promotion_management', 'send'))
                            <li>
                                <a href="{{route('admin.push-notification.create')}}"
                                   class="{{request()->is('admin/push-notification/create') || request()->is('admin/notifications/create') ? 'active-menu' : ''}}">
                                    {{translate('send_notifications')}}
                                </a>
                            </li>
                        @endif
                        <li>
                            <a href="{{route('admin.push-notification.list')}}"
                               class="{{request()->is('admin/push-notification/list') || request()->is('admin/push-notification/show/*') || request()->is('admin/notifications') ? 'active-menu' : ''}}">
                                {{translate('notification_history')}}
                            </a>
                        </li>
                        <li>
                            <a href="{{route('admin.push-notification.settings')}}"
                               class="{{request()->is('admin/push-notification/settings') || request()->is('admin/notifications/settings') ? 'active-menu' : ''}}">
                                {{translate('push_notification')}}
                            </a>
                        </li>
                        <li>
                            <a href="{{route('admin.push-notification.channels')}}"
                               class="{{request()->is('admin/push-notification/channels') || request()->is('admin/notifications/channels') ? 'active-menu' : ''}}">
                                {{translate('notification_channel')}}
                            </a>
                        </li>
                    </ul>
                </li>
            @endif

            @if(access_checker('booking_management'))
                <li class="nav-category">{{translate('booking_management')}}</li>
                <li class="has-sub-item {{request()->is('admin/booking/*')?'sub-menu-opened':''}}">
                    <a href="#" class="{{request()->is('admin/booking/*')?'active-menu':''}}">
                        <span class="material-icons" title="Bookings">calendar_month</span>
                        <span class="link-title">{{translate('bookings')}}</span>
                    </a>
                    <ul class="nav sub-menu">
                        <li>
                            <a href="{{route('admin.booking.list', ['booking_status'=>'accepted'])}}"
                               class="{{request()->is('admin/booking/list') && request()->query('booking_status')=='accepted'?'active-menu':''}}">
                                <span class="link-title">Upcoming Bookings
                                    <span class="count">{{ $aside_accepted_bookings ?? 0 }}</span>
                                </span>
                            </a>
                        </li>
                        <li>
                            <a href="{{route('admin.booking.list', ['booking_status'=>'completed'])}}"
                               class="{{request()->is('admin/booking/list') && request()->query('booking_status')=='completed'?'active-menu':''}}">
                                <span class="link-title">{{translate('Completed')}}
                                    <span class="count">{{ $aside_completed_bookings ?? 0 }}</span>
                                </span>
                            </a>
                        </li>
                    </ul>
                </li>
            @endif

            @if(access_checker('blog_management'))
                <li class="nav-category">{{translate('blog_management')}}</li>
                <li class="has-sub-item {{request()->is('admin/blog*') ? 'sub-menu-opened' : ''}}">
                    <a href="#" class="{{request()->is('admin/blog*') ? 'active-menu' : ''}}">
                        <span class="material-icons" title="{{translate('blog_management')}}">article</span>
                        <span class="link-title">{{translate('blog_management')}}</span>
                    </a>
                    <ul class="nav sub-menu">
                        <li>
                            <a href="{{route('admin.blog.index')}}"
                               class="{{request()->is('admin/blog') || request()->is('admin/blog/show/*') || request()->is('admin/blog/preview/*') ? 'active-menu' : ''}}">
                                {{translate('blog')}}
                            </a>
                        </li>
                        @if(access_checker('blog_management', 'create'))
                            <li>
                                <a href="{{route('admin.blog.create')}}"
                                   class="{{request()->is('admin/blog/create') || request()->is('admin/blog/edit/*') ? 'active-menu' : ''}}">
                                    {{translate('create_blog')}}
                                </a>
                            </li>
                        @endif
                        <li>
                            <a href="{{route('admin.blog-category.index')}}"
                               class="{{request()->is('admin/blog-category*') ? 'active-menu' : ''}}">
                                {{translate('blog_categories')}}
                            </a>
                        </li>
                    </ul>
                </li>
            @endif

            @if(access_checker('system_management'))
                <li class="nav-category">{{translate('business_setup')}}</li>
                <li class="has-sub-item {{ request()->is('admin/business-settings*') || request()->is('admin/configuration*') ? 'sub-menu-opened' : '' }}">
                    <a href="#" class="{{ request()->is('admin/business-settings*') || request()->is('admin/configuration*') ? 'active-menu' : '' }}">
                        <span class="material-icons" title="{{translate('business_setup')}}">tune</span>
                        <span class="link-title">{{translate('business_settings')}}</span>
                    </a>
                    <ul class="nav sub-menu">
                        <li>
                            <a href="{{route('admin.business-settings.get-business-information')}}"
                               class="{{request()->is('admin/business-settings/get-business-information')?'active-menu':''}}">
                                {{translate('business_setup')}}
                            </a>
                        </li>
                        <li>
                            <a href="{{route('admin.business-settings.get-pages-setup')}}"
                               class="{{request()->is('admin/business-settings/get-pages-setup')?'active-menu':''}}">
                                {{translate('page_settings')}}
                            </a>
                        </li>
                        <li>
                            <a href="{{route('admin.business-settings.404-logs')}}"
                               class="{{request()->is('admin/business-settings/404-logs')?'active-menu':''}}">
                                {{translate('404_logs')}}
                            </a>
                        </li>
                        <li>
                            <a href="{{route('admin.business-settings.cron-jobs')}}"
                               class="{{request()->is('admin/business-settings/cron-jobs')?'active-menu':''}}">
                                {{translate('cron_job')}}
                            </a>
                        </li>
                    </ul>
                </li>
                <li class="nav-category">{{translate('system_setup')}}</li>
                <li class="has-sub-item {{ request()->is('admin/system-setup*') ? 'sub-menu-opened' : '' }}">
                    <a href="#" class="{{ request()->is('admin/system-setup*') ? 'active-menu' : '' }}">
                        <span class="material-icons" title="{{translate('system_setup')}}">settings</span>
                        <span class="link-title">{{translate('system_setup')}}</span>
                    </a>
                    <ul class="nav sub-menu">
                        <li>
                            <a href="{{route('admin.system-setup.login')}}"
                               class="{{request()->is('admin/system-setup/login')?'active-menu':''}}">
                                {{translate('login_setup')}}
                            </a>
                        </li>
                        <li>
                            <a href="{{route('admin.system-setup.language')}}"
                               class="{{request()->is('admin/system-setup/language')?'active-menu':''}}">
                                {{translate('language_setup')}}
                            </a>
                        </li>
                        <li>
                            <a href="{{route('admin.system-setup.gallery')}}"
                               class="{{request()->is('admin/system-setup/gallery*')?'active-menu':''}}">
                                {{translate('gallery')}}
                            </a>
                        </li>
                        <li>
                            <a href="{{route('admin.system-setup.backup')}}"
                               class="{{request()->is('admin/system-setup/backup*')?'active-menu':''}}">
                                {{translate('backup_database')}}
                            </a>
                        </li>
                    </ul>
                </li>
            @endif

            @if(access_checker('transaction_management') || access_checker('report_management'))
                <li class="nav-category">{{translate('transaction_reports_analytics')}}</li>
                @if(access_checker('transaction_management'))
                    <li>
                        <a href="{{route('admin.transaction.list')}}" class="{{request()->is('admin/transaction/*')?'active-menu':''}}">
                            <span class="material-icons" title="{{translate('all_transactions')}}">receipt_long</span>
                            <span class="link-title">{{translate('all_transactions')}}</span>
                        </a>
                    </li>
                @endif
                @if(can_view_report('transaction_report') || can_view_report('business_report') || can_view_report('booking_report') || can_view_report('provider_report'))
                    <li class="has-sub-item {{request()->is('admin/report/*')?'sub-menu-opened':''}}">
                        <a href="#" class="{{request()->is('admin/report/*')?'active-menu':''}}">
                            <span class="material-icons" title="{{translate('Reports')}}">event_note</span>
                            <span class="link-title">{{translate('Reports')}}</span>
                        </a>
                        <ul class="nav sub-menu">
                            @if(can_view_report('transaction_report'))
                                <li>
                                    <a href="{{route('admin.report.transaction')}}" class="{{request()->is('admin/report/transaction')?'active-menu':''}}">
                                        {{translate('transaction_reports')}}
                                    </a>
                                </li>
                            @endif
                            @if(can_view_report('business_report'))
                                <li>
                                    <a href="{{route('admin.report.business.overview')}}" class="{{request()->is('admin/report/business*')?'active-menu':''}}">
                                        {{translate('business_reports')}}
                                    </a>
                                </li>
                            @endif
                            @if(can_view_report('booking_report'))
                                <li>
                                    <a href="{{route('admin.report.booking')}}" class="{{request()->is('admin/report/booking')?'active-menu':''}}">
                                        {{translate('booking_reports')}}
                                    </a>
                                </li>
                            @endif
                            @if(can_view_report('provider_report'))
                                <li>
                                    <a href="{{route('admin.report.provider')}}" class="{{request()->is('admin/report/provider')?'active-menu':''}}">
                                        {{translate('provider_reports')}}
                                    </a>
                                </li>
                            @endif
                        </ul>
                    </li>
                @endif
                @if(can_view_report('keyword_analytics') || can_view_report('customer_analytics'))
                    <li class="has-sub-item {{request()->is('admin/analytics/*')?'sub-menu-opened':''}}">
                        <a href="#" class="{{request()->is('admin/analytics/*')?'active-menu':''}}">
                            <span class="material-icons" title="{{translate('Analytics')}}">analytics</span>
                            <span class="link-title">{{translate('Analytics')}}</span>
                        </a>
                        <ul class="nav sub-menu">
                            @if(can_view_report('keyword_analytics'))
                                <li>
                                    <a href="{{route('admin.analytics.search.keyword')}}" class="{{request()->is('admin/analytics/search/keyword')?'active-menu':''}}">
                                        {{translate('keyword_search')}}
                                    </a>
                                </li>
                            @endif
                            @if(can_view_report('customer_analytics'))
                                <li>
                                    <a href="{{route('admin.analytics.search.customer')}}" class="{{request()->is('admin/analytics/search/customer*')?'active-menu':''}}">
                                        {{translate('customer_search')}}
                                    </a>
                                </li>
                            @endif
                        </ul>
                    </li>
                @endif
            @endif
        </ul>
    </div>

    <div class="aside-footer">
        <button type="button" class="logout-btn" onclick="form_alert('admin-logout-form','{{translate('want_to_sign_out')}}?')">
            <span class="material-icons">logout</span>
            {{translate('Sign_Out')}}
        </button>
        <form action="{{route('admin.auth.logout')}}" method="GET" id="admin-logout-form" class="d-none"></form>
    </div>
</aside>
