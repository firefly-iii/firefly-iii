<?php
/**
 * OpposingAccountIban.php
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
 * Class OpposingAccountIban
 *
 * @package FireflyIII\Import\Converter
 */
class OpposingAccountIban extends BasicConverter implements ConverterInterface
{

    /**
     * @param $value
     *
     * @return Account
     */
    public function convert($value): Account
    {
        $value = trim($value);
        Log::debug('Going to convert opposing IBAN', ['value' => $value]);

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
        $account = $repository->findByIban($value, []);
        if (!is_null($account->id)) {
            Log::debug('Found account by IBAN', ['id' => $account->id]);
            Log::notice(
                'The match between IBAN and account is uncertain because the type of transactions may not have been determined.',
                ['id' => $account->id, 'iban' => $value]
            );
            $this->setCertainty(50);

            return $account;
        }

        $account = $repository->store(
            ['name'           => $value, 'iban' => $value, 'user' => $this->user->id, 'accountType' => 'import', 'virtualBalance' => 0, 'active' => true,
             'openingBalance' => 0]
        );
        $this->setCertainty(100);

        return $account;
    }
}
