<?php

namespace CletusKingdom\LaravelPaystack;

use Illuminate\Support\ServiceProvider;

class PaystackServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/config/paystack.php', 'paystack'
        );

        $this->app->singleton('paystack', function ($app) {
            return new Paystack();
        });
    }

    public function boot()
    {
        $this->publishes([
            __DIR__.'/config/paystack.php' => config_path('paystack.php'),
        ], 'config');
    }
}