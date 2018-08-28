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
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */
/** @noinspection PhpUndefinedMethodInspection */
/** @noinspection PhpUndefinedMethodInspection */
declare(strict_types=1);

namespace FireflyIII\Generator\Report;

use FireflyIII\Models\Transaction;
use Illuminate\Support\Collection;

/**
 * Class Support.
 * @method Collection getExpenses()
 * @method Collection getIncome()
 * @codeCoverageIgnore
 */
class Support
{
    /**
     * Get the top expenses.
     *
     * @return Collection
     */
    public function getTopExpenses(): Collection
    {
        return $this->getExpenses()->sortBy('transaction_amount');
    }

    /**
     * Get the top income.
     *
     * @return Collection
     */
    public function getTopIncome(): Collection
    {
        return $this->getIncome()->sortByDesc('transaction_amount');
    }

    /**
     * Get averages from a collection.
     *
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
            ++$result[$opposingId]['count'];
            $result[$opposingId]['sum']     = bcadd($result[$opposingId]['sum'], $transaction->transaction_amount);
            $result[$opposingId]['average'] = bcdiv($result[$opposingId]['sum'], (string)$result[$opposingId]['count']);
        }

        // sort result by average:
        $average = [];
        foreach ($result as $key => $row) {
            $average[$key] = (float)$row['average'];
        }

        array_multisort($average, $sortFlag, $result);

        return $result;
    }

    /**
     * Summarize collection by earned and spent data.
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity) // it's exactly five.
     *
     * @param array $spent
     * @param array $earned
     *
     * @return array
     */
    protected function getObjectSummary(array $spent, array $earned): array
    {
        $return = [
            'sum' => [
                'spent'  => '0',
                'earned' => '0',
            ],
        ];

        /**
         * @var int
         * @var string $entry
         */
        foreach ($spent as $objectId => $entry) {
            if (!isset($return[$objectId])) {
                $return[$objectId] = ['spent' => '0', 'earned' => '0'];
            }

            $return[$objectId]['spent'] = $entry;
            $return['sum']['spent']     = bcadd($return['sum']['spent'], $entry);
        }

        /**
         * @var int
         * @var string $entry
         */
        foreach ($earned as $objectId => $entry) {
            if (!isset($return[$objectId])) {
                $return[$objectId] = ['spent' => '0', 'earned' => '0'];
            }

            $return[$objectId]['earned'] = $entry;
            $return['sum']['earned']     = bcadd($return['sum']['earned'], $entry);
        }

        return $return;
    }

    /**
     * Summarize the data by account.
     *
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
