<?php

namespace Dyrynda\Ldap\Http\Middleware;

use Adldap;
use Closure;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use Illuminate\Support\MessageBag;

class LdapMiddleware
{
    protected $ldapUser;

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (! $request->user()) {
            return redirect()->route('login');
        }

        if (config('laravel_ldap.disable_while_testing') && app()->runningUnitTests()) {
            return $next($request);
        }

        if (! $this->ldapUser = $this->getRequestUser($request)) {
            $messageBag = new MessageBag([
                config('laravel_ldap.identifier') => ['Your account has been disabled or no longer exists.'],
            ]);
        } elseif ($this->ldapUserDisallowed()) {
            $messageBag = new MessageBag([
                config('laravel_ldap.identifier') => ['You no longer have access to this application.'],
            ]);
        } elseif ($this->passwordWasUpdated($request)) {
            $messageBag = new MessageBag([
                'password' => ['Your password was recently changed. Please login again to continue.'],
            ]);
        } elseif ($this->passwordIsExpiring()) {
            $messageBag = new MessageBag([
                'password' => ['Your password will expire soon and must be reset.'],
            ]);
        }

        if (isset($messageBag)) {
            cache()->forget($this->cacheKey($request));

            auth()->logout();

            return redirect()->route('login')->with('errors', $messageBag);
        }

        return $next($request);
    }

    /**
     * Determine if the user is still a member of the allowed groups.
     *
     * @return bool
     */
    protected function ldapUserDisallowed()
    {
        return with(config('laravel_ldap.allowed_groups'), function ($allowedGroups) {
            $allowedGroups = array_map(function ($group) {
                return mb_strtoupper($group);
            }, $allowedGroups);

            return array_intersect($allowedGroups, array_map(function ($group) {
                return mb_strtoupper($group);
            }, $this->ldapUser->memberof)) !== $allowedGroups;
        });
    }

    /**
     * Determine if the user's password was recently changed.
     *
     * @param  \Illuminate\Http\Request  $request
     *
     * @return bool
     */
    protected function passwordWasUpdated($request)
    {
        // If the password attribute doesn't exist on the user model, skip this check
        if (! array_key_exists(config('laravel_ldap.password_expiration_field'), $request->user()->getAttributes())) {
            return false;
        }

        return Carbon::fromNanosecondInterval(
            Arr::first($this->ldapUser->pwdlastset)
        )->gt(Carbon::fromNanosecondInterval($this->passwordUpdated($request)));
    }

    /**
     * Determine if the user's password is due to expire soon.
     *
     * @return bool
     */
    protected function passwordIsExpiring()
    {
        return Carbon::fromNanosecondInterval(
            Arr::first($this->ldapUser->accountexpires)
        )->lte(now()->addDays(config('laravel_ldap.password_expiry_threshold')));
    }

    /**
     * Get the LDAP user for the current request.
     *
     * @param  \Illuminate\Http\Request  $request
     *
     * @return \Adldap\Models\Model|array|null
     */
    protected function getRequestUser($request)
    {
        return cache()->remember($this->cacheKey($request), 5, function () use ($request) {
            return Adldap::search()->find($this->identifier($request));
        });
    }

    /**
     * Return the cache key for the given request.
     *
     * @param  \Illuminate\Http\Request  $request
     *
     * @return string
     */
    protected function cacheKey($request)
    {
        return sprintf('ldapUser.%s', Str::slug($this->identifier($request)));
    }

    /**
     * Retrieve the identifying field from the current request's user.
     *
     * @param  \Illuminate\Http\Request  $request
     *
     * @return string
     */
    protected function identifier($request)
    {
        return data_get($request->user(), config('laravel_ldap.identifier'));
    }

    /**
     * Retrieve the timestamp which determines when the user last updated their password.
     *
     * @param  \Illuminate\Http\Request  $request
     *
     * @return string
     */
    protected function passwordUpdated($request)
    {
        return data_get($request->user(), config('laravel_ldap.password_expiration_field'));
    }
}
