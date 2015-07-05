<?php

namespace FireflyIII\Helpers\Csv;

use Auth;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;

/**
 * Class OpposingAccount
 *
 * @package FireflyIII\Helpers\Csv
 */
class OpposingAccount
{
    /** @var  array */
    protected $data;

    /**
     * @param array $data
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * @return \FireflyIII\Models\Account|null
     */
    public function parse()
    {
        // first priority. try to find the account based on ID,
        // if any.
        if ($this->data['opposing-account-id'] instanceof Account) {

            return $this->data['opposing-account-id'];
        }

        // second: try to find the account based on IBAN, if any.
        if ($this->data['opposing-account-iban'] instanceof Account) {
            return $this->data['opposing-account-iban'];
        }


        if (is_string($this->data['opposing-account-iban'])) {

            return $this->parseIbanString();
        }

        // third: try to find account based on name, if any.
        if ($this->data['opposing-account-name'] instanceof Account) {

            return $this->data['opposing-account-name'];
        }

        if (is_string($this->data['opposing-account-name'])) {
            return $this->parseNameString();
        }

        return null;

        // if nothing, create expense/revenue, never asset. TODO
    }

    /**
     * @return Account|null
     */
    protected function parseIbanString()
    {

        // create by name and/or iban.
        $accountType = $this->getAccountType();
        $accounts    = Auth::user()->accounts()->where('account_type_id', $accountType->id)->get();
        foreach ($accounts as $entry) {
            if ($entry->iban == $this->data['opposing-account-iban']) {

                return $entry;
            }
        }
        // create if not exists:
        $account = Account::firstOrCreateEncrypted(
            [
                'user_id'         => Auth::user()->id,
                'account_type_id' => $accountType->id,
                'name'            => $this->data['opposing-account-iban'],
                'iban'            => $this->data['opposing-account-iban'],
                'active'          => true,
            ]
        );

        return $account;
    }

    /**
     *
     * @return AccountType
     */
    protected function getAccountType()
    {
        // opposing account type:
        if ($this->data['amount'] < 0) {
            // create expense account:

            return AccountType::where('type', 'Expense account')->first();
        } else {
            // create revenue account:

            return AccountType::where('type', 'Revenue account')->first();
        }
    }

    /**
     * @return Account|null
     */
    protected function parseNameString()
    {
        $accountType = $this->getAccountType();
        $accounts    = Auth::user()->accounts()->where('account_type_id', $accountType->id)->get();
        foreach ($accounts as $entry) {
            if ($entry->name == $this->data['opposing-account-name']) {
                return $entry;
            }
        }
        // create if not exists:
        $account = Account::firstOrCreateEncrypted(
            [
                'user_id'         => Auth::user()->id,
                'account_type_id' => $accountType->id,
                'name'            => $this->data['opposing-account-name'],
                'iban'            => '',
                'active'          => true,
            ]
        );

        return $account;
    }
}