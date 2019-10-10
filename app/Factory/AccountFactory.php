<?php

/**
 * AccountFactory.php
 * Copyright (c) 2019 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III (https://github.com/firefly-iii).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace FireflyIII\Factory;

use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
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
    use AccountServiceTrait;

    /** @var AccountRepositoryInterface */
    protected $accountRepository;
    /** @var User */
    private $user;

    /** @var array */
    private $canHaveVirtual;

    /** @var array */
    protected $validAssetFields = ['account_role', 'account_number', 'currency_id', 'BIC', 'include_net_worth'];
    /** @var array */
    protected $validCCFields = ['account_role', 'cc_monthly_payment_date', 'cc_type', 'account_number', 'currency_id', 'BIC', 'include_net_worth'];
    /** @var array */
    protected $validFields = ['account_number', 'currency_id', 'BIC', 'interest', 'interest_period', 'include_net_worth'];

    /**
     * AccountFactory constructor.
     *
     * @codeCoverageIgnore
     */
    public function __construct()
    {
        if ('testing' === config('app.env')) {
            Log::warning(sprintf('%s should not be instantiated in the TEST environment!', get_class($this)));
        }
        $this->canHaveVirtual    = [AccountType::ASSET, AccountType::DEBT, AccountType::LOAN, AccountType::MORTGAGE, AccountType::CREDITCARD];
        $this->accountRepository = app(AccountRepositoryInterface::class);
    }

    /**
     * @param array $data
     *
     * @return Account
     * @throws FireflyException
     */
    public function create(array $data): Account
    {
        $type = $this->getAccountType($data['account_type_id'], $data['account_type']);

        if (null === $type) {
            throw new FireflyException(
                sprintf('AccountFactory::create() was unable to find account type #%d ("%s").', $data['account_type_id'], $data['account_type'])
            );
        }

        $data['iban'] = $this->filterIban($data['iban'] ?? null);

        // account may exist already:
        Log::debug('Data array is as follows', $data);
        $return = $this->find($data['name'], $type->type);

        if (null === $return) {
            // create it:
            $databaseData
                = [
                'user_id'         => $this->user->id,
                'account_type_id' => $type->id,
                'name'            => $data['name'],
                'virtual_balance' => $data['virtual_balance'] ?? '0',
                'active'          => true === $data['active'],
                'iban'            => $data['iban'],
            ];

            $currency = $this->getCurrency((int)($data['currency_id'] ?? null), (string)($data['currency_code'] ?? null));
            unset($data['currency_code']);
            $data['currency_id'] = $currency->id;

            // remove virtual balance when not an asset account or a liability
            if (!in_array($type->type, $this->canHaveVirtual, true)) {
                $databaseData['virtual_balance'] = '0';
            }

            // fix virtual balance when it's empty
            if ('' === $databaseData['virtual_balance']) {
                $databaseData['virtual_balance'] = '0';
            }

            $return = Account::create($databaseData);
            $this->updateMetaData($return, $data);

            // if it can have a virtual balance, it can also have an opening balance.
            if (in_array($type->type, $this->canHaveVirtual, true)) {
                if ($this->validOBData($data)) {
                    $this->updateOBGroup($return, $data);
                }
                if (!$this->validOBData($data)) {
                    $this->deleteOBGroup($return);
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
        $type = AccountType::whereType($accountType)->first();

        return $this->user->accounts()->where('account_type_id', $type->id)->where('name', $accountName)->first();
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
        /** @var AccountType $type */
        $type   = AccountType::whereType($accountType)->first();
        $return = $this->user->accounts->where('account_type_id', $type->id)
                                       ->where('name', $accountName)->first();

        if (null === $return) {
            Log::debug('Found nothing. Will create a new one.');
            $return = $this->create(
                [
                    'user_id'         => $this->user->id,
                    'name'            => $accountName,
                    'account_type_id' => $type->id,
                    'account_type'    => null,
                    'virtual_balance' => '0',
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
     * @param int|null $accountTypeId
     * @param null|string $accountType
     *
     * @return AccountType|null
     *
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
            if (count($types) > 0) {
                Log::debug(sprintf('%d accounts in list from config', count($types)), $types);
                $result = AccountType::whereIn('type', $types)->first();
            }
            if (null === $result && null !== $accountType) {
                // try as full name:
                $result = AccountType::whereType($accountType)->first();
            }
        }
        if (null === $result) {
            Log::warning(sprintf('Found NO account type based on %d and "%s"', $accountTypeId, $accountType));
        }
        if (null !== $result) {
            Log::debug(sprintf('Found account type based on %d and "%s": "%s"', $accountTypeId, $accountType, $result->type));
        }


        return $result;

    }


}
