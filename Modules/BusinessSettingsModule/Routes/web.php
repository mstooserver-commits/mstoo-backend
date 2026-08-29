<?php

use Illuminate\Support\Facades\Route;
use Modules\BusinessSettingsModule\Http\Controllers\Web\Admin\BusinessInformationController;
use Modules\BusinessSettingsModule\Http\Controllers\Web\Admin\ConfigurationController;
use Modules\BusinessSettingsModule\Http\Controllers\Web\Admin\DatabaseBackupController;
use Modules\BusinessSettingsModule\Http\Controllers\Web\Admin\GalleryController;
use Modules\BusinessSettingsModule\Http\Controllers\Web\Admin\SystemSetupController;
use Modules\BusinessSettingsModule\Http\Controllers\Web\Admin\SystemToolsController;

Route::group(['namespace' => 'Api\V1\Admin'], function () {
    Route::get('file-manager', 'FileManagerController@index');
});

Route::group(['prefix' => 'admin', 'as' => 'admin.', 'namespace' => 'Web\Admin', 'middleware' => ['admin']], function () {
    Route::group(['prefix' => 'business-settings', 'as' => 'business-settings.'], function () {
        Route::get('get-business-information', 'BusinessInformationController@business_information_get')
            ->middleware('mpc:system_management,view')
            ->name('get-business-information');
        Route::put('set-business-information', 'BusinessInformationController@business_information_set')
            ->middleware('mpc:system_management,edit')
            ->name('set-business-information');
        Route::post('set-business-information', 'BusinessInformationController@business_information_set')
            ->middleware('mpc:system_management,edit');

        Route::put('set-maintenance', [BusinessInformationController::class, 'maintenance_set'])
            ->middleware('mpc:system_management,edit')
            ->name('set-maintenance');
        Route::put('set-provider-settings', [BusinessInformationController::class, 'provider_settings_set'])
            ->middleware('mpc:system_management,edit')
            ->name('set-provider-settings');

        Route::put('set-otp-login-information', [BusinessInformationController::class, 'otp_login_information_set'])
            ->middleware('mpc:system_management,edit')
            ->name('set-otp-login-information');

        Route::put('set-bidding-system', 'BusinessInformationController@set_bidding_system')
            ->middleware('mpc:system_management,edit')
            ->name('set-bidding-system');

        Route::put('update-action-status', 'BusinessInformationController@update_action_status')
            ->middleware('mpc:system_management,edit')
            ->name('update-action-status');
        Route::put('set-promotion-setup', 'BusinessInformationController@promotion_setup_set')
            ->middleware('mpc:system_management,edit')
            ->name('set-promotion-setup');

        Route::get('get-pages-setup', 'BusinessInformationController@pages_setup_get')
            ->middleware('mpc:system_management,view')
            ->name('get-pages-setup');
        Route::post('set-pages-setup', 'BusinessInformationController@pages_setup_set')
            ->middleware('mpc:system_management,edit')
            ->name('set-pages-setup');

        Route::get('get-gallery-setup/{folder_path?}', function () {
            return redirect()->route('admin.system-setup.gallery');
        })->middleware('mpc:system_management,view')->name('get-gallery-setup');
        Route::post('/image-upload', function () {
            return redirect()->route('admin.system-setup.gallery');
        })->middleware('mpc:system_management,edit')->name('upload-gallery-image');
        Route::get('download/public', [BusinessInformationController::class, 'download_public_directory'])
            ->middleware('mpc:system_management,manage_backup')
            ->name('download.public');

        Route::get('get-database-backup', function () {
            return redirect()->route('admin.system-setup.backup');
        })->middleware('mpc:system_management,view')->name('get-database-backup');
        Route::get('backup-database-backup', function () {
            return redirect()->route('admin.system-setup.backup');
        })->middleware('mpc:system_management,manage_backup')->name('backup-database-backup');
        Route::get('restore-database-backup/{file_name}', [BusinessInformationController::class, 'restore_database_backup'])
            ->middleware('mpc:system_management,manage_backup')
            ->name('restore-database-backup');

        Route::get('get-landing-information', 'LandingPageController@landing_information_get')
            ->middleware('mpc:system_management,view')
            ->name('get-landing-information');
        Route::put('set-landing-information', 'LandingPageController@landing_information_set')
            ->middleware('mpc:system_management,edit')
            ->name('set-landing-information');
        Route::delete('delete-landing-information/{page}/{id}', 'LandingPageController@landing_information_delete')
            ->middleware('mpc:system_management,edit')
            ->name('delete-landing-information');

        Route::get('404-logs', [SystemToolsController::class, 'not_found_logs'])
            ->middleware('mpc:system_management,view')
            ->name('404-logs');
        Route::delete('404-logs', [SystemToolsController::class, 'clear_not_found_logs'])
            ->middleware('mpc:system_management,edit')
            ->name('404-logs.clear');
        Route::get('cron-jobs', [SystemToolsController::class, 'cron'])
            ->middleware('mpc:system_management,view')
            ->name('cron-jobs');
        Route::post('clear-cache', [SystemToolsController::class, 'clear_cache'])
            ->middleware('mpc:system_management,edit')
            ->name('clear-cache');
    });

    Route::group(['prefix' => 'system-setup', 'as' => 'system-setup.'], function () {
        Route::get('login', [SystemSetupController::class, 'login'])
            ->middleware('mpc:system_management,view')
            ->name('login');
        Route::put('login', [SystemSetupController::class, 'loginSave'])
            ->middleware('mpc:system_management,edit')
            ->name('login.save');

        Route::get('language', [SystemSetupController::class, 'language'])
            ->middleware('mpc:system_management,view')
            ->name('language');
        Route::put('language', [SystemSetupController::class, 'languageSave'])
            ->middleware('mpc:system_management,edit')
            ->name('language.save');
        Route::get('ai', [\Modules\BusinessSettingsModule\Http\Controllers\Web\Admin\AiConfigController::class, 'index'])
            ->middleware('mpc:system_management,view')
            ->name('ai');
        Route::put('ai', [\Modules\BusinessSettingsModule\Http\Controllers\Web\Admin\AiConfigController::class, 'save'])
            ->middleware('mpc:system_management,edit')
            ->name('ai.save');

        Route::get('gallery', [GalleryController::class, 'index'])
            ->middleware('mpc:system_management,view')
            ->name('gallery');
        Route::post('gallery/upload', [GalleryController::class, 'upload'])
            ->middleware('mpc:system_management,edit')
            ->name('gallery.upload');
        Route::get('gallery/{filename}', [GalleryController::class, 'show'])
            ->middleware('mpc:system_management,view')
            ->name('gallery.show');
        Route::delete('gallery/{filename}', [GalleryController::class, 'destroy'])
            ->middleware('mpc:system_management,edit')
            ->name('gallery.delete');

        Route::get('backup', [DatabaseBackupController::class, 'index'])
            ->middleware('mpc:system_management,view')
            ->name('backup');
        Route::put('backup/dump-path', [DatabaseBackupController::class, 'updateDumpPath'])
            ->middleware('mpc:system_management,manage_backup')
            ->name('backup.dump-path');
        Route::put('backup/settings', [DatabaseBackupController::class, 'updateSettings'])
            ->middleware('mpc:system_management,manage_backup')
            ->name('backup.settings');
        Route::post('backup', [DatabaseBackupController::class, 'create'])
            ->middleware('mpc:system_management,manage_backup')
            ->name('backup.create');
        Route::get('backup/{id}/status', [DatabaseBackupController::class, 'status'])
            ->middleware('mpc:system_management,view')
            ->name('backup.status');
        Route::get('backup/{id}/download', [DatabaseBackupController::class, 'download'])
            ->middleware('mpc:system_management,manage_backup')
            ->name('backup.download');
        Route::delete('backup/{id}', [DatabaseBackupController::class, 'destroy'])
            ->middleware('mpc:system_management,manage_backup')
            ->name('backup.delete');
        Route::get('backup/legacy/{filename}/download', [DatabaseBackupController::class, 'downloadLegacy'])
            ->middleware('mpc:system_management,manage_backup')
            ->name('backup.legacy.download');
        Route::delete('backup/legacy/{filename}', [DatabaseBackupController::class, 'destroyLegacy'])
            ->middleware('mpc:system_management,manage_backup')
            ->name('backup.legacy.delete');
    });

    Route::group(['prefix' => 'configuration', 'as' => 'configuration.'], function () {
        Route::get('get-notification-setting', 'ConfigurationController@notification_settings_get')
            ->middleware('mpc:system_management,view')
            ->name('get-notification-setting');
        Route::put('set-notification-setting', 'ConfigurationController@notification_settings_set')
            ->middleware('mpc:system_management,edit')
            ->name('set-notification-setting');
        Route::any('set-message-setting', 'ConfigurationController@message_settings_set')
            ->middleware('mpc:system_management,edit')
            ->name('set-message-setting');

        Route::get('get-email-config', 'ConfigurationController@email_config_get')
            ->middleware('mpc:system_management,view')
            ->name('get-email-config');
        Route::put('set-email-config', 'ConfigurationController@email_config_set')
            ->middleware('mpc:system_management,edit')
            ->name('set-email-config');

        Route::get('get-third-party-config', 'ConfigurationController@third_party_config_get')
            ->middleware('mpc:system_management,view')
            ->name('get-third-party-config');
        Route::put('set-third-party-config', 'ConfigurationController@third_party_config_set')
            ->middleware('mpc:system_management,edit')
            ->name('set-third-party-config');

        Route::get('get-app-settings', 'ConfigurationController@app_settings_config_get')
            ->middleware('mpc:system_management,view')
            ->name('get-app-settings');
        Route::put('set-app-settings', 'ConfigurationController@app_settings_config_set')
            ->middleware('mpc:system_management,edit')
            ->name('set-app-settings');

        Route::put('social-login-config-set', [ConfigurationController::class, 'social_login_config_set'])
            ->middleware('mpc:system_management,edit')
            ->name('social-login-config-set');
    });

    Route::group(['prefix' => 'customer', 'as' => 'customer.'], function () {
        Route::get('settings', [ConfigurationController::class, 'get_customer_settings'])->middleware('mpc:system_management,view')->name('settings');
        Route::put('settings', [ConfigurationController::class, 'set_customer_settings'])->middleware('mpc:system_management,edit');
    });
});
