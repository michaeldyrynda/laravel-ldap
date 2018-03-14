<?php

namespace Nuskope\Ldap\Http\Middleware;

use Adldap;
use Closure;
use Illuminate\Support\MessageBag;
use Illuminate\Support\Carbon;

class NuskopeLdapMiddleware
{
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

        $ldapUser = $this->getRequestUser($request);
        $allowedGroups = config('nuskope_ldap.allowed_groups');;

        if (! $ldapUser || array_intersect($allowedGroups, $ldapUser->memberof) == $allowedGroups) {
            cache()->forget($this->cacheKey($request));

            auth()->logout();

            return redirect()->route('login')
                ->with('errors', new MessageBag([
                    'username' => ['You no longer have CRM access.'],
                ]));
        }

        if (Carbon::fromNanosecondInterval(array_first($ldapUser->pwdlastset))->gte(now()->subMinutes(15))) {
            cache()->forget($this->cacheKey($request));

            auth()->logout();

            return redirect()->route('login')
                ->with('errors', new MessageBag([
                    'password' => ['Your password was recently changed. Please login to continue.'],
                ]));
        }

        if (Carbon::fromNanosecondInterval(array_first($ldapUser->accountexpires))->lte(now()->addDays(5))) {
            cache()->forget($this->cacheKey($request));

            auth()->logout();

            return redirect()->route('login')
                ->with('errors', new MessageBag([
                    'password' => ['Your password will expire soon and must be reset.'],
                ]));
        }

        return $next($request);
    }

    /**
     * Get the LDAP user for the current request.
     *
     * @param  \Illuminate\Http\Request  $request
     *
     * @return \Adldap\Models\Model|array|null
     */
    private function getRequestUser($request)
    {
        return cache()->remember($this->cacheKey($request), 5, function () use ($request) {
            return Adldap::search()->find($request->user()->UserName);
        });
    }

    /**
     * Return the cache key for the given request.
     *
     * @param  \Illuminate\Http\Request  $request
     *
     * @return string
     */
    private function cacheKey($request)
    {
        return sprintf('ldapUser.%s', str_slug($request->user()->UserName));
    }
}
