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

use FireflyIII\Models\TransactionType;

/**
 * Class Support.
 * @method array getExpenses()
 * @method array getIncome()
 *
 * @codeCoverageIgnore
 */
class Support
{
    /**
     * Get the top expenses.
     *
     * @return array
     */
    public function getTopExpenses(): array
    {
        $expenses = $this->getExpenses();
        usort($expenses, function ($a, $b) {
            return $a['amount'] <=> $b['amount'];
        });

        return $expenses;
    }

    /**
     * Get the top income.
     *
     * @return array
     */
    public function getTopIncome(): array
    {
        $income = $this->getIncome();
        usort($income, function ($a, $b) {
            return $b['amount'] <=> $a['amount'];
        });

        return $income;
    }

    /**
     * Get averages from a collection.
     *
     * @param array $array
     * @param int $sortFlag
     *
     * @return array
     */
    protected function getAverages(array $array, int $sortFlag): array
    {
        $result = [];
        /** @var array $journal */
        foreach ($array as $journal) {
            // opposing name and ID:
            $opposingId = $journal['destination_account_id'];

            // is not set?
            if (!isset($result[$opposingId])) {
                $name                = $journal['destination_account_name'];
                $result[$opposingId] = [
                    'name'    => $name,
                    'count'   => 1,
                    'id'      => $opposingId,
                    'average' => $journal['amount'],
                    'sum'     => $journal['amount'],
                ];
                continue;
            }
            ++$result[$opposingId]['count'];
            $result[$opposingId]['sum']     = bcadd($result[$opposingId]['sum'], $journal['amount']);
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
            $entry = bcmul($entry, '-1');
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
     * @param array $array
     *
     * @return array
     */
    protected function summarizeByAccount(array $array): array
    {
        $result = [];
        /** @var array $journal */
        foreach ($array as $journal) {
            $accountId          = $journal['source_account_id'] ?? 0;
            $result[$accountId] = $result[$accountId] ?? '0';
            $result[$accountId] = bcadd($journal['amount'], $result[$accountId]);
        }

        return $result;
    }

    /**
     * Summarize the data by the asset account or liability, depending on the type.
     *
     * In case of transfers, it will choose the source account.
     *
     * @param array $array
     *
     * @return array
     */
    protected function summarizeByAssetAccount(array $array): array
    {
        $result = [];
        /** @var array $journal */
        foreach ($array as $journal) {
            $accountId = 0;
            switch ($journal['transaction_type_type']) {
                case TransactionType::WITHDRAWAL:
                case TransactionType::TRANSFER:
                    $accountId = $journal['source_account_id'] ?? 0;
                    break;
                case TransactionType::DEPOSIT:
                    $accountId = $journal['destination_account_id'] ?? 0;
                    break;
            }

            $result[$accountId] = $result[$accountId] ?? '0';
            $result[$accountId] = bcadd($journal['amount'], $result[$accountId]);
        }

        return $result;
    }
}
