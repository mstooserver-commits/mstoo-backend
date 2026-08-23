<?php

use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'admin/pro-member', 'as' => 'admin.pro-member.', 'namespace' => 'Web\Admin', 'middleware' => ['admin']], function () {
    Route::get('benefits', 'BenefitController@index')->name('benefits')->middleware('mpc:pro_member_management,manage_benefits');
    Route::post('benefits', 'BenefitController@store')->name('benefits.store')->middleware('mpc:pro_member_management,manage_benefits');

    Route::get('settings', 'SettingController@index')->name('settings')->middleware('mpc:pro_member_management,manage_settings');
    Route::post('settings', 'SettingController@store')->name('settings.store')->middleware('mpc:pro_member_management,manage_settings');

    Route::group(['prefix' => 'plans', 'as' => 'plans.', 'middleware' => ['mpc:pro_member_management,manage_plans']], function () {
        Route::get('/', 'PlanController@index')->name('index');
        Route::get('create', 'PlanController@create')->name('create');
        Route::post('store', 'PlanController@store')->name('store');
        Route::get('show/{id}', 'PlanController@show')->name('show');
        Route::get('edit/{id}', 'PlanController@edit')->name('edit');
        Route::put('update/{id}', 'PlanController@update')->name('update');
        Route::any('status/{id}', 'PlanController@status')->name('status');
        Route::delete('delete/{id}', 'PlanController@destroy')->name('delete');
    });

    Route::group(['prefix' => 'members', 'as' => 'members.', 'middleware' => ['mpc:pro_member_management,view']], function () {
        Route::get('/', 'MemberController@index')->name('index');
        Route::get('show/{id}', 'MemberController@show')->name('show');
        Route::post('cancel/{id}', 'MemberController@cancel')->name('cancel')->middleware('mpc:pro_member_management,edit');
    });

    Route::get('transactions', 'TransactionController@index')->name('transactions')->middleware('mpc:pro_member_management,view_transactions');
});

Route::group(['prefix' => 'payment/pro-member', 'as' => 'pro-member.payment.', 'namespace' => 'Web', 'middleware' => ['detectUser']], function () {
    Route::get('razor-pay', 'PaymentController@razorPay')->name('razor-pay');
    Route::post('razor-pay/callback', 'PaymentController@razorPayCallback')->name('razor-pay.callback')->withoutMiddleware('detectUser');
});
