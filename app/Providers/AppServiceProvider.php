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

        // Force Laravel to always use APP_URL for generating URLs
        // This is critical for Raspberry Pi deployment where the app is accessible
        // via both local network (http://107.course/) and VPN (http://107.course.ats-sport.com/)
        if (config('app.url')) {
            URL::forceRootUrl(config('app.url'));
        }
    }
}
