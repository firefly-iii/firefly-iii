<?php
/**
 * AccountId.php
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
use Log;

/**
 * Class AccountId
 *
 * @package FireflyIII\Import\Converter
 */
class AccountId extends BasicConverter implements ConverterInterface
{

    /**
     * @param $value
     *
     * @return Account
     */
    public function convert($value)
    {
        $value = intval(trim($value));
        Log::debug('Going to convert using AssetAccountId', ['value' => $value]);
        if ($value === 0) {
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
        $account = $repository->find($value);// not mapped? Still try to find it first:
        if (!is_null($account->id)) {
            $this->setCertainty(90);
            Log::debug('Found account by ID ', ['id' => $account->id]);

            return $account;
        }
        $this->setCertainty(0); // should not really happen. If the ID does not match FF, what is FF supposed to do?

        return new Account;

    }
}
