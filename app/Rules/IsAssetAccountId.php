<?php

namespace FireflyIII\TransactionRules\Triggers;

use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use Illuminate\Contracts\Validation\Rule;

/**
 *
 * Class IsAssetAccountId
 */
class IsAssetAccountId implements Rule
{
    /**
     * Get the validation error message. This is not translated because only the API uses it.
     *
     * @return string
     */
    public function message(): string
    {
        return 'This is not an asset account.';
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string $attribute
     * @param  mixed  $value
     *
     * @return bool
     */
    public function passes($attribute, $value): bool
    {
        $accountId = (int)$value;
        $account   = Account::with('accountType')->find($accountId);
        if (null === $account) {
            return false;
        }
        if ($account->accountType->type !== AccountType::ASSET && $account->accountType->type !== AccountType::DEFAULT) {
            return false;
        }

        return true;
    }
}
