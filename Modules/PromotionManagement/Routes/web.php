<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'admin', 'as' => 'admin.', 'namespace' => 'Web\Admin', 'middleware' => ['admin','mpc:promotion_management']], function () {

    Route::group(['prefix' => 'discount', 'as' => 'discount.'], function () {
        Route::any('create', 'DiscountController@create')->name('create');
        Route::any('list', 'DiscountController@index')->name('list');
        Route::post('store', 'DiscountController@store')->name('store');
        Route::get('edit/{id}', 'DiscountController@edit')->name('edit');
        Route::put('update/{id}', 'DiscountController@update')->name('update');
        Route::any('status-update/{id}', 'DiscountController@status_update')->name('status-update');
        Route::delete('delete/{id}', 'DiscountController@destroy')->name('delete');
        Route::any('download', 'DiscountController@download')->name('download');
    });

    Route::group(['prefix' => 'coupon', 'as' => 'coupon.'], function () {
        Route::any('create', 'CouponController@create')->name('create');
        Route::any('list', 'CouponController@index')->name('list');
        Route::post('store', 'CouponController@store')->name('store');
        Route::get('edit/{id}', 'CouponController@edit')->name('edit');
        Route::put('update/{id}', 'CouponController@update')->name('update');
        Route::any('status-update/{id}', 'CouponController@status_update')->name('status-update');
        Route::delete('delete/{id}', 'CouponController@destroy')->name('delete');
        Route::any('download', 'CouponController@download')->name('download');
    });

    Route::group(['prefix' => 'campaign', 'as' => 'campaign.'], function () {
        Route::any('create', 'CampaignController@create')->name('create');
        Route::any('list', 'CampaignController@index')->name('list');
        Route::post('store', 'CampaignController@store')->name('store');
        Route::get('edit/{id}', 'CampaignController@edit')->name('edit');
        Route::put('update/{id}', 'CampaignController@update')->name('update');
        Route::any('status-update/{id}', 'CampaignController@status_update')->name('status-update');
        Route::delete('delete/{id}', 'CampaignController@destroy')->name('delete');
        Route::any('download', 'CampaignController@download')->name('download');
    });

    Route::group(['prefix' => 'wallet-bonus', 'as' => 'wallet-bonus.'], function () {
        Route::any('create', 'WalletBonusController@create')->name('create');
        Route::any('list', 'WalletBonusController@index')->name('list');
        Route::post('store', 'WalletBonusController@store')->name('store');
        Route::get('edit/{id}', 'WalletBonusController@edit')->name('edit');
        Route::put('update/{id}', 'WalletBonusController@update')->name('update');
        Route::any('status-update/{id}', 'WalletBonusController@status_update')->name('status-update');
        Route::delete('delete/{id}', 'WalletBonusController@destroy')->name('delete');
        Route::any('download', 'WalletBonusController@download')->name('download');
    });

    Route::group(['prefix' => 'advertisement', 'as' => 'advertisement.'], function () {
        Route::any('create', 'AdvertisementController@index')->name('create');
        Route::any('list', 'AdvertisementController@index')->name('list');
        Route::post('store', 'AdvertisementController@store')->name('store');
        Route::get('edit/{id}', 'AdvertisementController@edit')->name('edit');
        Route::put('update/{id}', 'AdvertisementController@update')->name('update');
        Route::any('status-update/{id}', 'AdvertisementController@status_update')->name('status-update');
        Route::delete('delete/{id}', 'AdvertisementController@destroy')->name('delete');
    });

    Route::group(['prefix' => 'banner', 'as' => 'banner.'], function () {
        Route::any('create', 'BannerController@create')->name('create');
        Route::any('list', 'BannerController@create')->name('list');
        Route::post('store', 'BannerController@store')->name('store');
        Route::get('edit/{id}', 'BannerController@edit')->name('edit');
        Route::put('update/{id}', 'BannerController@update')->name('update');
        Route::any('status-update/{id}', 'BannerController@status_update')->name('status-update');
        Route::delete('delete/{id}', 'BannerController@destroy')->name('delete');
        Route::any('download', 'BannerController@download')->name('download');
    });

    Route::group(['prefix' => 'push-notification', 'as' => 'push-notification.'], function () {
        Route::get('create', 'PushNotificationController@create')->name('create')->middleware('mpc:promotion_management,send');
        Route::get('list', 'PushNotificationController@history')->name('list');
        Route::get('show/{id}', 'PushNotificationController@show')->name('show');
        Route::post('store', 'PushNotificationController@store')->name('store')->middleware('mpc:promotion_management,send');
        Route::get('edit/{id}', 'PushNotificationController@edit')->name('edit');
        Route::put('update/{id}', 'PushNotificationController@update')->name('update');
        Route::any('status-update/{id}', 'PushNotificationController@status_update')->name('status-update');
        Route::delete('delete/{id}', 'PushNotificationController@destroy')->name('delete');
        Route::any('download', 'PushNotificationController@download')->name('download');
        Route::get('users/search', 'PushNotificationController@searchUsers')->name('users-search');
        Route::post('preview-recipients', 'PushNotificationController@previewRecipients')->name('preview-recipients');
        Route::get('settings', 'PushNotificationController@settings')->name('settings');
        Route::put('settings', 'PushNotificationController@updateSettings')->name('settings-update');
        Route::get('channels', 'PushNotificationController@channels')->name('channels');
    });

    Route::group(['prefix' => 'notifications', 'as' => 'notifications.'], function () {
        Route::get('create', 'PushNotificationController@create')->name('create');
        Route::get('/', 'PushNotificationController@history')->name('index');
        Route::get('settings', 'PushNotificationController@settings')->name('settings');
        Route::get('channels', 'PushNotificationController@channels')->name('channels');
    });

});

