<!DOCTYPE html>
<html lang="en">
<head>
    <title>{{translate('forgot_password')}}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="preconnect" href="https://fonts.googleapis.com"/>
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin/>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet"/>
    <link href="{{asset('assets/admin-module')}}/css/material-icons.css" rel="stylesheet"/>
    <link rel="stylesheet" href="{{asset('assets/admin-module')}}/css/bootstrap.min.css"/>
    <link rel="stylesheet" href="{{asset('assets/admin-module')}}/css/style.css"/>
    <link rel="stylesheet" href="{{asset('assets/admin-module')}}/css/mstoo-admin.css?v=20260829"/>
    <link rel="stylesheet" href="{{asset('assets/admin-module')}}/css/toastr.css">
</head>
<body class="login-page">
<div class="login-stage">
    @include('auth::partials._login-brand', [
        'brandTitle' => 'Reset access',
        'brandLead' => 'Enter your admin email or phone. We will send an OTP to verify you.',
        'showPoints' => false,
    ])
    <section class="login-panel">
        <div class="login-card">
            <form action="{{ route('admin.auth.forgot-password.send') }}" method="post">
                @csrf
                <p class="login-card-badge">Account recovery</p>
                <h2>{{translate('forgot_password')}}</h2>
                <p class="login-card-copy">We will send a one-time code to verify it is you.</p>
                <label class="login-label" for="identity">{{translate('email_or_phone')}}</label>
                <div class="login-control mb-4">
                    <span class="material-icons">person_outline</span>
                    <input type="text" name="identity" id="identity" class="form-control" required
                           value="{{ old('identity') }}" placeholder="Email or phone">
                </div>
                <button class="btn btn--primary w-100 login-submit mb-3" type="submit">{{translate('send_otp')}}</button>
                <a class="login-back" href="{{ route('admin.auth.login') }}">{{translate('back_to_login')}}</a>
            </form>
        </div>
    </section>
</div>
<script src="{{asset('assets/admin-module')}}/js/jquery-3.6.0.min.js"></script>
<script src="{{asset('assets/admin-module')}}/js/toastr.js"></script>
{!! Toastr::message() !!}
</body>
</html>
