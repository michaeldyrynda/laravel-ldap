<?php

namespace Nuskope\Ldap;

use Illuminate\Support\Carbon;
use Illuminate\Support\ServiceProvider;

class NuskopeLdapServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../config/ldap.php' => config_path('nuskope_ldap.php'),
        ], 'nuskope_ldap');

        Carbon::macro('fromNanosecondInterval', function ($nanoseconds) {
            // Convert last update time (100 nanosecond intervals) to microseconds
            $seconds = round($nanoseconds / 10000000);

            // Return the 100-nanosecond interval value as a normalised UNIX timestamp
            return static::createFromTimestampUTC($seconds - config('nuskope_ldap.windows_epoch_interval'));
        });
    }
}
