<?php

/*
 * SortsQueryResults.php
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

namespace FireflyIII\Support\JsonApi;

use FireflyIII\Models\Account;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use LaravelJsonApi\Core\Query\SortField;
use LaravelJsonApi\Core\Query\SortFields;

trait SortsQueryResults
{
    final protected function postQuerySort(string $class, Collection $collection, SortFields $parameters): Collection
    {
        Log::debug(__METHOD__);
        foreach ($parameters->all() as $field) {
            $collection = $this->sortQueryCollection($class, $collection, $field);
        }

        return $collection;
    }

    /**
     * TODO improve this.
     */
    private function sortQueryCollection(string $class, Collection $collection, SortField $field): Collection
    {
        // here be custom sort things.
        // sort by balance
        if (Account::class === $class && 'balance' === $field->name()) {
            $ascending  = $field->isAscending();
            $collection = $collection->sort(function (Account $left, Account $right) use ($ascending): int {
                $leftSum  = $this->sumBalance($left->balance);
                $rightSum = $this->sumBalance($right->balance);

                return $ascending ? bccomp($leftSum, $rightSum) : bccomp($rightSum, $leftSum);
            });
        }
        if (Account::class === $class && 'balance_difference' === $field->name()) {
            $ascending  = $field->isAscending();
            $collection = $collection->sort(function (Account $left, Account $right) use ($ascending): int {
                $leftSum  = $this->sumBalanceDifference($left->balance);
                $rightSum = $this->sumBalanceDifference($right->balance);

                return $ascending ? bccomp($leftSum, $rightSum) : bccomp($rightSum, $leftSum);
            });
        }
        // sort by account number
        if (Account::class === $class && 'account_number' === $field->name()) {
            $ascending  = $field->isAscending();
            $collection = $collection->sort(function (Account $left, Account $right) use ($ascending): int {
                $leftNr  = sprintf('%s%s', $left->iban, $left->account_number);
                $rightNr = sprintf('%s%s', $right->iban, $right->account_number);

                return $ascending ? strcmp($leftNr, $rightNr) : strcmp($rightNr, $leftNr);
            });
        }

        // sort by last activity
        if (Account::class === $class && 'last_activity' === $field->name()) {
            $ascending  = $field->isAscending();
            $collection = $collection->sort(function (Account $left, Account $right) use ($ascending): int {
                $leftNr  = (int) $left->last_activity?->format('U');
                $rightNr = (int) $right->last_activity?->format('U');
                if ($ascending) {
                    return $leftNr <=> $rightNr;
                }

                return $rightNr <=> $leftNr;
                // return (int) ($ascending ? $rightNr < $leftNr : $leftNr < $rightNr );
            });
        }

        // sort by balance difference.

        return $collection;
    }

    private function sumBalance(?array $balance): string
    {
        if (null === $balance) {
            return '-10000000000'; // minus one billion
        }
        if (0 === count($balance)) {
            return '-10000000000'; // minus one billion
        }
        $sum = '0';
        foreach ($balance as $entry) {
            $sum = bcadd($sum, $entry['balance']);
        }

        return $sum;
    }

    private function sumBalanceDifference(?array $balance): string
    {
        if (null === $balance) {
            return '-10000000000'; // minus one billion
        }
        if (0 === count($balance)) {
            return '-10000000000'; // minus one billion
        }
        $sum = '0';
        foreach ($balance as $entry) {
            $sum = bcadd($sum, $entry['balance_difference']);
        }

        return $sum;
    }
}
