<?php
declare(strict_types = 1);
namespace FireflyIII\Helpers\Csv\Converter;

use Auth;
use Carbon\Carbon;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;

/**
 * Class AssetAccountName
 *
 * @package FireflyIII\Helpers\Csv\Converter
 */
class AssetAccountName extends BasicConverter implements ConverterInterface
{

    /**
     * @return Account|null
     */
    public function convert(): Account
    {
        $crud = app('FireflyIII\Crud\Account\AccountCrudInterface');

        if (isset($this->mapped[$this->index][$this->value])) {
            $account = $crud->find(intval($this->mapped[$this->index][$this->value]));

            return $account;
        }

        $set = $crud->getAccountsByType([AccountType::DEFAULT, AccountType::ASSET]);
        /** @var Account $entry */
        foreach ($set as $entry) {
            if ($entry->name == $this->value) {
                return $entry;
            }
        }
        $accountData = [
            'name'                   => $this->value,
            'accountType'            => 'asset',
            'virtualBalance'         => 0,
            'virtualBalanceCurrency' => 1, // hard coded.
            'active'                 => true,
            'user'                   => Auth::user()->id,
            'iban'                   => null,
            'accountNumber'          => $this->value,
            'accountRole'            => null,
            'openingBalance'         => 0,
            'openingBalanceDate'     => new Carbon,
            'openingBalanceCurrency' => 1, // hard coded.
        ];

        $account = $crud->store($accountData);

        return $account;
    }
}
