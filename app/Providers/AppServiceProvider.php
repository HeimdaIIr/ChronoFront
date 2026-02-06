<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // Force PHP timezone to match Laravel config
        // This ensures PHP date functions use the same timezone as Carbon/Laravel
        date_default_timezone_set(config('app.timezone'));

        // DO NOT force URL - let Laravel use the actual HTTP request host
        // This allows the app to work correctly when accessed via:
        // - Local network: http://107.course/ → generates http://107.course/ links
        // - VPN: http://107.course.ats-sport.com/ → generates http://107.course.ats-sport.com/ links
        // By not forcing APP_URL, Laravel will automatically use the current request's host
    }
}
