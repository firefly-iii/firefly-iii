<?php

namespace FireflyIII\Helpers\Csv\PostProcessing;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use Auth;
use FireflyIII\Validation\FireflyValidator;
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
        // first priority. try to find the account based on ID,
        // if any.
        if ($this->data['opposing-account-id'] instanceof Account) {
            $this->data['opposing-account-object'] = $this->data['opposing-account-id'];

            return $this->data;
        }

        // second: try to find the account based on IBAN, if any.
        if ($this->data['opposing-account-iban'] instanceof Account) {
            $this->data['opposing-account-object'] = $this->data['opposing-account-iban'];

            return $this->data;
        }

        $rules     = ['iban' => 'iban'];
        $check     = ['iban' => $this->data['opposing-account-iban']];
        $validator = Validator::make($check, $rules);

        if (is_string($this->data['opposing-account-iban']) && $validator->valid()) {

            $this->data['opposing-account-object'] = $this->parseIbanString();

            return $this->data;
        }

        // third: try to find account based on name, if any.
        if ($this->data['opposing-account-name'] instanceof Account) {

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