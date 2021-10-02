<?php
declare(strict_types=1);

namespace FireflyIII\Ldap\Rules;

use LdapRecord\Laravel\Auth\Rule;
use LdapRecord\Models\ActiveDirectory\Group;

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
        if (null !== $groupFilter && '' !== (string)$groupFilter) {
            $administrators = Group::find('cn=Administrators,dc=local,dc=com');

            return $this->user->groups()->recursive()->exists($administrators);
        }

        return true;
    }
}
