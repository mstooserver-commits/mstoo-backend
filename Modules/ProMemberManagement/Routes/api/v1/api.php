<?php

use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'customer/pro-member', 'as' => 'customer.pro-member.', 'namespace' => 'Api\V1\Customer'], function () {
    Route::get('config', 'ProMemberController@config');
    Route::get('plans', 'ProMemberController@plans');
    Route::get('plans/{id}', 'ProMemberController@show');

    Route::group(['middleware' => ['auth:api']], function () {
        Route::get('current', 'ProMemberController@current');
        Route::post('purchase', 'ProMemberController@purchase');
        Route::post('renew', 'ProMemberController@purchase');
        Route::post('cancel', 'ProMemberController@cancel');
        Route::get('history', 'ProMemberController@history');
        Route::get('transactions', 'ProMemberController@transactions');
    });
});

Route::group(['prefix' => 'customer/subscription', 'namespace' => 'Api\V1\Customer'], function () {
    Route::get('packages', 'ProMemberController@plans');
    Route::get('packages/{id}', 'ProMemberController@show');
    Route::group(['middleware' => ['auth:api']], function () {
        Route::get('current', 'ProMemberController@current');
        Route::post('purchase', 'ProMemberController@purchase');
        Route::post('renew', 'ProMemberController@purchase');
        Route::post('cancel', 'ProMemberController@cancel');
        Route::get('history', 'ProMemberController@history');
    });
});

Route::group(['prefix' => 'admin', 'as' => 'admin.', 'namespace' => 'Api\V1\Admin', 'middleware' => ['auth:api', 'admin.api']], function () {
    Route::get('subscription-package', 'PlanController@index');
    Route::post('subscription-package', 'PlanController@store');
    Route::put('subscription-package/status/update', 'PlanController@status_update');
    Route::delete('subscription-package/delete', 'PlanController@destroy');
    Route::get('subscription-package/{id}', 'PlanController@edit');
    Route::put('subscription-package/{id}', 'PlanController@update');

    Route::get('subscription-subscriber', 'MemberController@index');
    Route::get('subscription-subscriber/{id}', 'MemberController@show');
    Route::post('subscription-subscriber/{id}/cancel', 'MemberController@cancel');

    Route::get('subscription-settings', 'SettingController@index');
    Route::put('subscription-settings', 'SettingController@update');
});
