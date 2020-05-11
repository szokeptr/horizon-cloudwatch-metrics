<?php

namespace HorizonCW;

use Illuminate\Support\ServiceProvider;

class HorizonCWServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../config/horizon-cw.php' => config_path('horizon-cw.php'),
        ]);
    }

    public function register()
    {
        $this->configure();
        $this->registerCommands();
    }

    protected function configure()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/horizon-cw.php', 'horizon-cw'
        );
    }

    protected function registerCommands()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                Console\PushMetrics::class,
            ]);
        }
    }
}
