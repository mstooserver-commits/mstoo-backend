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
    <link
        href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,400&display=swap"
        rel="stylesheet"/>
    <link href="{{asset('assets/admin-module')}}/css/material-icons.css" rel="stylesheet"/>
    <link rel="stylesheet" href="{{asset('assets/admin-module')}}/css/bootstrap.min.css"/>
    <link rel="stylesheet" href="{{asset('assets/admin-module')}}/css/style.css"/>
    <link rel="stylesheet" href="{{asset('assets/admin-module')}}/css/toastr.css">
</head>
<body>
<div class="login-form dark-support" data-bg-img="{{asset('assets/admin-module')}}/img/media/login-bg.png">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8 col-xl-7">
                <form action="{{route('admin.auth.login')}}" method="POST" id="login-form">
                    @csrf
                    <div class="card my-5 ov-hidden">
                        <div class="login-wrap">
                            <div class="login-left">
                                <img class="login-img"
                                     src="{{asset('assets/admin-module')}}/img/media/login-img.png"
                                     alt="">
                            </div>
                            <div class="login-right-wrap">
                                <div class="login-right">
                                    <div class="mb-3">
                                        @php($loginTitle = business_live('login_title', 'business_information', ''))
                                        @php($loginSubtitle = business_live('login_subtitle', 'business_information', ''))
                                        @php($loginLogo = business_config('business_logo', 'business_information'))
                                        @if($loginTitle)
                                            <h3 class="mb-1">{{ $loginTitle }}</h3>
                                        @endif
                                        @if($loginSubtitle)
                                            <p class="text-muted mb-3">{{ $loginSubtitle }}</p>
                                        @endif
                                        @if($loginLogo && $loginLogo->live_values)
                                            <div class="mb-3">
                                                <img src="{{ asset('storage/app/public/business') }}/{{ $loginLogo->live_values }}"
                                                     alt="MSTOO"
                                                     style="max-height:42px;"
                                                     onerror="this.src='{{ asset('assets/placeholder.png') }}'">
                                            </div>
                                        @endif
                                        <div class="mb-30">
                                            <div class="form-floating">
                                                <input type="text" name="email_or_phone" class="form-control"
                                                       placeholder="{{translate('email')}}" required id="email"
                                                       value="{{ old('email_or_phone') }}">
                                                <label>{{translate('email')}}</label>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <div class="form-floating">
                                                <input type="password" name="password" class="form-control"
                                                       placeholder="{{translate('password')}}" required id="password">
                                                <label>{{translate('password')}}</label>
                                                <span class="material-icons togglePassword">visibility_off</span>
                                            </div>
                                        </div>
                                    </div>

                                    @php($recaptcha = business_config('recaptcha', 'third_party'))
                                    @if(isset($recaptcha) && $recaptcha->is_active)
                                        <div class="recaptcha d-flex justify-content-center mb-4">
                                            <div id="recaptcha_element" class="w-100" data-type="image"></div>
                                        </div>
                                    @endif

                                    <div class="d-flex justify-content-center">
                                        <button class="btn btn--primary radius-50 text-uppercase" type="submit">
                                            {{translate('sign_in')}}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
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
