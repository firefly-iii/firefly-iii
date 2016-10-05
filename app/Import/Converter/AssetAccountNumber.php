<?php
/**
 * AssetAccountNumber.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Import\Converter;

use FireflyIII\Crud\Account\AccountCrudInterface;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use Log;

/**
 * Class AssetAccountNumber
 *
 * @package FireflyIII\Import\Converter
 */
class AssetAccountNumber extends BasicConverter implements ConverterInterface
{

    /**
     * @param $value
     *
     * @return Account
     */
    public function convert($value)
    {
        $value = trim($value);
        Log::debug('Going to convert using AssetAccountNumber', ['value' => $value]);

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
        $account = $repository->findByAccountNumber($value, [AccountType::ASSET]);
        if (!is_null($account->id)) {
            Log::debug('Found account by name', ['id' => $account->id]);
            $this->setCertainty(50);

            return $account;
        }

        // try to find by the name we would give it:
        $accountName = 'Asset account with number ' . e($value);
        $account     = $repository->findByName($accountName, [AccountType::ASSET]);
        if (!is_null($account->id)) {
            Log::debug('Found account by name', ['id' => $account->id]);
            $this->setCertainty(50);

            return $account;
        }


        $account = $repository->store(
            ['name'           => $accountName, 'openingBalance' => 0, 'iban' => null, 'user' => $this->user->id,
             'accountType'    => 'asset',
             'virtualBalance' => 0, 'accountNumber' => $value, 'active' => true]
        );

        if (is_null($account->id)) {
            $this->setCertainty(0);
            Log::info('Could not store new asset account by account number', $account->getErrors()->toArray());

            return new Account;
        }

        $this->setCertainty(100);

        return $account;

    }
}
