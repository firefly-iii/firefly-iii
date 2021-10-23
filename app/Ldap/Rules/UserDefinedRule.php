<?php
declare(strict_types=1);

namespace FireflyIII\Ldap\Rules;

use LdapRecord\Laravel\Auth\Rule;
use LdapRecord\Models\ActiveDirectory\Group;
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
        if (null !== $groupFilter && '' !== (string)$groupFilter) {
            Log::debug('Group filter is not empty, will now apply it.');
            return $this->user->groups()->recursive()->exists(Group::findOrFail($groupFilter));
        }
        Log::debug('Group filter is empty or NULL, so will return true.');

        return true;
    }
}
