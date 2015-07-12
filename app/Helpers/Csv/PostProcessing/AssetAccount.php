<?php

namespace FireflyIII\Helpers\Csv\PostProcessing;

use Auth;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use Log;
use Validator;

/**
 * Class AssetAccount
 *
 * @package FireflyIII\Helpers\Csv\PostProcessing
 */
class AssetAccount implements PostProcessorInterface
{

    /** @var  array */
    protected $data;

    /**
     * @return array
     */
    public function process()
    {
        $result = $this->checkIdNameObject(); // has object in ID or Name?
        if (!is_null($result)) {
            return $result;
        }

        $result = $this->checkIbanString();
        if (!is_null($result)) {
            return $result;
        }

        $result = $this->checkNameString();
        if (!is_null($result)) {
            return $result;
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
     * @return array
     */
    protected function checkIdNameObject()
    {
        if ($this->data['asset-account-id'] instanceof Account) { // first priority. try to find the account based on ID, if any
            $this->data['asset-account-object'] = $this->data['asset-account-id'];

            return $this->data;
        }
        if ($this->data['asset-account-iban'] instanceof Account) { // second: try to find the account based on IBAN, if any.
            $this->data['asset-account-object'] = $this->data['asset-account-iban'];

            return $this->data;
        }

        return null;
    }

    /**
     * @return array|null
     */
    protected function checkIbanString()
    {
        $rules     = ['iban' => 'iban'];
        $check     = ['iban' => $this->data['asset-account-iban']];
        $validator = Validator::make($check, $rules);
        if (!$validator->fails()) {
            $this->data['asset-account-object'] = $this->parseIbanString();

            return $this->data;
        }

        return null;
    }

    /**
     * @return Account|null
     */
    protected function parseIbanString()
    {
        // create by name and/or iban.
        $accounts = Auth::user()->accounts()->get();
        foreach ($accounts as $entry) {
            if ($entry->iban == $this->data['asset-account-iban']) {

                return $entry;
            }
        }
        $account = $this->createAccount();

        return $account;
    }

    /**
     * @return Account|null
     */
    protected function createAccount()
    {
        $accountType = $this->getAccountType();

        // create if not exists:
        $name    = is_string($this->data['asset-account-name']) && strlen($this->data['asset-account-name']) > 0 ? $this->data['asset-account-name']
            : $this->data['asset-account-iban'];
        $account = Account::firstOrCreateEncrypted(
            [
                'user_id'         => Auth::user()->id,
                'account_type_id' => $accountType->id,
                'name'            => $name,
                'iban'            => $this->data['asset-account-iban'],
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
        return AccountType::where('type', 'Asset account')->first();
    }

    /**
     * @return array|null
     */
    protected function checkNameString()
    {
        if ($this->data['asset-account-name'] instanceof Account) { // third: try to find account based on name, if any.
            $this->data['asset-account-object'] = $this->data['asset-account-name'];

            return $this->data;
        }
        if (is_string($this->data['asset-account-name'])) {
            $this->data['asset-account-object'] = $this->parseNameString();

            return $this->data;
        }

        return null;
    }

    /**
     * @return Account|null
     */
    protected function parseNameString()
    {
        $accountType = $this->getAccountType();
        $accounts    = Auth::user()->accounts()->where('account_type_id', $accountType->id)->get();
        foreach ($accounts as $entry) {
            if ($entry->name == $this->data['asset-account-name']) {
                Log::debug('Found an asset account with this name (#' . $entry->id . ': ******)');

                return $entry;
            }
        }
        // create if not exists:
        $account = Account::firstOrCreateEncrypted(
            [
                'user_id'         => Auth::user()->id,
                'account_type_id' => $accountType->id,
                'name'            => $this->data['asset-account-name'],
                'iban'            => '',
                'active'          => true,
            ]
        );

        return $account;
    }
}
