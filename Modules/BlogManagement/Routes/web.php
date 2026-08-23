<?php

use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'admin', 'as' => 'admin.', 'namespace' => 'Web\Admin', 'middleware' => ['admin', 'mpc:blog_management']], function () {
    Route::group(['prefix' => 'blog', 'as' => 'blog.'], function () {
        Route::get('/', 'BlogController@index')->name('index');
        Route::get('create', 'BlogController@create')->name('create')->middleware('mpc:blog_management,create');
        Route::post('store', 'BlogController@store')->name('store')->middleware('mpc:blog_management,create');
        Route::get('show/{id}', 'BlogController@show')->name('show');
        Route::get('preview/{id}', 'BlogController@preview')->name('preview');
        Route::get('edit/{id}', 'BlogController@edit')->name('edit')->middleware('mpc:blog_management,edit');
        Route::put('update/{id}', 'BlogController@update')->name('update')->middleware('mpc:blog_management,edit');
        Route::delete('delete/{id}', 'BlogController@destroy')->name('delete')->middleware('mpc:blog_management,delete');
        Route::get('download', 'BlogController@download')->name('download');
        Route::get('tags/search', 'BlogController@searchTags')->name('tags-search');
        Route::put('settings', 'BlogController@updateSettings')->name('settings');
        Route::put('intro', 'BlogController@updateIntro')->name('intro');
    });

    Route::group(['prefix' => 'blog-category', 'as' => 'blog-category.'], function () {
        Route::get('/', 'BlogCategoryController@index')->name('index');
        Route::get('create', 'BlogCategoryController@create')->name('create');
        Route::post('store', 'BlogCategoryController@store')->name('store');
        Route::get('edit/{id}', 'BlogCategoryController@edit')->name('edit');
        Route::put('update/{id}', 'BlogCategoryController@update')->name('update');
        Route::delete('delete/{id}', 'BlogCategoryController@destroy')->name('delete');
        Route::any('status/{id}', 'BlogCategoryController@status')->name('status');
    });
});
