<?php
/*
 * AccountEnricher.php
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

namespace FireflyIII\Support\JsonApi\Enrichments;

use Carbon\Carbon;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Repositories\UserGroups\Account\AccountRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class AccountEnrichment implements EnrichmentInterface
{

    private Collection $collection;

    #[\Override] public function enrich(Collection $collection): Collection
    {
        $this->collection         = $collection;

        // do everything here:
        $this->getLastActivity();
        $this->getMetaBalances();

        return $this->collection;
    }

    /**
     * TODO this method refers to a single-use method inside Steam that could be moved here.
     * @return void
     */
    private function getLastActivity(): void
    {
        /** @var AccountRepositoryInterface $accountRepository */
        $accountRepository = app(AccountRepositoryInterface::class);
        $lastActivity      = $accountRepository->getLastActivity($this->collection);
        foreach ($lastActivity as $row) {
            $this->collection->where('id', $row['account_id'])->first()->last_activity = Carbon::parse($row['date_max'], config('app.timezone'));
        }
    }

    /**
     * TODO this method refers to a single-use method inside Steam that could be moved here.
     * @return void
     */
    private function getMetaBalances(): void
    {
        try {
            $array = app('steam')->balancesByAccountsConverted($this->collection, today());
        } catch (FireflyException $e) {
            Log::error(sprintf('Could not load balances: %s', $e->getMessage()));
            return;
        }
        foreach ($array as $accountId => $row) {
            $this->collection->where('id', $accountId)->first()->balance = $row['balance'];
            $this->collection->where('id', $accountId)->first()->native_balance = $row['native_balance'];
        }
    }
}
