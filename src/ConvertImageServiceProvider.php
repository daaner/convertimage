<?php

namespace Daaner\ConvertImage;

use Illuminate\Support\ServiceProvider;

class ConvertImageServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../config/convert.php' => config_path('convert.php'),
        ], 'config');

    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/convert.php', 'convert');

        $this->app->singleton('convert', function () {
            return $this->app->make(ConvertImage::class);
        });

        $this->app->alias('convert', 'ConvertImage');
    }
}
