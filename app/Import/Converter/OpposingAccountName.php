<?php
/**
 * OpposingAccountName.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Import\Converter;

use FireflyIII\Crud\Account\AccountCrudInterface;
use FireflyIII\Models\Account;
use Log;

/**
 * Class OpposingAccountName
 *
 * @package FireflyIII\Import\Converter
 */
class OpposingAccountName extends BasicConverter implements ConverterInterface
{

    /**
     * @param $value
     *
     * @return Account
     */
    public function convert($value): Account
    {
        $value = trim($value);
        Log::debug('Going to convert ', ['value' => $value]);

        if (strlen($value) === 0) {
            $value = '(empty account name)';
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
        $account = $repository->findByName($value, []);
        if (!is_null($account->id)) {
            Log::debug('Found account by name', ['id' => $account->id]);
            Log::warning(
                'The match between name and account is uncertain because the type of transactions may not have been determined.',
                ['id' => $account->id, 'name' => $value]
            );

            return $account;
        }

        $account = $repository->store(
            ['name'           => $value, 'iban' => null, 'user' => $this->user->id, 'accountType' => 'import', 'virtualBalance' => 0, 'active' => true,
             'openingBalance' => 0,
            ]
        );

        return $account;
    }
}