<?php

/**
 * AccountFactory.php
 * Copyright (c) 2019 james@firefly-iii.org
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
use FireflyIII\Services\Internal\Support\LocationServiceTrait;
use FireflyIII\Services\Internal\Update\AccountUpdateService;
use FireflyIII\User;
use Log;

/**
 * Factory to create or return accounts.
 *
 * Class AccountFactory
 */
class AccountFactory
{
    use AccountServiceTrait, LocationServiceTrait;

    protected AccountRepositoryInterface $accountRepository;
    protected array                      $validAssetFields;
    protected array                      $validCCFields;
    protected array                      $validFields;
    private array                        $canHaveVirtual;
    private User                         $user;

    /**
     * AccountFactory constructor.
     *
     * @codeCoverageIgnore
     */
    public function __construct()
    {
        $this->accountRepository = app(AccountRepositoryInterface::class);
        $this->canHaveVirtual    = config('firefly.can_have_virtual_amounts');
        $this->validAssetFields  = config('firefly.valid_asset_fields');
        $this->validCCFields     = config('firefly.valid_cc_fields');
        $this->validFields       = config('firefly.valid_account_fields');
    }

    /**
     * @param string $accountName
     * @param string $accountType
     *
     * @return Account
     * @throws FireflyException
     */
    public function findOrCreate(string $accountName, string $accountType): Account
    {
        Log::debug(sprintf('findOrCreate("%s", "%s")', $accountName, $accountType));

        $type = $this->accountRepository->getAccountTypeByType($accountType);
        if (null === $type) {
            throw new FireflyException(sprintf('Cannot find account type "%s"', $accountType));
        }
        $return = $this->user->accounts->where('account_type_id', $type->id)->where('name', $accountName)->first();

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
     * @param array $data
     *
     * @return Account
     * @throws FireflyException
     */
    public function create(array $data): Account
    {
        $type         = $this->getAccountType($data);
        $data['iban'] = $this->filterIban($data['iban'] ?? null);

        // account may exist already:
        $return = $this->find($data['name'], $type->type);

        if (null === $return) {
            $return = $this->createAccount($type, $data);
        }

        return $return;
    }

    /**
     * @param array $data
     *
     * @return AccountType|null
     */
    protected function getAccountType(array $data): ?AccountType
    {
        $accountTypeId   = array_key_exists('account_type_id', $data) ? (int)$data['account_type_id'] : 0;
        $accountTypeName = array_key_exists('account_type_name', $data) ? $data['account_type_name'] : null;
        $result          = null;
        // find by name or ID
        if ($accountTypeId > 0) {
            $result = AccountType::find($accountTypeId);
        }
        if (null !== $accountTypeName) {
            $result = $this->accountRepository->getAccountTypeByType($accountTypeName);
        }

        // try with type:
        if (null === $result) {
            $types = config(sprintf('firefly.accountTypeByIdentifier.%s', $accountTypeName)) ?? [];
            if (count($types) > 0) {
                $result = AccountType::whereIn('type', $types)->first();
            }
        }
        if (null === $result) {
            Log::warning(sprintf('Found NO account type based on %d and "%s"', $accountTypeId, $accountTypeName));
            throw new FireflyException(sprintf('AccountFactory::create() was unable to find account type #%d ("%s").', $accountTypeId, $accountTypeName));
        }
        Log::debug(sprintf('Found account type based on %d and "%s": "%s"', $accountTypeId, $accountTypeName, $result->type));

        return $result;
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
     * @param array $data
     *
     * @return Account
     */
    private function createAccount(AccountType $type, array $data): Account
    {
        $this->accountRepository->resetAccountOrder();

        // create it:
        $virtualBalance = array_key_exists('virtual_balance', $data) ? $data['virtual_balance'] : null;
        $active         = array_key_exists('active', $data) ? $data['active'] : true;
        $databaseData   = ['user_id'         => $this->user->id,
                           'account_type_id' => $type->id,
                           'name'            => $data['name'],
                           'order'           => 25000,
                           'virtual_balance' => $virtualBalance,
                           'active'          => $active,
                           'iban'            => $data['iban'],
        ];
        // fix virtual balance when it's empty
        if ('' === (string)$databaseData['virtual_balance']) {
            $databaseData['virtual_balance'] = null;
        }
        // remove virtual balance when not an asset account or a liability
        if (!in_array($type->type, $this->canHaveVirtual, true)) {
            $databaseData['virtual_balance'] = null;
        }
        // create account!
        $account = Account::create($databaseData);

        // update meta data:
        $data = $this->cleanMetaDataArray($account, $data);
        $this->storeMetaData($account, $data);

        // create opening balance
        $this->storeOpeningBalance($account, $data);

        // create notes
        $notes = array_key_exists('notes', $data) ? $data['notes'] : '';
        $this->updateNote($account, $notes);

        // create location
        $this->storeNewLocation($account, $data);

        // set order
        $this->storeOrder($account, $data);

        // refresh and return
        $account->refresh();

        return $account;
    }

    /**
     * @param Account $account
     * @param array   $data
     *
     * @return array
     */
    private function cleanMetaDataArray(Account $account, array $data): array
    {
        $currencyId   = array_key_exists('currency_id', $data) ? (int)$data['currency_id'] : 0;
        $currencyCode = array_key_exists('currency_code', $data) ? (string)$data['currency_code'] : '';
        $accountRole  = array_key_exists('account_role', $data) ? (string)$data['account_role'] : null;
        $currency     = $this->getCurrency($currencyId, $currencyCode);

        // only asset account may have a role:
        if ($account->accountType->type !== AccountType::ASSET) {
            $accountRole = '';
        }

        $data['account_role'] = $accountRole;
        $data['currency_id']  = $currency->id;

        return $data;
    }

    /**
     * @param Account $account
     * @param array   $data
     */
    private function storeMetaData(Account $account, array $data): void
    {

        $fields = $this->validFields;
        if ($account->accountType->type === AccountType::ASSET) {
            $fields = $this->validAssetFields;
        }
        if ($account->accountType->type === AccountType::ASSET && 'ccAsset' === $data['account_role']) {
            $fields = $this->validCCFields; // @codeCoverageIgnore
        }

        /** @var AccountMetaFactory $factory */
        $factory = app(AccountMetaFactory::class);
        foreach ($fields as $field) {
            // if the field is set but NULL, skip it.
            // if the field is set but "", update it.
            if (isset($data[$field]) && null !== $data[$field]) {

                // convert boolean value:
                if (is_bool($data[$field]) && false === $data[$field]) {
                    $data[$field] = 0; // @codeCoverageIgnore
                }
                if (is_bool($data[$field]) && true === $data[$field]) {
                    $data[$field] = 1; // @codeCoverageIgnore
                }

                $factory->crud($account, $field, (string)$data[$field]);
            }
        }
    }

    /**
     * @param Account $account
     * @param array   $data
     */
    private function storeOpeningBalance(Account $account, array $data)
    {
        $accountType = $account->accountType->type;

        // if it can have a virtual balance, it can also have an opening balance.
        if (in_array($accountType, $this->canHaveVirtual, true)) {
            if ($this->validOBData($data)) {
                $this->updateOBGroup($account, $data);
            }
            if (!$this->validOBData($data)) {
                $this->deleteOBGroup($account);
            }
        }
    }

    /**
     * @param Account $account
     * @param array   $data
     */
    private function storeOrder(Account $account, array $data): void
    {
        $accountType = $account->accountType->type;
        $maxOrder    = $this->accountRepository->maxOrder($accountType);
        $order       = null;
        if (!array_key_exists('order', $data)) {
            $order = $maxOrder + 1;
        }
        if (array_key_exists('order', $data)) {
            $order = (int)($data['order'] > $maxOrder ? $maxOrder + 1 : $data['order']);
            $order = 0 === $order ? $maxOrder + 1 : $order;
        }

        $updateService = app(AccountUpdateService::class);
        $updateService->setUser($account->user);
        $updateService->update($account, ['order' => $order]);
    }

    /**
     * @param User $user
     */
    public function setUser(User $user): void
    {
        $this->user = $user;
    }


}
