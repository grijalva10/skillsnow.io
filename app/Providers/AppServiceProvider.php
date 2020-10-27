<?php

namespace App\Providers;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Schema::defaultStringLength(191);

        if (config('app.env') === 'production') {
            URL::forceScheme('https');
        }

        view()->composer(['admin.includes.header'], function ($view) {
            $translationLangs = array_map('basename', \Illuminate\Support\Facades\File::directories(base_path('/resources/lang')));
            $view->with('translationLangs', $translationLangs);
        });

        // $this->app['router']->aliasMiddleware('sclc', \App\Http\Middleware\SCLC::class);
        // $this->app['router']->aliasMiddleware('sclcc', \App\Http\Middleware\SCLCC::class);
        // $this->app['router']->aliasMiddleware('selfhelpm', \App\Http\Middleware\SelfHelpM::class);
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {

    }
}
