<?php

namespace Modules\BlogManagement\Providers;

use Illuminate\Support\ServiceProvider;

class BlogManagementServiceProvider extends ServiceProvider
{
    protected $moduleName = 'BlogManagement';
    protected $moduleNameLower = 'blogmanagement';

    public function boot()
    {
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->loadMigrationsFrom(module_path($this->moduleName, 'Database/Migrations'));
        $this->commands([
            \Modules\BlogManagement\Console\PublishScheduledBlogsCommand::class,
        ]);
    }

    public function register()
    {
        $this->app->register(RouteServiceProvider::class);
    }

    protected function registerConfig()
    {
        $this->mergeConfigFrom(
            module_path($this->moduleName, 'Config/config.php'),
            $this->moduleNameLower
        );
    }

    protected function registerViews()
    {
        $sourcePath = module_path($this->moduleName, 'Resources/views');
        $this->loadViewsFrom($sourcePath, $this->moduleNameLower);
    }

    protected function registerTranslations()
    {
        $this->loadTranslationsFrom(module_path($this->moduleName, 'Resources/lang'), $this->moduleNameLower);
    }
}
