<?php

namespace flexiPIM\Hawthorne;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Illuminate\Console\Scheduling\Schedule;
use DB;

class HawthorneServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        /**
         * To Load the Packages Route File
         */
        $this->loadRoutesFrom(__DIR__.'/routes.php');
        /**
         * To Load the Hydro Farm Connector Migration File Loader
         */
        $this->loadMigrationsFrom(__DIR__.'/migrations');
        /**
         * To Load the Hydro Farm Package View
         */
        $this->loadViewsFrom(__DIR__.'/views', 'Hawthorne');
        /**
         * To Load the Product Sync Console Command Class
         */
        $this->commands('flexiPIM\Hawthorne\Commands\HawthorneDataSync');
        /**
         * To Boot the Scheduler For Hydro Farm Data Sync
         */
        if(Schema::hasTable('hawthorne_configuration')){
            $values = DB::table('hawthorne_configuration')->value('cron_time');
            if(!empty($values)){
                $this->app->booted(function () use ($values) {
                    $schedule = $this->app->make(Schedule::class);
                    $schedule->command('hawthorne:sync',['user' => null])->withoutOverlapping()->cron('0 22 * * *')->timezone('America/Los_Angeles');
                });
            }
        }
    }
}
