<?php

namespace FireflyIII\Ldap\Scopes;

use LdapRecord\Models\Model;
use LdapRecord\Models\Scope;
use LdapRecord\Query\Model\Builder;
use Log;


/**
 * Class UserDefinedScope
 */
class UserDefinedScope implements Scope
{
    /**
     * Apply the scope to the given query.
     *
     * @param Builder $query
     * @param Model   $model
     *
     * @return void
     */
    public function apply(Builder $query, Model $model)
    {

        Log::debug('UserDefinedScope is disabled.');

        // scope is disabled:



        /*
        $groupFilter = config('ldap.group_filter');
        Log::debug(sprintf('UserDefinedScope with group filter "%s"', $groupFilter));
        if (null !== $groupFilter && '' !== (string)$groupFilter) {
            Log::debug('UserDefinedScope: Group filter is not empty, will now apply it.');
            $query->in($groupFilter);
        }
        Log::debug('UserDefinedScope: done!');
        */
    }
}
