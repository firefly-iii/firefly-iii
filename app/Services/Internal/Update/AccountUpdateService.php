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

use FireflyIII\Events\UpdatedAccount;
use FireflyIII\Exceptions\FireflyException;
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
    private array                        $canHaveOpeningBalance;
    private User                         $user;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->canHaveVirtual        = config('firefly.can_have_virtual_amounts');
        $this->canHaveOpeningBalance = config('firefly.can_have_opening_balance');
        $this->validAssetFields      = config('firefly.valid_asset_fields');
        $this->validCCFields         = config('firefly.valid_cc_fields');
        $this->validFields           = config('firefly.valid_account_fields');
        $this->accountRepository     = app(AccountRepositoryInterface::class);
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

        // update opening balance.
        $this->updateCreditLiability($account, $data);

        // update note:
        if (array_key_exists('notes', $data) && null !== $data['notes']) {
            $this->updateNote($account, (string)$data['notes']);
        }

        // update preferences if inactive:
        $this->updatePreferences($account);

        event(new UpdatedAccount($account));

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
        if (array_key_exists('name', $data)) {
            $account->name = $data['name'];
        }
        if (array_key_exists('active', $data)) {
            $account->active = $data['active'];
        }
        if (array_key_exists('iban', $data)) {
            $account->iban = $data['iban'];
        }

        // set liability, but account must already be a liability.
        //$liabilityType = $data['liability_type'] ?? '';
        if ($this->isLiability($account) && array_key_exists('liability_type', $data)) {
            $type = $this->getAccountType($data['liability_type']);
            if (null !== $type) {
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
     *
     * @throws FireflyException
     */
    private function updateOpeningBalance(Account $account, array $data): void
    {

        // has valid initial balance (IB) data?
        $type = $account->accountType;
        if (in_array($type->type, $this->canHaveOpeningBalance, true)) {
            // check if is submitted as empty, that makes it valid:
            if ($this->validOBData($data) && !$this->isEmptyOBData($data)) {
                $openingBalance     = $data['opening_balance'];
                $openingBalanceDate = $data['opening_balance_date'];

                $this->updateOBGroupV2($account, $openingBalance, $openingBalanceDate);
            }

            if (!$this->validOBData($data) && $this->isEmptyOBData($data)) {
                $this->deleteOBGroup($account);
            }
        }
    }

    /**
     * @param Account $account
     * @param array   $data
     *
     * @throws FireflyException
     */
    private function updateCreditLiability(Account $account, array $data): void
    {
        $type = $account->accountType;
        $valid = config('firefly.valid_liabilities');
        if (in_array($type->type, $valid, true)) {
            // check if is submitted as empty, that makes it valid:
            if ($this->validOBData($data) && !$this->isEmptyOBData($data)) {
                $openingBalance     = $data['opening_balance'];
                $openingBalanceDate = $data['opening_balance_date'];

                $this->updateCreditTransaction($account, $openingBalance, $openingBalanceDate);
            }

            if (!$this->validOBData($data) && $this->isEmptyOBData($data)) {
                $this->deleteCreditTransaction($account);
            }
        }
    }

    /**
     * @param Account $account
     */
    private function updatePreferences(Account $account): void
    {
        $account->refresh();
        if (true === $account->active) {
            return;
        }
        $preference = app('preferences')->getForUser($account->user, 'frontpageAccounts');
        if (null === $preference) {
            return;
        }
        $array = $preference->data;
        Log::debug('Old array is: ', $array);
        Log::debug(sprintf('Must remove : %d', $account->id));
        $removeAccountId = (int)$account->id;
        $new             = [];
        foreach ($array as $value) {
            if ((int)$value !== $removeAccountId) {
                Log::debug(sprintf('Will include: %d', $value));
                $new[] = (int)$value;
            }
        }
        Log::debug('Final new array is', $new);
        app('preferences')->setForUser($account->user, 'frontpageAccounts', $new);
    }
}
