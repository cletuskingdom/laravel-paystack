<?php

namespace CletusKingdom\Paystack;
use Illuminate\Support\ServiceProvider;

class PaystackServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/config/paystack.php', 'paystack'
        );

        $this->app->singleton('laravel-paystack', function ($app) {
            return new Paystack();
        });

        $this->app->alias(Paystack::class, 'paystack');
    }

    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/config/paystack.php' => config_path('paystack.php'),
            ], 'paystack-config');
        }
    }
}