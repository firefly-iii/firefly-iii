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
        Log::debug('Start post processing opposing account');
        // first priority. try to find the account based on ID,
        // if any.
        if ($this->data['opposing-account-id'] instanceof Account) {
            Log::debug('opposing-account-id is an account (#' . $this->data['opposing-account-id']->id . ': ' . $this->data['opposing-account-id']->name . ')');
            $this->data['opposing-account-object'] = $this->data['opposing-account-id'];
            Log::debug('Done post processing opposing account.');

            return $this->data;
        }

        // second: try to find the account based on IBAN, if any.
        if ($this->data['opposing-account-iban'] instanceof Account) {
            Log::debug(
                'opposing-account-iban is an account (#' .
                $this->data['opposing-account-iban']->id . ': ' . $this->data['opposing-account-iban']->name . ')'
            );
            $this->data['opposing-account-object'] = $this->data['opposing-account-iban'];
            Log::debug('Done post processing opposing account.');

            return $this->data;
        }

        $rules     = ['iban' => 'iban'];
        $check     = ['iban' => $this->data['opposing-account-iban']];
        $validator = Validator::make($check, $rules);
        $result    = !$validator->fails();


        if (is_string($this->data['opposing-account-iban']) && strlen($this->data['opposing-account-iban']) > 0) {
            Log::debug('opposing-account-iban is an IBAN string (' . $this->data['opposing-account-iban'] . ')');
            if ($result) {
                Log::debug('opposing-account-iban is a valid IBAN string!');
                Log::debug('Go to parseIbanString()');
                $this->data['opposing-account-object'] = $this->parseIbanString();
                Log::debug('Done post processing opposing account.');

                return $this->data;
            } else {
                Log::debug('opposing-account-iban is NOT a valid IBAN string!');
            }
        }

        // third: try to find account based on name, if any.
        if ($this->data['opposing-account-name'] instanceof Account) {
            Log::debug(
                'opposing-account-name is an Account (#' .
                $this->data['opposing-account-name']->id . ': ' . $this->data['opposing-account-name']->name . ') '
            );
            $this->data['opposing-account-object'] = $this->data['opposing-account-name'];
            Log::debug('Done post processing opposing account.');

            return $this->data;
        }

        if (is_string($this->data['opposing-account-name'])) {
            Log::debug('Opposing account name is a string: ' . $this->data['opposing-account-name']);
            Log::debug('Go to parseNameString');
            $this->data['opposing-account-object'] = $this->parseNameString();
            Log::debug('Done post processing opposing account.');

            return $this->data;
        }
        Log::debug('Done post processing opposing account.');

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
        Log::debug('Parse IBAN string!');
        // create by name and/or iban.
        $accountType = $this->getAccountType();
        $accounts    = Auth::user()->accounts()->get();
        foreach ($accounts as $entry) {
            if ($entry->iban == $this->data['opposing-account-iban']) {
                Log::debug('Found existing account with this IBAN: (#' . $entry->id . ': ' . $entry->name . ')');

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
        Log::debug('Created new (' . $accountType->type . ')B account with this IBAN: (#' . $account->id . ': ' . $account->name . ')');

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

        Log::debug('Created a new (' . $accountType->type . ')A account with this name (#' . $account->id . ': ' . $account->name . ')');


        return $account;
    }
}