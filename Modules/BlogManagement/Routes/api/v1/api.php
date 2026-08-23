<?php

use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'customer', 'as' => 'customer.', 'namespace' => 'Api\V1\Customer'], function () {
    Route::get('blogs', 'BlogController@index');
    Route::get('blogs/{slug}', 'BlogController@show');
    Route::get('blog-categories', 'BlogController@categories');
});
