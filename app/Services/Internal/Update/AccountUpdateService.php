<?php
/**
 * AccountUpdateService.php
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

namespace FireflyIII\Services\Internal\Update;

use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\Location;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Services\Internal\Support\AccountServiceTrait;
use FireflyIII\User;
use Log;

/**
 * Class AccountUpdateService
 * TODO this is a mess.
 */
class AccountUpdateService
{
    use AccountServiceTrait;

    protected AccountRepositoryInterface $accountRepository;
    protected array                      $validAssetFields;
    protected array                      $validCCFields;
    protected array                      $validFields;
    private array                        $canHaveVirtual;
    private User                         $user;

    /**
     * Constructor.
     */
    public function __construct()
    {
        // TODO move to configuration.
        $this->canHaveVirtual    = [AccountType::ASSET, AccountType::DEBT, AccountType::LOAN, AccountType::MORTGAGE, AccountType::CREDITCARD];
        $this->accountRepository = app(AccountRepositoryInterface::class);
        $this->validAssetFields  = ['account_role', 'account_number', 'currency_id', 'BIC', 'include_net_worth'];
        $this->validCCFields     = ['account_role', 'cc_monthly_payment_date', 'cc_type', 'account_number', 'currency_id', 'BIC', 'include_net_worth'];
        $this->validFields       = ['account_number', 'currency_id', 'BIC', 'interest', 'interest_period', 'include_net_worth'];
    }

    /**
     * @param User $user
     */
    public function setUser(User $user): void
    {
        $this->user = $user;
    }

    /**
     * Update account data.
     *
     * @param Account $account
     * @param array   $data
     *
     * @return Account
     */
    public function update(Account $account, array $data): Account
    {
        Log::debug(sprintf('Now in %s', __METHOD__));
        $this->accountRepository->setUser($account->user);
        $this->user = $account->user;
        $account    = $this->updateAccount($account, $data);
        $account    = $this->updateAccountOrder($account, $data);

        // find currency, or use default currency instead.
        if (array_key_exists('currency_id', $data) || array_key_exists('currency_code', $data)) {
            $currency = $this->getCurrency((int)($data['currency_id'] ?? null), (string)($data['currency_code'] ?? null));
            unset($data['currency_code'], $data['currency_id']);
            $data['currency_id'] = $currency->id;
        }

        // update all meta data:
        $this->updateMetaData($account, $data);

        // update, delete or create location:
        $this->updateLocation($account, $data);

        // update opening balance.
        $this->updateOpeningBalance($account, $data);

        // update note:
        if (isset($data['notes']) && null !== $data['notes']) {
            $this->updateNote($account, (string)$data['notes']);
        }

        // update preferences if inactive:
        $this->updatePreferences($account, $data);

        return $account;
    }

    /**
     * @param Account $account
     * @param array   $data
     *
     * @return Account
     */
    private function updateAccount(Account $account, array $data): Account
    {
        // update the account itself:
        if(array_key_exists('name', $data)) {
            $account->name   = $data['name'];
        }
        if(array_key_exists('active', $data)) {
            $account->active = $data['active'];
        }
        if(array_key_exists('iban', $data)) {
            $account->iban   = $data['iban'];
        }

        // set liability, but account must already be a liability.
        //$liabilityType = $data['liability_type'] ?? '';
        if ($this->isLiability($account) && array_key_exists('liability_type', $data)) {
            $type                     = $this->getAccountType($data['liability_type']);
            if(null !== $type) {
                $account->account_type_id = $type->id;
            }
        }

        // update virtual balance (could be set to zero if empty string).
        if (array_key_exists('virtual_balance', $data) && null !== $data['virtual_balance']) {
            $account->virtual_balance = '' === trim($data['virtual_balance']) ? '0' : $data['virtual_balance'];
        }

        $account->save();

        return $account;
    }

    /**
     * @param Account $account
     *
     * @return bool
     */
    private function isLiability(Account $account): bool
    {
        $type = $account->accountType->type;

        return in_array($type, [AccountType::DEBT, AccountType::LOAN, AccountType::MORTGAGE], true);
    }

    /**
     * @param string $type
     *
     * @return bool
     */
    private function isLiabilityType(string $type): bool
    {
        if ('' === $type) {
            return false;
        }

        return 1 === AccountType::whereIn('type', [AccountType::DEBT, AccountType::LOAN, AccountType::MORTGAGE])->where('type', ucfirst($type))->count();
    }

    /**
     * @param string $type
     */
    private function getAccountType(string $type): AccountType
    {
        return AccountType::whereType(ucfirst($type))->first();
    }

    /**
     * @param Account $account
     * @param array   $data
     *
     * @return Account
     */
    public function updateAccountOrder(Account $account, array $data): Account
    {
        // skip if no order info
        if (!array_key_exists('order', $data) || $data['order'] === $account->order) {
            Log::debug(sprintf('Account order will not be touched because its not set or already at %d.', $account->order));

            return $account;
        }
        // skip if not of orderable type.
        $type = $account->accountType->type;
        if (!in_array($type, [AccountType::ASSET, AccountType::MORTGAGE, AccountType::LOAN, AccountType::DEBT], true)) {
            Log::debug('Will not change order of this account.');

            return $account;
        }
        // get account type ID's because a join and an update is hard:
        $oldOrder = (int)$account->order;
        $newOrder = $data['order'];
        Log::debug(sprintf('Order is set to be updated from %s to %s', $oldOrder, $newOrder));
        $list = $this->getTypeIds([AccountType::MORTGAGE, AccountType::LOAN, AccountType::DEBT]);
        if (in_array($type, [AccountType::ASSET], true)) {
            $list = $this->getTypeIds([AccountType::ASSET]);
        }

        if ($newOrder > $oldOrder) {
            $this->user->accounts()->where('accounts.order', '<=', $newOrder)->where('accounts.order', '>', $oldOrder)
                       ->where('accounts.id', '!=', $account->id)
                       ->whereIn('accounts.account_type_id', $list)
                       ->decrement('order', 1);
            $account->order = $newOrder;
            Log::debug(sprintf('Order of account #%d ("%s") is now %d', $account->id, $account->name, $newOrder));
            $account->save();

            return $account;
        }

        $this->user->accounts()->where('accounts.order', '>=', $newOrder)->where('accounts.order', '<', $oldOrder)
                   ->where('accounts.id', '!=', $account->id)
                   ->whereIn('accounts.account_type_id', $list)
                   ->increment('order', 1);
        $account->order = $newOrder;
        Log::debug(sprintf('Order of account #%d ("%s") is now %d', $account->id, $account->name, $newOrder));
        $account->save();

        return $account;
    }

    private function getTypeIds(array $array): array
    {
        $return = [];
        /** @var string $type */
        foreach ($array as $type) {
            /** @var AccountType $type */
            $type     = AccountType::whereType($type)->first();
            $return[] = (int)$type->id;
        }

        return $return;
    }

    /**
     * @param Account $account
     * @param array   $data
     */
    private function updateLocation(Account $account, array $data): void
    {
        $updateLocation = $data['update_location'] ?? false;
        // location must be updated?
        if (true === $updateLocation) {
            // if all set to NULL, delete
            if (null === $data['latitude'] && null === $data['longitude'] && null === $data['zoom_level']) {
                $account->locations()->delete();
            }

            // otherwise, update or create.
            if (!(null === $data['latitude'] && null === $data['longitude'] && null === $data['zoom_level'])) {
                $location = $this->accountRepository->getLocation($account);
                if (null === $location) {
                    $location = new Location;
                    $location->locatable()->associate($account);
                }

                $location->latitude   = $data['latitude'] ?? config('firefly.default_location.latitude');
                $location->longitude  = $data['longitude'] ?? config('firefly.default_location.longitude');
                $location->zoom_level = $data['zoom_level'] ?? config('firefly.default_location.zoom_level');
                $location->save();
            }
        }
    }

    /**
     * @param Account $account
     * @param array   $data
     */
    private function updateOpeningBalance(Account $account, array $data): void
    {

        // has valid initial balance (IB) data?
        $type = $account->accountType;
        // if it can have a virtual balance, it can also have an opening balance.

        if (in_array($type->type, $this->canHaveVirtual, true)) {

            // check if is submitted as empty, that makes it valid:
            if ($this->validOBData($data) && !$this->isEmptyOBData($data)) {
                $this->updateOBGroup($account, $data);
            }

            if (!$this->validOBData($data) && $this->isEmptyOBData($data)) {
                $this->deleteOBGroup($account);
            }
        }
    }

    /**
     * @param Account $account
     * @param array   $data
     */
    private function updatePreferences(Account $account, array $data): void
    {
        Log::debug(sprintf('Now in updatePreferences(#%d)', $account->id));
        if (array_key_exists('active', $data) && (false === $data['active'] || 0 === $data['active'])) {
            Log::debug('Account was marked as inactive.');
            $preference = app('preferences')->getForUser($account->user, 'frontpageAccounts');
            if (null !== $preference) {
                $removeAccountId = (int)$account->id;
                $array           = $preference->data;
                Log::debug('Current list of accounts: ', $array);
                Log::debug(sprintf('Going to remove account #%d', $removeAccountId));
                $filtered = array_filter(
                    $array, function ($accountId) use ($removeAccountId) {
                    return (int)$accountId !== $removeAccountId;
                }
                );
                Log::debug('Left with accounts', array_values($filtered));
                app('preferences')->setForUser($account->user, 'frontpageAccounts', array_values($filtered));
                app('preferences')->forget($account->user, 'frontpageAccounts');

                return;
            }
            Log::debug("Found no frontpageAccounts preference, do nothing.");

            return;
        }
        Log::debug('Account was not marked as inactive, do nothing.');
    }
}
