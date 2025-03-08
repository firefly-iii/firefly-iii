<?php

/*
 * CurrencyRepositoryInterface.php
 * Copyright (c) 2023 james@firefly-iii.org
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

namespace FireflyIII\Repositories\UserGroups\Currency;

use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\TransactionCurrency;
use Illuminate\Support\Collection;

/**
 * Interface CurrencyRepositoryInterface
 *
 * @deprecated
 */
interface CurrencyRepositoryInterface
{
    public function currencyInUse(TransactionCurrency $currency): bool;

    /**
     * Currency is in use where exactly.
     */
    public function currencyInUseAt(TransactionCurrency $currency): ?string;

    public function destroy(TransactionCurrency $currency): bool;

    /**
     * Disables a currency
     */
    public function disable(TransactionCurrency $currency): void;

    /**
     * Enables a currency
     */
    public function enable(TransactionCurrency $currency): void;

    /**
     * Find by ID, return NULL if not found.
     */
    public function find(int $currencyId): ?TransactionCurrency;

    public function findByCode(string $currencyCode): ?TransactionCurrency;

    public function findByName(string $name): ?TransactionCurrency;

    /**
     * Find by object, ID or code. Returns user default or system default.
     */
    public function findCurrency(?int $currencyId, ?string $currencyCode): TransactionCurrency;

    /**
     * Find by object, ID or code. Returns NULL if nothing found.
     */
    public function findCurrencyNull(?int $currencyId, ?string $currencyCode): ?TransactionCurrency;

    /**
     * Get the user group's currencies.
     *
     * @return Collection<TransactionCurrency>
     */
    public function get(): Collection;

    /**
     * Get ALL currencies.
     */
    public function getAll(): Collection;

    public function getByIds(array $ids): Collection;

    public function isFallbackCurrency(TransactionCurrency $currency): bool;

    public function makeDefault(TransactionCurrency $currency): void;

    public function searchCurrency(string $search, int $limit): Collection;

    /**
     * @throws FireflyException
     */
    public function store(array $data): TransactionCurrency;

    public function update(TransactionCurrency $currency, array $data): TransactionCurrency;
}
