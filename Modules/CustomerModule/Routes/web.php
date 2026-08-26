<?php

use Illuminate\Support\Facades\Route;
use Modules\CustomerModule\Http\Controllers\Web\Admin\LoyaltyPointController;
use Modules\CustomerModule\Http\Controllers\Web\Admin\NewsletterController;
use Modules\CustomerModule\Http\Controllers\Web\Admin\WalletController;
use Modules\CustomerModule\Http\Controllers\Web\WalletPaymentController;

Route::get('about-us', 'PagesController@about_us')->name('about-us');
Route::get('privacy-policy', 'PagesController@privacy_policy')->name('privacy-policy');
Route::get('terms-and-conditions', 'PagesController@terms_and_conditions')->name('terms-and-conditions');
Route::get('refund-policy', 'PagesController@refund_policy')->name('refund-policy');
Route::get('return-policy', 'PagesController@return_policy')->name('return-policy');
Route::get('cancellation-policy', 'PagesController@cancellation_policy')->name('cancellation-policy');

Route::group(['prefix' => 'admin', 'as' => 'admin.', 'namespace' => 'Web\Admin', 'middleware' => ['admin']], function () {
    Route::group(['prefix' => 'customer', 'as' => 'customer.'], function () {
        Route::get('list', 'CustomerController@index')->name('index')->middleware('mpc:customer_management,view');
        Route::get('create', 'CustomerController@create')->name('create')->middleware('mpc:customer_management,create');
        Route::post('store', 'CustomerController@store')->name('store')->middleware('mpc:customer_management,create');
        Route::get('detail/{id}', 'CustomerController@show')->name('detail')->middleware('mpc:customer_management,view');
        Route::get('edit/{id}', 'CustomerController@edit')->name('edit')->middleware('mpc:customer_management,edit');
        Route::put('update/{id}', 'CustomerController@update')->name('update')->middleware('mpc:customer_management,edit');
        Route::any('status-update/{id}', 'CustomerController@status_update')->name('status-update')->middleware('mpc:customer_management,edit');
        Route::post('document-status/{id}', 'CustomerController@document_status')->name('document-status')->middleware('mpc:customer_management,edit');
        Route::delete('delete/{id}', 'CustomerController@destroy')->name('delete')->middleware('mpc:customer_management,delete');
        Route::get('download', 'CustomerController@download')->name('download')->middleware('mpc:customer_management,export');
        Route::post('bulk', 'CustomerController@bulk')->name('bulk')->middleware('mpc:customer_management,edit');
        Route::post('wallet-adjust/{id}', 'CustomerController@wallet_adjust')->name('wallet-adjust')->middleware('mpc:customer_management,manage_wallet');

        Route::group(['prefix' => 'wallet', 'as' => 'wallet.'], function () {
            Route::get('add-fund', [WalletController::class, 'add_fund'])->name('add-fund')->middleware('mpc:customer_management,manage_wallet');
            Route::post('add-fund', [WalletController::class, 'store_fund'])->middleware('mpc:customer_management,manage_wallet');
            Route::any('report', [WalletController::class, 'get_func_report'])->name('report')->middleware('mpc:customer_management,view');
            Route::any('report/download', [WalletController::class, 'get_func_report_download'])->name('report.download')->middleware('mpc:customer_management,export');
        });

        Route::group(['prefix' => 'loyalty-point', 'as' => 'loyalty-point.', 'middleware' => ['mpc:customer_management,view']], function () {
            Route::any('report', [LoyaltyPointController::class, 'get_loyalty_point_report'])->name('report');
            Route::any('report/download', [LoyaltyPointController::class, 'get_loyalty_point_report_download'])->name('report.download');
        });
    });

    Route::group(['prefix' => 'newsletter', 'as' => 'newsletter.', 'middleware' => ['mpc:newsletter_management,view']], function () {
        Route::get('/', [NewsletterController::class, 'index'])->name('index');
        Route::post('store', [NewsletterController::class, 'store'])->name('store')->middleware('mpc:newsletter_management,create');
        Route::get('status/{id}', [NewsletterController::class, 'status'])->name('status')->middleware('mpc:newsletter_management,edit');
        Route::delete('delete/{id}', [NewsletterController::class, 'destroy'])->name('delete')->middleware('mpc:newsletter_management,delete');
    });
});

Route::group(['prefix' => 'payment/wallet/add-fund', 'as' => 'wallet.add-fund.', 'middleware' => ['detectUser']], function () {
    Route::get('razor-pay', [WalletPaymentController::class, 'razorPay'])->name('razor-pay');
    Route::post('razor-pay/callback', [WalletPaymentController::class, 'razorPayCallback'])->name('razor-pay.callback')->withoutMiddleware('detectUser');
});
