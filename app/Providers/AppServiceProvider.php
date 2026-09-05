<?php

namespace App\Providers;

use App\Services\Bureau\BureauCreditoGateway;
use App\Services\Bureau\HttpBureauCreditoGateway;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(BureauCreditoGateway::class, fn () => new HttpBureauCreditoGateway(
            rtrim((string) config('services.score_bureau.url'), '/'),
            (int) config('services.score_bureau.timeout'),
        ));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
