<!DOCTYPE html>
<html lang="en">
<head>
    <title>{{translate('reset_password')}}</title>
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
        'brandTitle' => 'Verify OTP',
        'brandLead' => 'Enter the OTP and choose a new password.',
        'showPoints' => false,
    ])
    <section class="login-panel">
        <div class="login-card">
            <form action="{{ route('admin.auth.forgot-password.reset') }}" method="post">
                @csrf
                <p class="login-card-badge">Set a new password</p>
                <h2>{{translate('reset_password')}}</h2>
                <p class="login-card-copy">Use the OTP sent to your email or phone.</p>
                <input type="hidden" name="identity" value="{{ $identity }}">
                <label class="login-label" for="otp">{{translate('otp')}}</label>
                <div class="login-control mb-3">
                    <span class="material-icons">pin</span>
                    <input type="text" name="otp" id="otp" class="form-control" required maxlength="6" inputmode="numeric">
                </div>
                <label class="login-label" for="password">{{translate('new_password')}}</label>
                <div class="login-control mb-3">
                    <span class="material-icons">lock_outline</span>
                    <input type="password" name="password" id="password" class="form-control" required minlength="8">
                    <span class="material-icons togglePassword" role="button" tabindex="0">visibility_off</span>
                </div>
                <label class="login-label" for="password_confirmation">{{translate('confirm_password')}}</label>
                <div class="login-control mb-4">
                    <span class="material-icons">lock_outline</span>
                    <input type="password" name="password_confirmation" id="password_confirmation" class="form-control" required minlength="8">
                    <span class="material-icons togglePassword" role="button" tabindex="0">visibility_off</span>
                </div>
                <button class="btn btn--primary w-100 login-submit" type="submit">{{translate('update_password')}}</button>
            </form>
        </div>
    </section>
</div>
<script src="{{asset('assets/admin-module')}}/js/jquery-3.6.0.min.js"></script>
<script src="{{asset('assets/admin-module')}}/js/main.js"></script>
<script src="{{asset('assets/admin-module')}}/js/toastr.js"></script>
{!! Toastr::message() !!}
</body>
</html>
