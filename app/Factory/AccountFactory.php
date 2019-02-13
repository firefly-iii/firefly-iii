<?php

/**
 * AccountFactory.php
 * Copyright (c) 2018 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III.
 *
 * Firefly III is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Firefly III is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */

/** @noinspection PhpDynamicAsStaticMethodCallInspection */
/** @noinspection PhpUndefinedMethodInspection */

declare(strict_types=1);

namespace FireflyIII\Factory;

use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Services\Internal\Support\AccountServiceTrait;
use FireflyIII\User;
use Log;

/**
 * Factory to create or return accounts.
 *
 * Class AccountFactory
 */
class AccountFactory
{
    /** @var User */
    private $user;

    use AccountServiceTrait;

    /**
     * AccountFactory constructor.
     *
     * @codeCoverageIgnore
     */
    public function __construct()
    {
        if ('testing' === config('app.env')) {
            Log::warning(sprintf('%s should not be instantiated in the TEST environment!', \get_class($this)));
        }
    }

    /**
     * @param array $data
     *
     * @return Account
     * @throws FireflyException
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function create(array $data): Account
    {
        $type = $this->getAccountType($data['account_type_id'], $data['accountType']);

        if (null === $type) {
            throw new FireflyException(
                sprintf('AccountFactory::create() was unable to find account type #%d ("%s").', $data['account_type_id'], $data['accountType'])
            );
        }

        $data['iban'] = $this->filterIban($data['iban']);

        // account may exist already:
        $return = $this->find($data['name'], $type->type);

        if (null === $return) {
            // create it:
            $databaseData
                = [
                'user_id'         => $this->user->id,
                'account_type_id' => $type->id,
                'name'            => $data['name'],
                'virtual_balance' => $data['virtualBalance'] ?? '0',
                'active'          => true === $data['active'],
                'iban'            => $data['iban'],
            ];

            // find currency, or use default currency instead.
            /** @var TransactionCurrencyFactory $factory */
            $factory = app(TransactionCurrencyFactory::class);
            /** @var TransactionCurrency $currency */
            $currency = $factory->find((int)($data['currency_id'] ?? null), (string)($data['currency_code'] ?? null));

            if (null === $currency) {
                // use default currency:
                $currency = app('amount')->getDefaultCurrencyByUser($this->user);
            }
            $currency->enabled = true;
            $currency->save();

            unset($data['currency_code']);
            $data['currency_id'] = $currency->id;
            // remove virtual balance when not an asset account or a liability
            $canHaveVirtual = [AccountType::ASSET, AccountType::DEBT, AccountType::LOAN, AccountType::MORTGAGE, AccountType::CREDITCARD];
            if (!\in_array($type->type, $canHaveVirtual, true)) {
                $databaseData['virtual_balance'] = '0';
            }

            // fix virtual balance when it's empty
            if ('' === $databaseData['virtual_balance']) {
                $databaseData['virtual_balance'] = '0';
            }

            $return = Account::create($databaseData);
            $this->updateMetaData($return, $data);

            if (\in_array($type->type, $canHaveVirtual, true)) {
                if ($this->validIBData($data)) {
                    $this->updateIB($return, $data);
                }
                if (!$this->validIBData($data)) {
                    $this->deleteIB($return);
                }
            }
            $this->updateNote($return, $data['notes'] ?? '');
        }

        return $return;
    }

    /**
     * @param string $accountName
     * @param string $accountType
     *
     * @return Account|null
     */
    public function find(string $accountName, string $accountType): ?Account
    {
        $type     = AccountType::whereType($accountType)->first();
        $accounts = $this->user->accounts()->where('account_type_id', $type->id)->get(['accounts.*']);
        $return   = null;
        /** @var Account $object */
        foreach ($accounts as $object) {
            if ($object->name === $accountName) {
                $return = $object;
                break;
            }
        }

        return $return;
    }

    /**
     *
     * @param string $accountName
     * @param string $accountType
     *
     * @return Account
     * @throws FireflyException
     */
    public function findOrCreate(string $accountName, string $accountType): Account
    {
        Log::debug(sprintf('Searching for "%s" of type "%s"', $accountName, $accountType));
        $type     = AccountType::whereType($accountType)->first();
        $accounts = $this->user->accounts()->where('account_type_id', $type->id)->get(['accounts.*']);
        $return   = null;

        Log::debug(sprintf('Account type is #%d', $type->id));

        /** @var Account $object */
        foreach ($accounts as $object) {
            if ($object->name === $accountName) {
                Log::debug(sprintf('Found account #%d "%s".', $object->id, $object->name));
                $return = $object;
                break;
            }
        }
        if (null === $return) {
            Log::debug('Found nothing. Will create a new one.');
            $return = $this->create(
                [
                    'user_id'         => $this->user->id,
                    'name'            => $accountName,
                    'account_type_id' => $type->id,
                    'accountType'     => null,
                    'virtualBalance'  => '0',
                    'iban'            => null,
                    'active'          => true,
                ]
            );
        }

        return $return;
    }

    /**
     * @param User $user
     */
    public function setUser(User $user): void
    {
        $this->user = $user;
    }

    /**
     * @param int|null    $accountTypeId
     * @param null|string $accountType
     *
     * @return AccountType|null
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function getAccountType(?int $accountTypeId, ?string $accountType): ?AccountType
    {
        $accountTypeId = (int)$accountTypeId;
        $result        = null;
        if ($accountTypeId > 0) {
            $result = AccountType::find($accountTypeId);
        }
        if (null === $result) {
            Log::debug(sprintf('No account type found by ID, continue search for "%s".', $accountType));
            /** @var array $types */
            $types = config('firefly.accountTypeByIdentifier.' . $accountType) ?? [];
            if (\count($types) > 0) {
                Log::debug(sprintf('%d accounts in list from config', \count($types)), $types);
                $result = AccountType::whereIn('type', $types)->first();
            }
            if (null === $result && null !== $accountType) {
                // try as full name:
                $result = AccountType::whereType($accountType)->first();
            }
        }

        return $result;

    }

}
