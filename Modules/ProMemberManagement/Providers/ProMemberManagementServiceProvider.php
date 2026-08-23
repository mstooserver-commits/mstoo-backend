<?php

namespace Modules\ProMemberManagement\Providers;

use Illuminate\Support\ServiceProvider;

class ProMemberManagementServiceProvider extends ServiceProvider
{
    protected $moduleName = 'ProMemberManagement';
    protected $moduleNameLower = 'promembermanagement';

    public function boot()
    {
        $this->registerConfig();
        $this->registerViews();
        $this->loadMigrationsFrom(module_path($this->moduleName, 'Database/Migrations'));
        $this->commands([
            \Modules\ProMemberManagement\Console\ExpireProMembershipsCommand::class,
        ]);
    }

    public function register()
    {
        $this->app->register(RouteServiceProvider::class);
        $this->app->singleton(\Modules\ProMemberManagement\Services\ProMemberService::class);
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
        $this->loadViewsFrom(module_path($this->moduleName, 'Resources/views'), $this->moduleNameLower);
    }
}
