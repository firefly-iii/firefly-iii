<?php
declare(strict_types=1);

namespace FireflyIII\Ldap\Rules;

use LdapRecord\Laravel\Auth\Rule;
use LdapRecord\Models\Attributes\DistinguishedName;
use LdapRecord\Query\ObjectNotFoundException;
use Log;

/**
 * Class UserDefinedRule
 */
class UserDefinedRule extends Rule
{
    /**
     * Check if the rule passes validation.
     *
     * @return bool
     * @throws ObjectNotFoundException
     */
    public function isValid()
    {
        $groupFilter = config('ldap.group_filter');
        Log::debug(sprintf('UserDefinedRule with group filter "%s"', $groupFilter));

        if (empty($groupFilter)) {
            Log::debug('Group filter is empty, return true.');

            return true;
        }
        Log::debug('Group filter is not empty, continue.');

        // group class:
        // use ;
        $openLDAP        = class_exists(\LdapRecord\Models\OpenLDAP\Group::class) ? \LdapRecord\Models\OpenLDAP\Group::class : '';
        $activeDirectory = class_exists(\LdapRecord\Models\ActiveDirectory\Group::class) ? \LdapRecord\Models\ActiveDirectory\Group::class : '';
        $groupClass      = env('LDAP_DIALECT') === 'OpenLDAP' ? $openLDAP : $activeDirectory;

        Log::debug(sprintf('Will use group class "%s"', $groupClass));


        // We've been given an invalid group filter. We will assume the
        // developer is using some group ANR attribute, and attempt
        // to check the user's membership with the resulting group.
        if (!DistinguishedName::isValid($groupFilter)) {
            Log::debug('UserDefinedRule: Is not valid DN');

            return $this->user->groups()->recursive()->exists($groupClass::findByAnrOrFail($groupFilter));
        }

        $head = strtolower(DistinguishedName::make($groupFilter)->head());
        Log::debug(sprintf('UserDefinedRule: Head is "%s"', $head));
        // If the head of the DN we've been given is an OU, we will assume
        // the developer is looking to filter users based on hierarchy.
        // Otherwise, we'll attempt locating a group by the given
        // group filter and checking the users group membership.
        if ('ou' === $head) {
            Log::debug('UserDefinedRule: Will return if user is a descendant of.');

            return $this->user->isDescendantOf($groupFilter);
        }
        Log::debug('UserDefinedRule: Will return if user exists in group.');

        return $this->user->groups()->recursive()->exists($groupClass::findOrFail($groupFilter));
        //
        //
        //        // old
        //        $groupFilter = config('ldap.group_filter');
        //
        //        if (null !== $groupFilter && '' !== (string)$groupFilter) {
        //
        //
        //            return $this->user->groups()->recursive()->exists(Group::findOrFail($groupFilter));
        //        }
        //        Log::debug('Group filter is empty or NULL, so will return true.');
        //
        //        return true;
    }
}
