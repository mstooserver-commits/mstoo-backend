<?php

use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'customer/pro-member', 'as' => 'customer.pro-member.', 'namespace' => 'Api\V1\Customer'], function () {
    Route::get('config', 'ProMemberController@config');
    Route::get('plans', 'ProMemberController@plans');

    Route::group(['middleware' => ['auth:api']], function () {
        Route::get('current', 'ProMemberController@current');
        Route::post('purchase', 'ProMemberController@purchase');
        Route::get('transactions', 'ProMemberController@transactions');
    });
});
