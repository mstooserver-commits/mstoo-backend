<!DOCTYPE html>
<html lang="en">
<head>
    <title>{{translate('admin_login')}}</title>
    <meta http-equiv="X-UA-Compatible" content="IE=edge"/>
    <meta http-equiv="content-type" content="text/html; charset=utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="shortcut icon"
          href="{{asset('storage/app/public/business')}}/{{(business_config('business_favicon', 'business_information'))->live_values ?? null}}"/>
    <link rel="preconnect" href="https://fonts.googleapis.com"/>
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin/>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet"/>
    <link href="{{asset('assets/admin-module')}}/css/material-icons.css" rel="stylesheet"/>
    <link rel="stylesheet" href="{{asset('assets/admin-module')}}/css/bootstrap.min.css"/>
    <link rel="stylesheet" href="{{asset('assets/admin-module')}}/css/style.css"/>
    <link rel="stylesheet" href="{{asset('assets/admin-module')}}/css/mstoo-admin.css"/>
    <link rel="stylesheet" href="{{asset('assets/admin-module')}}/css/toastr.css">
</head>
<body class="login-page">
@php
    $loginTitle = business_live('login_title', 'business_information', '');
    $loginSubtitle = business_live('login_subtitle', 'business_information', '');
    $loginLogo = business_config('business_logo', 'business_information');
    $logoSrc = ($loginLogo && $loginLogo->live_values)
        ? asset('storage/app/public/business/' . $loginLogo->live_values)
        : asset('assets/admin-module/img/mstoo-logo.png');
@endphp
<div class="login-stage">
    <section class="login-brand">
        <div class="login-brand-inner">
            <img class="login-logo-img" src="{{ $logoSrc }}" alt="MSTOO"
                 onerror="this.src='{{ asset('assets/admin-module/img/mstoo-logo.png') }}'">
            <p class="login-kicker">Admin console</p>
            <h1>{{ $loginTitle ?: 'MSTOO Admin' }}</h1>
            <p class="login-lead">{{ $loginSubtitle ?: translate('welcome_to_admin_panel') }}</p>
            <ul class="login-points">
                <li>Manage customers, bookings, and providers</li>
                <li>Wallet, loyalty, and subscription tools</li>
                <li>Promotions, reports, and operations</li>
            </ul>
        </div>
        <p class="login-brand-foot">Professional CRM for MSTOO operations.</p>
    </section>
    <section class="login-panel">
        <div class="login-card">
            <form action="{{route('admin.auth.login')}}" method="POST" id="login-form">
                @csrf
                <h2>{{translate('sign_in')}}</h2>
                <p class="login-card-copy">{{translate('enter_your_credentials_to_continue')}}</p>
                <div class="mb-30">
                    <div class="form-floating">
                        <input type="text" name="email_or_phone" class="form-control"
                               placeholder="{{translate('email')}}" required id="email"
                               value="{{ old('email_or_phone') }}" autocomplete="username">
                        <label>{{translate('email')}}</label>
                    </div>
                </div>
                <div class="mb-3">
                    <div class="form-floating">
                        <input type="password" name="password" class="form-control"
                               placeholder="{{translate('password')}}" required id="password"
                               autocomplete="current-password">
                        <label>{{translate('password')}}</label>
                        <span class="material-icons togglePassword">visibility_off</span>
                    </div>
                </div>

                @php($recaptcha = business_config('recaptcha', 'third_party'))
                @if(isset($recaptcha) && $recaptcha->is_active)
                    <div class="recaptcha d-flex justify-content-center mb-4">
                        <div id="recaptcha_element" class="w-100" data-type="image"></div>
                    </div>
                @endif

                <button class="btn btn--primary w-100 text-uppercase" type="submit">
                    {{translate('sign_in')}}
                </button>
            </form>
        </div>
    </section>
</div>

<script src="{{asset('assets/admin-module')}}/js/jquery-3.6.0.min.js"></script>
<script src="{{asset('assets/admin-module')}}/js/bootstrap.bundle.min.js"></script>
<script src="{{asset('assets/admin-module')}}/js/main.js"></script>
<script src="{{asset('assets/admin-module')}}/js/sweet_alert.js"></script>
<script src="{{asset('assets/admin-module')}}/js/toastr.js"></script>
{!! Toastr::message() !!}

@if(isset($recaptcha) && $recaptcha->is_active)
    <script src="https://www.google.com/recaptcha/api.js?onload=onloadCallback&render=explicit" async defer></script>
    <script>
        var onloadCallback = function () {
            grecaptcha.render('recaptcha_element', {
                'sitekey': '{{$recaptcha->live_values['site_key']}}'
            });
        };
        $("#login-form").on('submit', function (e) {
            if (grecaptcha.getResponse().length === 0) {
                e.preventDefault();
                toastr.error("{{translate('please_check_the_recaptcha')}}");
            }
        });
    </script>
@endif

@if ($errors->any())
    <script>
        @foreach($errors->all() as $error)
        toastr.error(@json($error), 'Error', {
            CloseButton: true,
            ProgressBar: true
        });
        @endforeach
    </script>
@endif
</body>
</html>
