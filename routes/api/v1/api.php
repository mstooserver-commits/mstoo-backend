<?php

use App\Http\Controllers\Api\V1\Customer\LandingController;
use App\Http\Controllers\Api\V1\WhatsAppWebhookController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

Route::group(['prefix' => 'customer', 'as' => 'customer.', 'namespace' => 'Api\V1\Customer'], function () {
    Route::group(['prefix' => 'landing'], function () {
        Route::get('/contents', [LandingController::class, 'index']);
    });
});

Route::match(['get', 'post'], '/webhooks/whatsapp', WhatsAppWebhookController::class)
    ->name('webhooks.whatsapp');

