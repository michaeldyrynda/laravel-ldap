<?php

namespace Nuskope\Ldap;

use Adldap\Query\Builder;
use Adldap\Laravel\Scopes\ScopeInterface;

class ServiceStatusAccessScope implements ScopeInterface
{
    public function apply(Builder $query)
    {
        $query->whereMemberOf('CN=Service Status Access,OU=Security Groups,DC=staff,DC=nuskope,DC=com,DC=au');
    }
}
