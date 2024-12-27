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

use FireflyIII\Events\StoredAccount;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Services\Internal\Support\AccountServiceTrait;
use FireflyIII\Services\Internal\Support\LocationServiceTrait;
use FireflyIII\Services\Internal\Update\AccountUpdateService;
use FireflyIII\User;
use Illuminate\Support\Facades\Log;

/**
 * Factory to create or return accounts.
 *
 * Class AccountFactory
 */
class AccountFactory
{
    use AccountServiceTrait;
    use LocationServiceTrait;

    protected AccountRepositoryInterface $accountRepository;
    protected array                      $validAssetFields;
    protected array                      $validCCFields;
    protected array                      $validFields;
    private array                        $canHaveOpeningBalance;
    private array                        $canHaveVirtual;
    private User                         $user;

    /**
     * AccountFactory constructor.
     */
    public function __construct()
    {
        $this->accountRepository     = app(AccountRepositoryInterface::class);
        $this->canHaveVirtual        = config('firefly.can_have_virtual_amounts');
        $this->canHaveOpeningBalance = config('firefly.can_have_opening_balance');
        $this->validAssetFields      = config('firefly.valid_asset_fields');
        $this->validCCFields         = config('firefly.valid_cc_fields');
        $this->validFields           = config('firefly.valid_account_fields');
    }

    /**
     * @throws FireflyException
     */
    public function findOrCreate(string $accountName, string $accountType): Account
    {
        app('log')->debug(sprintf('findOrCreate("%s", "%s")', $accountName, $accountType));

        $type   = $this->accountRepository->getAccountTypeByType($accountType);
        if (null === $type) {
            throw new FireflyException(sprintf('Cannot find account type "%s"', $accountType));
        }
        $return = $this->user->accounts->where('account_type_id', $type->id)->where('name', $accountName)->first();

        if (null === $return) {
            app('log')->debug('Found nothing. Will create a new one.');
            $return = $this->create(
                [
                    'user_id'           => $this->user->id,
                    'user_group_id'     => $this->user->user_group_id,
                    'name'              => $accountName,
                    'account_type_id'   => $type->id,
                    'account_type_name' => null,
                    'virtual_balance'   => '0',
                    'iban'              => null,
                    'active'            => true,
                ]
            );
        }

        return $return;
    }

    /**
     * @throws FireflyException
     */
    public function create(array $data): Account
    {
        app('log')->debug('Now in AccountFactory::create()');
        $type         = $this->getAccountType($data);
        $data['iban'] = $this->filterIban($data['iban'] ?? null);

        // account may exist already:
        $return       = $this->find($data['name'], $type->type);

        if (null !== $return) {
            return $return;
        }

        $return       = $this->createAccount($type, $data);

        event(new StoredAccount($return));

        return $return;
    }

    /**
     * @throws FireflyException
     */
    protected function getAccountType(array $data): ?AccountType
    {
        $accountTypeId   = array_key_exists('account_type_id', $data) ? (int) $data['account_type_id'] : 0;
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
            if (0 !== count($types)) {
                $result = AccountType::whereIn('type', $types)->first();
            }
        }
        if (null === $result) {
            app('log')->warning(sprintf('Found NO account type based on %d and "%s"', $accountTypeId, $accountTypeName));

            throw new FireflyException(sprintf('AccountFactory::create() was unable to find account type #%d ("%s").', $accountTypeId, $accountTypeName));
        }
        app('log')->debug(sprintf('Found account type based on %d and "%s": "%s"', $accountTypeId, $accountTypeName, $result->type));

        return $result;
    }

    public function find(string $accountName, string $accountType): ?Account
    {
        app('log')->debug(sprintf('Now in AccountFactory::find("%s", "%s")', $accountName, $accountType));
        $type = AccountType::whereType($accountType)->first();

        // @var Account|null
        return $this->user->accounts()->where('account_type_id', $type->id)->where('name', $accountName)->first();
    }

    /**
     * @throws FireflyException
     */
    private function createAccount(AccountType $type, array $data): Account
    {
        $this->accountRepository->resetAccountOrder();

        // create it:
        $virtualBalance = array_key_exists('virtual_balance', $data) ? $data['virtual_balance'] : null;
        $active         = array_key_exists('active', $data) ? $data['active'] : true;
        $databaseData   = [
            'user_id'         => $this->user->id,
            'user_group_id'   => $this->user->user_group_id,
            'account_type_id' => $type->id,
            'name'            => $data['name'],
            'order'           => 25000,
            'virtual_balance' => $virtualBalance,
            'active'          => $active,
            'iban'            => $data['iban'],
        ];
        // fix virtual balance when it's empty
        if ('' === (string) $databaseData['virtual_balance']) {
            $databaseData['virtual_balance'] = null;
        }
        // remove virtual balance when not an asset account
        if (!in_array($type->type, $this->canHaveVirtual, true)) {
            $databaseData['virtual_balance'] = null;
        }
        // create account!
        $account        = Account::create($databaseData);
        Log::channel('audit')->info(sprintf('Account #%d ("%s") has been created.', $account->id, $account->name));

        // update meta data:
        $data           = $this->cleanMetaDataArray($account, $data);
        $this->storeMetaData($account, $data);

        // create opening balance (only asset accounts)
        try {
            $this->storeOpeningBalance($account, $data);
        } catch (FireflyException $e) {
            app('log')->error($e->getMessage());
            app('log')->error($e->getTraceAsString());
        }

        // create credit liability data (only liabilities)
        try {
            $this->storeCreditLiability($account, $data);
        } catch (FireflyException $e) {
            app('log')->error($e->getMessage());
            app('log')->error($e->getTraceAsString());
        }

        // create notes
        $notes          = array_key_exists('notes', $data) ? $data['notes'] : '';
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
     * @throws FireflyException
     */
    private function cleanMetaDataArray(Account $account, array $data): array
    {
        $currencyId           = array_key_exists('currency_id', $data) ? (int) $data['currency_id'] : 0;
        $currencyCode         = array_key_exists('currency_code', $data) ? (string) $data['currency_code'] : '';
        $accountRole          = array_key_exists('account_role', $data) ? (string) $data['account_role'] : null;
        $currency             = $this->getCurrency($currencyId, $currencyCode);

        // only asset account may have a role:
        if (AccountType::ASSET !== $account->accountType->type) {
            $accountRole = '';
        }
        // only liability may have direction:
        if (array_key_exists('liability_direction', $data) && !in_array($account->accountType->type, config('firefly.valid_liabilities'), true)) {
            $data['liability_direction'] = null;
        }
        $data['account_role'] = $accountRole;
        $data['currency_id']  = $currency->id;

        return $data;
    }

    private function storeMetaData(Account $account, array $data): void
    {
        $fields  = $this->validFields;
        if (AccountType::ASSET === $account->accountType->type) {
            $fields = $this->validAssetFields;
        }
        if (AccountType::ASSET === $account->accountType->type && 'ccAsset' === $data['account_role']) {
            $fields = $this->validCCFields;
        }

        // remove currency_id if necessary.
        $type    = $account->accountType->type;
        $list    = config('firefly.valid_currency_account_types');
        if (!in_array($type, $list, true)) {
            $pos = array_search('currency_id', $fields, true);
            if (false !== $pos) {
                unset($fields[$pos]);
            }
        }

        /** @var AccountMetaFactory $factory */
        $factory = app(AccountMetaFactory::class);
        foreach ($fields as $field) {
            // if the field is set but NULL, skip it.
            // if the field is set but "", update it.
            if (array_key_exists($field, $data) && null !== $data[$field]) {
                // convert boolean value:
                if (is_bool($data[$field]) && false === $data[$field]) {
                    $data[$field] = 0;
                }
                if (true === $data[$field]) {
                    $data[$field] = 1;
                }

                $factory->crud($account, $field, (string) $data[$field]);
            }
        }
    }

    /**
     * @throws FireflyException
     */
    private function storeOpeningBalance(Account $account, array $data): void
    {
        $accountType = $account->accountType->type;

        if (in_array($accountType, $this->canHaveOpeningBalance, true)) {
            if ($this->validOBData($data)) {
                $openingBalance     = $data['opening_balance'];
                $openingBalanceDate = $data['opening_balance_date'];
                $this->updateOBGroupV2($account, $openingBalance, $openingBalanceDate);
            }
            if (!$this->validOBData($data)) {
                $this->deleteOBGroup($account);
            }
        }
    }

    /**
     * @throws FireflyException
     */
    private function storeCreditLiability(Account $account, array $data): void
    {
        app('log')->debug('storeCreditLiability');
        $account->refresh();
        $accountType = $account->accountType->type;
        $direction   = $this->accountRepository->getMetaValue($account, 'liability_direction');
        $valid       = config('firefly.valid_liabilities');
        if (in_array($accountType, $valid, true)) {
            app('log')->debug('Is a liability with credit ("i am owed") direction.');
            if ($this->validOBData($data)) {
                app('log')->debug('Has valid CL data.');
                $openingBalance     = $data['opening_balance'];
                $openingBalanceDate = $data['opening_balance_date'];
                // store credit transaction.
                $this->updateCreditTransaction($account, $direction, $openingBalance, $openingBalanceDate);
            }
            if (!$this->validOBData($data)) {
                app('log')->debug('Does NOT have valid CL data, deletr any CL transaction.');
                $this->deleteCreditTransaction($account);
            }
        }
    }

    /**
     * @throws FireflyException
     */
    private function storeOrder(Account $account, array $data): void
    {
        $accountType   = $account->accountType->type;
        $maxOrder      = $this->accountRepository->maxOrder($accountType);
        $order         = null;
        if (!array_key_exists('order', $data)) {
            $order = $maxOrder + 1;
        }
        if (array_key_exists('order', $data)) {
            $order = (int) ($data['order'] > $maxOrder ? $maxOrder + 1 : $data['order']);
            $order = 0 === $order ? $maxOrder + 1 : $order;
        }

        $updateService = app(AccountUpdateService::class);
        $updateService->setUser($account->user);
        $updateService->update($account, ['order' => $order]);
    }

    public function setUser(User $user): void
    {
        $this->user = $user;
        $this->accountRepository->setUser($user);
    }
}
