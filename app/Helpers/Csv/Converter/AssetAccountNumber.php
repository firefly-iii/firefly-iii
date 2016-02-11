<?php
/**
 * AssetAccountNumber.php
 * Copyright (C) 2016 Sander Dorigo
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

declare(strict_types = 1);
namespace FireflyIII\Helpers\Csv\Converter;

use Auth;
use Carbon\Carbon;
use FireflyIII\Models\Account;

/**
 * Class AssetAccountNumber
 *
 * @package FireflyIII\Helpers\Csv\Converter
 */
class AssetAccountNumber extends BasicConverter implements ConverterInterface
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
        // if not, search for it (or create it):
        $value = $this->value ?? '';
        if (strlen($value) > 0) {
            // find or create new account:
            $account = $this->findAccount();

            if (is_null($account->id)) {
                // create it if doesn't exist.
                $repository = app('FireflyIII\Repositories\Account\AccountRepositoryInterface');


                $accountData = [
                    'name'                   => $this->value,
                    'accountType'            => 'asset',
                    'virtualBalance'         => 0,
                    'virtualBalanceCurrency' => 1, // TODO hard coded.
                    'active'                 => true,
                    'user'                   => Auth::user()->id,
                    'iban'                   => null,
                    'accountNumber'          => $this->value,
                    'accountRole'            => null,
                    'openingBalance'         => 0,
                    'openingBalanceDate'     => new Carbon,
                    'openingBalanceCurrency' => 1, // TODO hard coded.

                ];

                $account = $repository->store($accountData);
            }

            return $account;
        }

        return null;
    }

    /**
     * @return Account
     */
    protected function findAccount(): Account
    {
        $set = Auth::user()->accounts()->with(['accountmeta'])->accountTypeIn(['Default account', 'Asset account'])->get(['accounts.*']);
        /** @var Account $entry */
        foreach ($set as $entry) {
            $accountNumber = $entry->getMeta('accountNumber');
            if ($accountNumber == $this->value) {

                return $entry;
            }
        }

        return new Account;
    }
}
