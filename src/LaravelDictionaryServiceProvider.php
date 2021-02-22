<?php

namespace KamilZawada\LaravelDictionary;

use Illuminate\Support\ServiceProvider;

class LaravelDictionaryServiceProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../config/dictionary.php' => config_path('dictionary.php'),
        ]);

        if(config('dictionary.use_custom_routes'))
        {
            $this->loadRoutesFrom(base_path().'/routes/dictionary.php');
        }
        else
        {
            $this->loadRoutesFrom(__DIR__.'/../routes/dictionary.php');
        }

        $this->loadViewsFrom(__DIR__.'/../resources/views', 'dictionary');

        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/dictionary'),
        ]);

        $this->publishes([
            __DIR__.'/../public' => public_path('vendor/dictionary'),
        ], 'assets'); 

        $this->publishes([
            __DIR__.'/../routes/dictionary.php' => base_path().'/routes/dictionary.php'
        ], 'routes');
    }

    /**
     * Register any package services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}