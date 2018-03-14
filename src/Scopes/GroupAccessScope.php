<?php

namespace Dyrynda\Ldap\Scopes;

use Adldap\Query\Builder;
use Adldap\Laravel\Scopes\ScopeInterface;

class GroupAccessScope implements ScopeInterface
{
    public function apply(Builder $query)
    {
        collect(config('ldap.allowed_groups'))->each(function ($group) use ($query) {
            $query->whereMemberOf($group);
        });
    }
}
