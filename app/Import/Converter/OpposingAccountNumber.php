<?php
/**
 * OpposingAccountNumber.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Import\Converter;

use FireflyIII\Crud\Account\AccountCrudInterface;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use Log;

/**
 * Class OpposingAccountNumber
 *
 * @package FireflyIII\Import\Converter
 */
class OpposingAccountNumber extends BasicConverter implements ConverterInterface
{

    /**
     * @param $value
     *
     * @return Account
     */
    public function convert($value)
    {
        $value = trim($value);
        Log::debug('Going to convert using OpposingAccountNumber', ['value' => $value]);

        if (strlen($value) === 0) {
            $this->setCertainty(0);
            return new Account;
        }

        /** @var AccountCrudInterface $repository */
        $repository = app(AccountCrudInterface::class, [$this->user]);


        if (isset($this->mapping[$value])) {
            Log::debug('Found account in mapping. Should exist.', ['value' => $value, 'map' => $this->mapping[$value]]);
            $account = $repository->find(intval($this->mapping[$value]));
            if (!is_null($account->id)) {
                Log::debug('Found account by ID', ['id' => $account->id]);
                $this->setCertainty(100);
                return $account;
            }
        }

        // not mapped? Still try to find it first:
        $account = $repository->findByAccountNumber($value, []);
        if (!is_null($account->id)) {
            Log::debug('Found account by number', ['id' => $account->id]);
            $this->setCertainty(50);
            return $account;
        }

        // try to find by the name we would give it:
        $accountName = 'Import account with number ' . e($value);
        $account     = $repository->findByName($accountName, [AccountType::IMPORT]);
        if (!is_null($account->id)) {
            Log::debug('Found account by name', ['id' => $account->id]);
            $this->setCertainty(50);

            return $account;
        }


        $account = $repository->store(
            ['name'           => $accountName, 'openingBalance' => 0, 'iban' => null, 'user' => $this->user->id,
             'accountType'    => 'import',
             'virtualBalance' => 0, 'accountNumber' => $value, 'active' => true]
        );
        $this->setCertainty(100);
        return $account;

    }
}
