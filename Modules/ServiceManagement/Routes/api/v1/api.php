<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Modules\ServiceManagement\Http\Controllers\Api\V1\Customer\ServiceController as CustomerServiceController;
use Modules\ServiceManagement\Http\Controllers\Api\V1\Provider\ServiceRequestController;

use Modules\ServiceManagement\Http\Controllers\Api\V1\Provider\ServiceController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::group(['prefix' => 'admin', 'as' => 'admin.', 'namespace' => 'Api\V1\Admin', 'middleware' => ['auth:api', 'admin.api']], function () {
    Route::post('service/bulk', [\Modules\ServiceManagement\Http\Controllers\Api\V1\Admin\BulkAdController::class, 'store']);
    Route::resource('service', 'ServiceController', ['only' => ['index', 'store', 'edit', 'update', 'show']]);
    Route::put('service/status/update', 'ServiceController@status_update');
    Route::delete('service/delete', 'ServiceController@destroy');

    Route::resource('faq', 'FAQController', ['only' => ['index', 'store', 'edit', 'update', 'show']]);
    Route::put('faq/status/update', 'FAQController@status_update');
    Route::delete('faq/delete', 'FAQController@destroy');
});

Route::group(['prefix' => 'provider', 'as' => 'provider.', 'namespace' => 'Api\V1\Provider', 'middleware' => ['auth:api']], function () {
    Route::resource('service', 'ServiceController', ['only' => ['index', 'show']]);
    Route::put('service/status/update', 'ServiceController@status_update');
    Route::get('service/data/search', 'ServiceController@search');
    Route::get('service/review/{service_id}', 'ServiceController@review');


    //  custom route by naresh for provider
    Route::post('add_service', 'ServiceController@add_service');
    Route::post('add_services', [\Modules\ServiceManagement\Http\Controllers\Api\V1\Customer\BulkAdController::class, 'store']);
    Route::post('myservices', 'ServiceController@myservices');
    Route::get('getservice/{id}', 'ServiceController@getservice');
    Route::post('update_service/{id}', 'ServiceController@update_service');
    Route::post('delete_service/{id}', 'ServiceController@delete_service');

    Route::get('getrenter/{id}', 'ServiceController@getrenter');
    Route::get('getthumbnails/{id}', 'ServiceController@getthumbnails');

    // closed

    Route::get('service-request', [ServiceRequestController::class, 'index']);
    Route::post('service-request', [ServiceRequestController::class, 'make_request']);

    Route::resource('faq', 'FAQController', ['only' => ['index', 'show']]);
});

Route::group(['prefix' => 'customer', 'as' => 'customer.', 'namespace' => 'Api\V1\Customer'], function () {
    Route::post('ads/bulk', [\Modules\ServiceManagement\Http\Controllers\Api\V1\Customer\BulkAdController::class, 'store'])->middleware('auth:api');
    Route::post('service/bulk', [\Modules\ServiceManagement\Http\Controllers\Api\V1\Customer\BulkAdController::class, 'store'])->middleware('auth:api');
    Route::group(['prefix' => 'service'], function () {
        Route::get('/', [CustomerServiceController::class, 'index']);
        Route::get('search', [CustomerServiceController::class, 'search']);
        Route::get('search/recommended', [CustomerServiceController::class, 'search_recommended']);
        Route::get('popular', [CustomerServiceController::class, 'popular']);
        Route::get('recommended', [CustomerServiceController::class, 'recommended']);
        Route::get('trending', [CustomerServiceController::class, 'trending']);
        Route::get('recently-viewed', [CustomerServiceController::class, 'recently_viewed'])->middleware('auth:api');
        Route::get('offers', [CustomerServiceController::class, 'offers']);
        Route::get('detail/{id}', [CustomerServiceController::class, 'show']);
        Route::get('review/{service_id}', [CustomerServiceController::class, 'review']);
        Route::get('sub-category/{sub_category_id}', [CustomerServiceController::class, 'services_by_subcategory']);

        // filter service by price
        Route::get('filter/{sub_category_id}', [CustomerServiceController::class, 'filter_service']);


        Route::get('sort_allservice_by_price_asec', [CustomerServiceController::class, 'sort_allservice_by_price_asec']);
        Route::get('sort_allservice_by_price_desc', [CustomerServiceController::class, 'sort_allservice_by_price_desc']);
        Route::get('sort_allservice_by_oldest', [CustomerServiceController::class, 'sort_allservice_by_oldest']);

        Route::get('getrenter/{id}', [CustomerServiceController::class, 'getrenter']);
        Route::get('getthumbnails/{id}', [CustomerServiceController::class, 'getthumbnails']);

        Route::post('like_service', [CustomerServiceController::class, 'like_service'])->middleware('auth:api');
        Route::post('dislike_service', [CustomerServiceController::class, 'dislike_service'])->middleware('auth:api');
        Route::post('report_service', [CustomerServiceController::class, 'report_service'])->middleware('auth:api');
        Route::post('save_for_later', [CustomerServiceController::class, 'save_for_later'])->middleware('auth:api');
        Route::post('remove_save_for_later', [CustomerServiceController::class, 'remove_save_for_later'])->middleware('auth:api');
        Route::post('liked_services', [CustomerServiceController::class, 'liked_services'])->middleware('auth:api');


        Route::get('sort_by_price_asec/{sub_category_id}', [CustomerServiceController::class, 'sort_by_price_asec']);
        Route::get('sort_by_price_desc/{sub_category_id}', [CustomerServiceController::class, 'sort_by_price_desc']);
        Route::get('sort_by_newest/{sub_category_id}', [CustomerServiceController::class, 'sort_by_newest']);
        Route::get('sort_by_oldest/{sub_category_id}', [CustomerServiceController::class, 'sort_by_oldest']);


        Route::group(['prefix' => 'request'], function () {
            Route::post('make', [CustomerServiceController::class, 'make_request'])->middleware('auth:api');
            Route::get('list', [CustomerServiceController::class, 'request_list'])->middleware('auth:api');
        });

    });
    Route::get('recently-searched-keywords', 'ServiceController@recently_searched_keywords')->middleware('auth:api');
    Route::get('remove-searched-keywords', 'ServiceController@remove_searched_keywords')->middleware('auth:api');
});


// Custom route by naresh 
Route::get('getallcat', [ServiceController::class, 'getallcat']);
Route::get('getallservice', [ServiceController::class, 'getallservice']);
Route::get('getallsubcat', [ServiceController::class, 'getallsubcat']);
Route::get('getallsubcatbyid/{id}', [ServiceController::class, 'getallsubcatbyid']);
Route::get('getfields', [ServiceController::class, 'getfields']);
Route::get('getfieldsbyid/{id}', [ServiceController::class, 'getfieldsbyid']);
Route::get('getpaymentgateway', [ServiceController::class, 'getpaymentgateway']);
