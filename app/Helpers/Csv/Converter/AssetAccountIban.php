<?php
declare(strict_types = 1);
namespace FireflyIII\Helpers\Csv\Converter;

use Auth;
use Carbon\Carbon;
use FireflyIII\Models\Account;

/**
 * Class AssetAccountIban
 *
 * @package FireflyIII\Helpers\Csv\Converter
 */
class AssetAccountIban extends BasicConverter implements ConverterInterface
{

    /**
     * @return Account|null
     */
    public function convert()
    {
        // is mapped? Then it's easy!
        if (isset($this->mapped[$this->index][$this->value])) {
            $account = Auth::user()->accounts()->find($this->mapped[$this->index][$this->value]);

            return $account;
        }
        if (strlen($this->value) > 0) {
            // find or create new account:
            $account = $this->findAccount();

            if (is_null($account)) {
                // create it if doesn't exist.

                $repository  = app('FireflyIII\Repositories\Account\AccountRepositoryInterface');
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

                $account = $repository->store($accountData);
            }

            return $account;
        }

        return null;
    }

    /**
     * @return Account|null
     */
    protected function findAccount()
    {
        $set = Auth::user()->accounts()->accountTypeIn(['Default account', 'Asset account'])->get(['accounts.*']);
        /** @var Account $entry */
        foreach ($set as $entry) {
            if ($entry->iban == $this->value) {

                return $entry;
            }
        }

        return null;
    }
}
