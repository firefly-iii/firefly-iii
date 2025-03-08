<?php

/**
 * CurrencyRepositoryInterface.php
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

namespace FireflyIII\Repositories\Currency;

use Carbon\Carbon;
use FireflyIII\Enums\UserRoleEnum;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\CurrencyExchangeRate;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Models\UserGroup;
use FireflyIII\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Collection;

/**
 * Interface CurrencyRepositoryInterface.
 *
 * @method setUserGroup(UserGroup $group)
 * @method getUserGroup()
 * @method getUser()
 * @method checkUserGroupAccess(UserRoleEnum $role)
 * @method setUser(null|Authenticatable|User $user)
 * @method setUserGroupById(int $userGroupId)
 *
 */
interface CurrencyRepositoryInterface
{
    public function find(int $currencyId): ?TransactionCurrency;
    public function searchCurrency(string $search, int $limit): Collection;
    public function isFallbackCurrency(TransactionCurrency $currency): bool;
    public function getAll(): Collection;
    public function store(array $data): TransactionCurrency;
    public function makeDefault(TransactionCurrency $currency): void;
    public function destroy(TransactionCurrency $currency): bool;
    public function enable(TransactionCurrency $currency): void;
    public function disable(TransactionCurrency $currency): void;
    public function update(TransactionCurrency $currency, array $data): TransactionCurrency;

    /**
     * @throws FireflyException
     */
    public function currencyInUse(TransactionCurrency $currency);
    /**
     * @throws FireflyException
     */
    public function currencyInUseAt(TransactionCurrency $currency): ?string;

    /**
     * Find by currency code, return NULL if unfound.
     *
     * Used in the download exchange rates cron job. Does not require user object.
     */
    public function findByCode(string $currencyCode): ?TransactionCurrency;

    /**
     * Returns the complete set of transactions but needs
     * no user object.
     *
     * Used by the download exchange rate cron job.
     */
    public function getCompleteSet(): Collection;

    /**
     * Get the user group's currencies.
     *
     * @return Collection<TransactionCurrency>
     */
    public function get(): Collection;


    /**
     * Get currency exchange rate.
     *
     * Used in the download exchange rate cron job. Needs the user object!
     */
    public function getExchangeRate(TransactionCurrency $fromCurrency, TransactionCurrency $toCurrency, Carbon $date): ?CurrencyExchangeRate;

    /**
     * Set currency exchange rate.
     *
     * Used in download exchange rate cron job. Needs the user object!
     */
    public function setExchangeRate(TransactionCurrency $fromCurrency, TransactionCurrency $toCurrency, Carbon $date, float $rate): CurrencyExchangeRate;
}
