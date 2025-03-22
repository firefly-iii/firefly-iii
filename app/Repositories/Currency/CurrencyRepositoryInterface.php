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
 */
interface CurrencyRepositoryInterface
{
    /**
     * @throws FireflyException
     */
    public function currencyInUse(TransactionCurrency $currency): bool;

    /**
     * @throws FireflyException
     */
    public function currencyInUseAt(TransactionCurrency $currency): ?string;

    public function destroy(TransactionCurrency $currency): bool;

    public function disable(TransactionCurrency $currency): void;

    public function enable(TransactionCurrency $currency): void;

    public function find(int $currencyId): ?TransactionCurrency;

    /**
     * Find by currency code, return NULL if unfound.
     *
     * Used in the download exchange rates cron job. Does not require user object.
     */
    public function findByCode(string $currencyCode): ?TransactionCurrency;

    public function findByName(string $name): ?TransactionCurrency;

    public function findCurrency(?int $currencyId, ?string $currencyCode): TransactionCurrency;

    public function findCurrencyNull(?int $currencyId, ?string $currencyCode): ?TransactionCurrency;

    /**
     * Get the user group's currencies.
     *
     * @return Collection<TransactionCurrency>
     */
    public function get(): Collection;

    public function getAll(): Collection;

    /**
     * Returns the complete set of transactions but needs
     * no user object.
     *
     * Used by the download exchange rate cron job.
     */
    public function getCompleteSet(): Collection;

    /**
     * Get currency exchange rate.
     *
     * Used in the download exchange rate cron job. Needs the user object!
     */
    public function getExchangeRate(TransactionCurrency $fromCurrency, TransactionCurrency $toCurrency, Carbon $date): ?CurrencyExchangeRate;

    public function isFallbackCurrency(TransactionCurrency $currency): bool;

    public function makeDefault(TransactionCurrency $currency): void;

    public function searchCurrency(string $search, int $limit): Collection;

    /**
     * Set currency exchange rate.
     *
     * Used in download exchange rate cron job. Needs the user object!
     */
    public function setExchangeRate(TransactionCurrency $fromCurrency, TransactionCurrency $toCurrency, Carbon $date, float $rate): CurrencyExchangeRate;

    public function store(array $data): TransactionCurrency;

    public function update(TransactionCurrency $currency, array $data): TransactionCurrency;
}
