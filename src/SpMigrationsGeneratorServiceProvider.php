<?php

namespace Shahid\SpMigrationsGenerator;

use Illuminate\Support\ServiceProvider;

class SpMigrationsGeneratorServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('command.shahid.spmigrationsgenerator.generatespmigration', function($app){
            return $app['Shahid\SpMigrationsGenerator\SpMigrationsGenerateCommand'];
        });
        $this->commands('command.shahid.spmigrationsgenerator.generatespmigration');
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
