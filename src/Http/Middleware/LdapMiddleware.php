<?php

namespace Dyrynda\Ldap\Http\Middleware;

use Adldap;
use Closure;
use Illuminate\Support\MessageBag;
use Illuminate\Support\Carbon;

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

        if (! $this->ldapUser = $this->getRequestUser($request)) {
            $messageBag = new MessageBag([
                $this->username($request) => ['Your account has been disabled or no longer exists.'],
            ]);
        } elseif ($this->ldapUserDisallowed()) {
            $messageBag = new MessageBag([
                $this->username($request) => ['You no longer have access to this application.'],
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
            return array_intersect($allowedGroups, $this->ldapUser->memberof) !== $allowedGroups;
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
        if (! isset($request->user()->password_updated_at)) {
            return false;
        }

        return Carbon::fromNanosecondInterval(
            array_first($this->ldapUser->pwdlastset)
        )->gt(Carbon::fromNanosecondInterval($request->user()->password_updated_at));
    }

    /**
     * Determine if the user's password is due to expire soon.
     *
     * @return bool
     */
    protected function passwordIsExpiring()
    {
        return Carbon::fromNanosecondInterval(
            array_first($this->ldapUser->accountexpires)
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
            return Adldap::search()->find($this->username($request));
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
        return sprintf('ldapUser.%s', str_slug($this->username($request)));
    }

    /**
     * Retrieve the username of the current requests's user.
     *
     * @param  \Illuminate\Http\Request  $request
     *
     * @return string
     */
    protected function username($request)
    {
        return data_get($request->user(), config('laravel_ldap.username'));
    }
}
