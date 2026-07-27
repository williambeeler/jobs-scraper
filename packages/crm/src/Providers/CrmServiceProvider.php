<?php

namespace WilliamBeeler\Crm\Providers;

use Illuminate\Support\ServiceProvider;
use WilliamBeeler\Crm\Console\Commands\CompaniesRemapStatusesCommand;
use WilliamBeeler\Crm\Console\Commands\CompanyCleanupCommand;
use WilliamBeeler\Crm\Console\Commands\CompanyEnrichCommand;
use WilliamBeeler\Crm\Console\Commands\ImportLeadsCommand;
use WilliamBeeler\Crm\Console\Commands\ImportSBCLeadsCommand;
use WilliamBeeler\Crm\Console\Commands\LeadsGoogleCommand;
use WilliamBeeler\Crm\Models\Tag;
use WilliamBeeler\Crm\Support\TableNames;

class CrmServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/crm.php', 'crm');

        config()->set('tags.tag_model', Tag::class);
        config()->set('tags.taggable.table_name', TableNames::get('taggables'));
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                CompaniesRemapStatusesCommand::class,
                CompanyCleanupCommand::class,
                CompanyEnrichCommand::class,
                ImportLeadsCommand::class,
                ImportSBCLeadsCommand::class,
                LeadsGoogleCommand::class,
            ]);
        }

        config()->set('inertia.pages.paths', array_values(array_unique([
            ...config('inertia.pages.paths', []),
            __DIR__.'/../../resources/js/Pages',
        ])));

        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
        $this->loadRoutesFrom(__DIR__.'/../../routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../../resources/views', 'crm');
        $this->app->make('view')->addLocation(__DIR__.'/../../resources/views');

        $this->publishes([
            __DIR__.'/../../config/crm.php' => config_path('crm.php'),
        ], 'crm-config');
    }
}
