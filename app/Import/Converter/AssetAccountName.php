<?php
/**
 * AssetAccountName.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Import\Converter;

use FireflyIII\Crud\Account\AccountCrudInterface;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use Log;

/**
 * Class AssetAccountName
 *
 * @package FireflyIII\Import\Converter
 */
class AssetAccountName extends BasicConverter implements ConverterInterface
{

    /**
     * @param $value
     *
     * @return Account
     */
    public function convert($value)
    {
        $value = trim($value);
        Log::debug('Going to convert using AssetAccountName', ['value' => $value]);

        if (strlen($value) === 0) {
            return new Account;
        }

        /** @var AccountCrudInterface $repository */
        $repository = app(AccountCrudInterface::class, [$this->user]);


        if (isset($this->mapping[$value])) {
            Log::debug('Found account in mapping. Should exist.', ['value' => $value, 'map' => $this->mapping[$value]]);
            $account = $repository->find(intval($this->mapping[$value]));
            if (!is_null($account->id)) {
                Log::debug('Found account by ID', ['id' => $account->id]);

                return $account;
            }
        }

        // not mapped? Still try to find it first:
        $account = $repository->findByName($value, [AccountType::ASSET]);
        if (!is_null($account->id)) {
            Log::debug('Found account by name', ['id' => $account->id]);

            return $account;
        }


        $account = $repository->store(
            ['name'   => $value, 'iban' => null, 'openingBalance' => 0, 'user' => $this->user->id, 'accountType' => 'asset', 'virtualBalance' => 0,
             'active' => true]
        );

        return $account;


    }
}