<?php
declare(strict_types=1);

namespace FireflyIII\Ldap\Rules;

use LdapRecord\Laravel\Auth\Rule;
use LdapRecord\Models\ActiveDirectory\Group;
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
     */
    public function isValid()
    {
        // LDAP_GROUP_FILTER
        $groupFilter = config('ldap.group_filter');
        Log::debug(sprintf('UserDefinedRule with group filter "%s"', $groupFilter));
        if (null !== $groupFilter && '' !== (string)$groupFilter) {
            Log::debug('Group filter is not empty, will now apply it.');
            $administrators = Group::find($groupFilter);
            $result         = $this->user->groups()->recursive()->exists($administrators);
            Log::debug(sprintf('Search result is %s.', var_export($result, true)));

            return $result;
        }
        Log::debug('Group filter is empty or NULL, so will return true.');

        return true;
    }
}
