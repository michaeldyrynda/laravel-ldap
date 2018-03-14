<?php

namespace Nuskope\Ldap;

use Adldap\Query\Builder;
use Adldap\Laravel\Scopes\ScopeInterface;

class CrmAccessScope implements ScopeInterface
{
    public function apply(Builder $query)
    {
        $query->whereMemberOf('CN=CRM Access,OU=Security Groups,DC=staff,DC=nuskope,DC=com,DC=au');
    }
}
