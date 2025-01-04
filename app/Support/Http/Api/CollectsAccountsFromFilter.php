<?php

/*
 * CollectsAccountsFromFilter.php
 * Copyright (c) 2024 james@firefly-iii.org.
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
 * along with this program.  If not, see https://www.gnu.org/licenses/.
 */

declare(strict_types=1);

namespace FireflyIII\Support\Http\Api;

use FireflyIII\Enums\AccountTypeEnum;
use Illuminate\Support\Collection;

trait CollectsAccountsFromFilter
{
    /**
     * TODO Duplicate function but I think it belongs here or in a separate trait
     */
    private function getAccountList(array $queryParameters): Collection
    {
        $collection = new Collection();

        // always collect from the query parameter, even when it's empty.
        if (null !== $queryParameters['accounts']) {
            foreach ($queryParameters['accounts'] as $accountId) {
                $account = $this->repository->find((int) $accountId);
                if (null !== $account) {
                    $collection->push($account);
                }
            }
        }

        // if no "preselected", and found accounts
        if ('empty' === $queryParameters['preselected'] && $collection->count() > 0) {
            return $collection;
        }
        // if no preselected, but no accounts:
        if ('empty' === $queryParameters['preselected'] && 0 === $collection->count()) {
            $defaultSet = $this->repository->getAccountsByType([AccountTypeEnum::ASSET->value, AccountTypeEnum::DEFAULT->value])->pluck('id')->toArray();
            $frontpage  = app('preferences')->get('frontpageAccounts', $defaultSet);

            if (!(is_array($frontpage->data) && count($frontpage->data) > 0)) {
                $frontpage->data = $defaultSet;
                $frontpage->save();
            }

            return $this->repository->getAccountsById($frontpage->data);
        }

        // both options are overruled by "preselected"
        if ('all' === $queryParameters['preselected']) {
            return $this->repository->getAccountsByType([AccountTypeEnum::ASSET->value, AccountTypeEnum::DEFAULT->value, AccountTypeEnum::LOAN->value, AccountTypeEnum::DEBT->value, AccountTypeEnum::MORTGAGE->value]);
        }
        if ('assets' === $queryParameters['preselected']) {
            return $this->repository->getAccountsByType([AccountTypeEnum::ASSET->value, AccountTypeEnum::DEFAULT->value]);
        }
        if ('liabilities' === $queryParameters['preselected']) {
            return $this->repository->getAccountsByType([AccountTypeEnum::LOAN->value, AccountTypeEnum::DEBT->value, AccountTypeEnum::MORTGAGE->value]);
        }

        return $collection;
    }
}
