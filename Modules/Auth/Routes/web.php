<?php

use Illuminate\Support\Facades\Route;
use Modules\Auth\Http\Controllers\Web\PasswordResetController;
use Modules\Auth\Http\Controllers\Web\VerificationController;

//admin routes
Route::group(['prefix' => 'admin', 'as' => 'admin.'], function () {
    Route::group(['prefix' => 'auth', 'as' => 'auth.'], function () {
        Route::get('login', 'LoginController@login_form')->name('login');
        Route::post('login', 'LoginController@admin_login')->middleware('throttle:10,1');
        Route::get('logout', 'LoginController@logout')->name('logout');
        Route::get('forgot-password', [\Modules\Auth\Http\Controllers\AdminPasswordResetController::class, 'form'])->name('forgot-password');
        Route::post('forgot-password', [\Modules\Auth\Http\Controllers\AdminPasswordResetController::class, 'sendOtp'])->middleware('throttle:8,1')->name('forgot-password.send');
        Route::post('forgot-password/reset', [\Modules\Auth\Http\Controllers\AdminPasswordResetController::class, 'reset'])->middleware('throttle:8,1')->name('forgot-password.reset');
    });
});

//provider routes
Route::group(['prefix' => 'provider', 'as' => 'provider.', 'namespace' => null], function () {
    Route::group(['prefix' => 'auth', 'as' => 'auth.'], function () {
        Route::get('sign-up', 'RegisterController@provider_self_register_form')->name('sign-up');
        Route::post('sign-up', 'RegisterController@provider_self_register')->name('sign-up-submit');

        Route::get('login', 'LoginController@provider_login_form')->name('login');
        Route::post('login', 'LoginController@provider_login')->middleware('throttle:10,1');
        Route::get('logout', 'LoginController@logout')->name('logout');

        //reset password
        Route::group(['prefix' => 'reset-password', 'as' => 'reset-password.'], function () {
            Route::get('/', [PasswordResetController::class, 'index'])->name('index');

            Route::post('send-otp', [PasswordResetController::class, 'send_otp'])->name('send-otp');
            Route::post('verify-otp', [PasswordResetController::class, 'verify_otp'])->name('verify-otp');
            Route::post('change-password', [PasswordResetController::class, 'change_password'])->name('change-password');
        });

        //verification
        Route::group(['prefix' => 'verification', 'as' => 'verification.'], function () {
            Route::get('/', [VerificationController::class, 'index'])->name('index');

            Route::post('send-otp', [VerificationController::class, 'send_otp'])->name('send-otp');
            Route::post('verify-otp', [VerificationController::class, 'verify_otp'])->name('verify-otp');
        });
    });

});

//customer routes
Route::group(['prefix' => 'customer', 'as' => 'customer', 'namespace' => 'Api\V1'], function () {
    Route::group(['prefix' => 'auth', 'as' => 'auth.'], function () {
        Route::post('registration', 'RegisterController@customer_register')->name('registration');
        Route::post('login', 'LoginController@customer_login')->name('login');
        Route::post('social-login', 'LoginController@social_customer_login')->name('social-login');
    });
});

//serviceman routes
Route::group(['prefix' => 'serviceman', 'as' => 'serviceman', 'namespace' => 'Api\V1'], function () {
    Route::group(['prefix' => 'auth', 'as' => 'auth.'], function () {
        Route::post('login', 'LoginController@serviceman_login')->name('login');
    });
});


Route::group(['prefix' => 'user', 'as' => 'user.', 'middleware' => ['auth:api'], 'namespace' => 'Api\V1'], function () {
    Route::post('logout', 'LoginController@logout')->name('logout');
});
