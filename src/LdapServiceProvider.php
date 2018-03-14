<?php

namespace Dyrynda\Ldap;

use Illuminate\Support\Carbon;
use Illuminate\Support\ServiceProvider;

class LdapServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../config/ldap.php' => config_path('laravel_ldap.php'),
        ], 'laravel-ldap');

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        Carbon::macro('fromNanosecondInterval', function ($nanoseconds) {
            // Return the 100-nanosecond interval value as a normalised UNIX timestamp
            return with(round($nanoseconds / 10000000), function ($seconds) {
                return static::createFromTimestampUTC($seconds - config('laravel_ldap.windows_epoch_interval'));
            });
        });
    }
}
