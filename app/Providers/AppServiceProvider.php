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

        // Dynamically detect and use the request's domain for URL generation
        // This allows the app to work correctly when accessed via:
        // - Local network: http://107.course/
        // - VPN: http://107.course.ats-sport.com/
        // Laravel will automatically use the domain from the incoming HTTP request
        // to generate all URLs, ensuring links work regardless of access method
        if (app()->runningInConsole()) {
            // In console, use APP_URL from config
            if (config('app.url')) {
                URL::forceRootUrl(config('app.url'));
            }
        } else {
            // In HTTP requests, use the actual request URL
            // This ensures URLs match the domain the user is accessing from
            URL::forceRootUrl(
                request()->getScheme() . '://' . request()->getHost()
            );
        }
    }
}
