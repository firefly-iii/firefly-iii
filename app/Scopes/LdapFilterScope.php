<?php

namespace FireflyIII\Scopes;

use Adldap\Query\Builder;
use Adldap\Laravel\Scopes\ScopeInterface;

class LdapFilterScope implements ScopeInterface {
    /**
     * If the ADLDAP_AUTH_FILTER is provided, apply the filter to the LDAP query.
     * @param Builder $query
     * @return void
     */
    public function apply(Builder $query)
    {
        $filter = (string) config('ldap_auth.custom_filter');
        if ( '' !== $filter ) {
            $query->rawFilter($filter);
        }
    }
}
