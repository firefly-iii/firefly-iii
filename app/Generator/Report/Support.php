<?php
/**
 * Support.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III.
 *
 * Firefly III is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Firefly III is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Firefly III.  If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace FireflyIII\Generator\Report;

use FireflyIII\Models\Transaction;
use Illuminate\Support\Collection;

/**
 * Class Support
 *
 * @package FireflyIII\Generator\Report\Category
 */
class Support
{
    /**
     * @return Collection
     */
    public function getTopExpenses(): Collection
    {
        $transactions = $this->getExpenses()->sortBy('transaction_amount');

        return $transactions;
    }

    /**
     * @return Collection
     */
    public function getTopIncome(): Collection
    {
        $transactions = $this->getIncome()->sortByDesc('transaction_amount');

        return $transactions;
    }

    /**
     * @param Collection $collection
     * @param int        $sortFlag
     *
     * @return array
     */
    protected function getAverages(Collection $collection, int $sortFlag): array
    {
        $result = [];
        /** @var Transaction $transaction */
        foreach ($collection as $transaction) {
            // opposing name and ID:
            $opposingId = $transaction->opposing_account_id;

            // is not set?
            if (!isset($result[$opposingId])) {
                $name                = $transaction->opposing_account_name;
                $result[$opposingId] = [
                    'name'    => $name,
                    'count'   => 1,
                    'id'      => $opposingId,
                    'average' => $transaction->transaction_amount,
                    'sum'     => $transaction->transaction_amount,
                ];
                continue;
            }
            $result[$opposingId]['count']++;
            $result[$opposingId]['sum']     = bcadd($result[$opposingId]['sum'], $transaction->transaction_amount);
            $result[$opposingId]['average'] = bcdiv($result[$opposingId]['sum'], strval($result[$opposingId]['count']));
        }

        // sort result by average:
        $average = [];
        foreach ($result as $key => $row) {
            $average[$key] = floatval($row['average']);
        }

        array_multisort($average, $sortFlag, $result);

        return $result;
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity) // it's exactly five.
     * @param array $spent
     * @param array $earned
     *
     * @return array
     */
    protected function getObjectSummary(array $spent, array $earned): array
    {
        $return = [];

        /**
         * @var int    $accountId
         * @var string $entry
         */
        foreach ($spent as $objectId => $entry) {
            if (!isset($return[$objectId])) {
                $return[$objectId] = ['spent' => 0, 'earned' => 0];
            }

            $return[$objectId]['spent'] = $entry;
        }
        unset($entry);

        /**
         * @var int    $accountId
         * @var string $entry
         */
        foreach ($earned as $objectId => $entry) {
            if (!isset($return[$objectId])) {
                $return[$objectId] = ['spent' => 0, 'earned' => 0];
            }

            $return[$objectId]['earned'] = $entry;
        }


        return $return;
    }

    /**
     * @param Collection $collection
     *
     * @return array
     */
    protected function summarizeByAccount(Collection $collection): array
    {
        $result = [];
        /** @var Transaction $transaction */
        foreach ($collection as $transaction) {
            $accountId          = $transaction->account_id;
            $result[$accountId] = $result[$accountId] ?? '0';
            $result[$accountId] = bcadd($transaction->transaction_amount, $result[$accountId]);
        }

        return $result;
    }
}
