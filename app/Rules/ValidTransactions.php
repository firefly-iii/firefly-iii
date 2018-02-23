<?php

namespace FireflyIII\Rules;

use FireflyIII\Models\Transaction;
use Illuminate\Contracts\Validation\Rule;
use Log;

/**
 * Class ValidTransactions
 */
class ValidTransactions implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return trans('validation.invalid_selection');
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string $attribute
     * @param  mixed  $value
     *
     * @return bool
     */
    public function passes($attribute, $value)
    {
        Log::debug('In ValidTransactions::passes');
        if (!is_array($value)) {
            return true;
        }
        $userId = auth()->user()->id;
        foreach ($value as $transactionId) {
            $count = Transaction::where('transactions.id', $transactionId)
                                ->leftJoin('accounts', 'accounts.id', '=', 'transactions.account_id')
                                ->where('accounts.user_id', $userId)->count();
            if ($count === 0) {
                Log::debug(sprintf('Count for transaction #%d and user #%d is zero! Return FALSE', $transactionId, $userId));
                return false;
            }
        }
        Log::debug('Return true!');
        return true;
    }
}
