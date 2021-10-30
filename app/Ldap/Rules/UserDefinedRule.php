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
        $extraFilter = config('ldap.extra_filter');
        Log::debug(sprintf('UserDefinedRule with extra filter "%s"', $extraFilter));

        if (empty($extraFilter)) {
            Log::debug('Extra filter is empty, return true.');

            return true;
        }
        Log::debug('Extra filter is not empty, continue.');

        // group class:
        // use ;
        $openLDAP        = class_exists(\LdapRecord\Models\OpenLDAP\Group::class) ? \LdapRecord\Models\OpenLDAP\Group::class : '';
        $activeDirectory = class_exists(\LdapRecord\Models\ActiveDirectory\Group::class) ? \LdapRecord\Models\ActiveDirectory\Group::class : '';
        $groupClass      = config('ldap.dialect') === 'OpenLDAP' ? $openLDAP : $activeDirectory;

        Log::debug(sprintf('Will use dialect group class "%s"', $groupClass));


        // We've been given an invalid group filter. We will assume the
        // developer is using some group ANR attribute, and attempt
        // to check the user's membership with the resulting group.
        if (!DistinguishedName::isValid($extraFilter)) {
            Log::debug('UserDefinedRule: Is not valid DN');

            return $this->user->groups()->recursive()->exists($groupClass::findByAnrOrFail($extraFilter));
        }

        $head = strtolower(DistinguishedName::make($extraFilter)->head());
        Log::debug(sprintf('UserDefinedRule: Head is "%s"', $head));
        // If the head of the DN we've been given is an OU, we will assume
        // the developer is looking to filter users based on hierarchy.
        // Otherwise, we'll attempt locating a group by the given
        // group filter and checking the users group membership.
        if ('ou' === $head) {
            Log::debug('UserDefinedRule: Will return if user is a descendant of.');

            return $this->user->isDescendantOf($extraFilter);
        }
        Log::debug('UserDefinedRule: Will return if user exists in group.');

        return $this->user->groups()->recursive()->exists($groupClass::findOrFail($extraFilter));
    }
}
