<?php

namespace FireflyIII\Helpers\Csv\PostProcessing;

use Auth;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use Log;
use Validator;

/**
 * Class OpposingAccount
 *
 * @package FireflyIII\Helpers\Csv\PostProcessing
 */
class OpposingAccount implements PostProcessorInterface
{

    /** @var  array */
    protected $data;

    /**
     * @return array
     */
    public function process()
    {
        if ($this->data['opposing-account-id'] instanceof Account) { // first priority. try to find the account based on ID, if any
            $this->data['opposing-account-object'] = $this->data['opposing-account-id'];

            return $this->data;
        }
        if ($this->data['opposing-account-iban'] instanceof Account) { // second: try to find the account based on IBAN, if any.
            $this->data['opposing-account-object'] = $this->data['opposing-account-iban'];

            return $this->data;
        }
        $rules     = ['iban' => 'iban'];
        $check     = ['iban' => $this->data['opposing-account-iban']];
        $validator = Validator::make($check, $rules);
        $result    = !$validator->fails();
        if (is_string($this->data['opposing-account-iban']) && strlen($this->data['opposing-account-iban']) > 0) {
            if ($result) {
                $this->data['opposing-account-object'] = $this->parseIbanString();

                return $this->data;
            }
        }
        if ($this->data['opposing-account-name'] instanceof Account) { // third: try to find account based on name, if any.
            $this->data['opposing-account-object'] = $this->data['opposing-account-name'];

            return $this->data;
        }
        if (is_string($this->data['opposing-account-name'])) {
            $this->data['opposing-account-object'] = $this->parseNameString();

            return $this->data;
        }

        return null;
    }

    /**
     * @param array $data
     */
    public function setData(array $data)
    {
        $this->data = $data;
    }

    /**
     * @return Account|null
     */
    protected function parseIbanString()
    {
        // create by name and/or iban.
        $accountType = $this->getAccountType();
        $accounts    = Auth::user()->accounts()->get();
        foreach ($accounts as $entry) {
            if ($entry->iban == $this->data['opposing-account-iban']) {

                return $entry;
            }
        }
        // create if not exists:
        $name    = is_string($this->data['opposing-account-name']) && strlen($this->data['opposing-account-name']) > 0 ? $this->data['opposing-account-name']
            : $this->data['opposing-account-iban'];
        $account = Account::firstOrCreateEncrypted(
            [
                'user_id'         => Auth::user()->id,
                'account_type_id' => $accountType->id,
                'name'            => $name,
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
                Log::debug('Found an account with this name (#' . $entry->id . ': ' . $entry->name . ')');

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