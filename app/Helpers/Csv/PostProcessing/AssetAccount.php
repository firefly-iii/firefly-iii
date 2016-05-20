<?php
declare(strict_types = 1);
namespace FireflyIII\Helpers\Csv\PostProcessing;

use Auth;
use Carbon\Carbon;
use FireflyIII\Crud\Account\AccountCrudInterface;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
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
    public function process(): array
    {
        $result = $this->checkIdNameObject(); // has object in ID or Name?
        if (!is_null($result)) {
            return $result;
        }

        // no object? maybe asset-account-iban is a string and we can find the matching account.
        $result = $this->checkIbanString();
        if (!is_null($result)) {
            return $result;
        }

        // no object still? maybe we can find the account by name.
        $result = $this->checkNameString();
        if (!is_null($result)) {
            return $result;
        }
        // still nothing? Perhaps the account number can lead us to an account:
        $result = $this->checkAccountNumberString();
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
     * @return array|null
     */
    protected function checkAccountNumberString()
    {
        $accountNumber = $this->data['asset-account-number'] ?? null;
        if ($accountNumber instanceof Account) { // fourth: try to find account based on name, if any.
            $this->data['asset-account-object'] = $accountNumber;

            return $this->data;
        }
        if (is_string($accountNumber)) { // it's an actual account number
            $this->data['asset-account-object'] = $this->parseAccountNumberString();

            return $this->data;
        }

        return null;
    }

    /**
     * @return array|null
     */
    protected function checkIbanString()
    {
        $iban      = $this->data['asset-account-iban'] ?? '';
        $rules     = ['iban' => 'iban'];
        $check     = ['iban' => $iban];
        $validator = Validator::make($check, $rules);
        if (!$validator->fails()) {
            $this->data['asset-account-object'] = $this->parseIbanString();

            return $this->data;
        }

        return null;
    }

    /**
     * @return array
     */
    protected function checkIdNameObject()
    {
        $accountId     = $this->data['asset-account-id'] ?? null;
        $accountIban   = $this->data['asset-account-iban'] ?? null;
        $accountNumber = $this->data['asset-account-number'] ?? null;
        if ($accountId instanceof Account) { // first priority. try to find the account based on ID, if any
            $this->data['asset-account-object'] = $accountId;

            return $this->data;
        }
        if ($accountIban instanceof Account) { // second: try to find the account based on IBAN, if any.
            $this->data['asset-account-object'] = $accountIban;

            return $this->data;
        }

        if ($accountNumber instanceof Account) { // second: try to find the account based on account number, if any.
            $this->data['asset-account-object'] = $accountNumber;

            return $this->data;
        }


        return null;
    }

    /**
     * @return array|null
     */
    protected function checkNameString()
    {
        $accountName = $this->data['asset-account-name'] ?? null;
        if ($accountName instanceof Account) { // third: try to find account based on name, if any.
            $this->data['asset-account-object'] = $accountName;

            return $this->data;
        }
        if (is_string($accountName)) {
            $this->data['asset-account-object'] = $this->parseNameString();

            return $this->data;
        }

        return null;
    }

    /**
     * @return Account|null
     */
    protected function createAccount()
    {
        $accountType = $this->getAccountType();
        $name        = $this->data['asset-account-name'] ?? '';
        $iban        = $this->data['asset-account-iban'] ?? '';

        // create if not exists: // See issue #180
        $name    = strlen($name) > 0 ? $name : $iban;
        $account = Account::firstOrCreateEncrypted(
            [
                'user_id'         => Auth::user()->id,
                'account_type_id' => $accountType->id,
                'name'            => $name,
                'iban'            => $iban,
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
     * @return Account|null
     */
    protected function parseIbanString()
    {
        // create by name and/or iban.
        $iban     = $this->data['asset-account-iban'] ?? '';
        $accounts = Auth::user()->accounts()->get();
        foreach ($accounts as $entry) {
            if ($iban !== '' && $entry->iban === $iban) {

                return $entry;
            }
        }
        $account = $this->createAccount();

        return $account;
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

                return $entry;
            }
        }
        // create if not exists:
        // See issue #180
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

    /**
     * @return Account|null
     */
    private function parseAccountNumberString()
    {
        /** @var AccountCrudInterface $crud */
        $crud = app(AccountCrudInterface::class);

        $accountNumber = $this->data['asset-account-number'] ?? '';
        $accountType   = $this->getAccountType();
        $accounts      = Auth::user()->accounts()->with(['accountmeta'])->where('account_type_id', $accountType->id)->get();
        /** @var Account $entry */
        foreach ($accounts as $entry) {
            $metaFieldValue = $entry->getMeta('accountNumber');
            if ($metaFieldValue === $accountNumber && $metaFieldValue !== '') {

                return $entry;
            }
        }
        // create new if not exists and return that one:
        $accountData = [
            'name'                   => $accountNumber,
            'accountType'            => 'asset',
            'virtualBalance'         => 0,
            'virtualBalanceCurrency' => 1, // hard coded.
            'active'                 => true,
            'user'                   => Auth::user()->id,
            'iban'                   => null,
            'accountNumber'          => $accountNumber,
            'accountRole'            => null,
            'openingBalance'         => 0,
            'openingBalanceDate'     => new Carbon,
            'openingBalanceCurrency' => 1, // hard coded.
        ];
        $account     = $crud->store($accountData);

        return $account;
    }
}
