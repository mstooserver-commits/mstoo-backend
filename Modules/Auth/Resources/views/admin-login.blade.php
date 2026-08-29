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
    <link rel="stylesheet" href="{{asset('assets/admin-module')}}/css/mstoo-admin.css?v=20260829"/>
    <link rel="stylesheet" href="{{asset('assets/admin-module')}}/css/toastr.css">
</head>
<body class="login-page">
<div class="login-stage">
    @include('auth::partials._login-brand')
    <section class="login-panel">
        <div class="login-card">
            <form action="{{route('admin.auth.login')}}" method="POST" id="login-form">
                @csrf
                <p class="login-card-badge">Secure access</p>
                <h2>{{translate('sign_in')}}</h2>
                <p class="login-card-copy">{{translate('enter_your_credentials_to_continue')}}</p>

                <label class="login-label" for="email">{{translate('email')}}</label>
                <div class="login-control mb-3">
                    <span class="material-icons">mail_outline</span>
                    <input type="text" name="email_or_phone" class="form-control"
                           placeholder="{{translate('email')}}" required id="email"
                           value="{{ old('email_or_phone') }}" autocomplete="username">
                </div>

                <label class="login-label" for="password">{{translate('password')}}</label>
                <div class="login-control mb-3">
                    <span class="material-icons">lock_outline</span>
                    <input type="password" name="password" class="form-control"
                           placeholder="{{translate('password')}}" required id="password"
                           autocomplete="current-password" minlength="6">
                    <span class="material-icons togglePassword" role="button" tabindex="0">visibility_off</span>
                </div>

                <div class="login-toolbar">
                    <label class="mb-0 d-flex align-items-center gap-2">
                        <input type="checkbox" name="remember" value="1" {{ old('remember') ? 'checked' : '' }}>
                        {{translate('remember_me')}}
                    </label>
                    <a href="{{ route('admin.auth.forgot-password') }}">{{translate('forgot_password')}}</a>
                </div>

                @php($recaptcha = business_config('recaptcha', 'third_party'))
                @if(isset($recaptcha) && $recaptcha->is_active)
                    <div class="recaptcha d-flex justify-content-center mb-4">
                        <div id="recaptcha_element" class="w-100" data-type="image"></div>
                    </div>
                @endif

                <button class="btn btn--primary w-100 login-submit" type="submit" id="login-submit">
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

<script>
    $('#login-form').on('submit', function () {
        var btn = $('#login-submit');
        btn.prop('disabled', true).text('{{translate('signing_in')}}...');
    });
</script>

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
                $('#login-submit').prop('disabled', false).text('{{translate('sign_in')}}');
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
