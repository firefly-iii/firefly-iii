<?php

/*
 * CategoryReportGenerator.php
 * Copyright (c) 2021 james@firefly-iii.org
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

namespace FireflyIII\Support\Report\Category;

use Carbon\Carbon;
use FireflyIII\Repositories\Category\NoCategoryRepositoryInterface;
use FireflyIII\Repositories\Category\OperationsRepositoryInterface;
use FireflyIII\User;
use Illuminate\Support\Collection;

/**
 * Class CategoryReportGenerator
 */
class CategoryReportGenerator
{
    private Collection                    $accounts;
    private Carbon                        $end;
    private NoCategoryRepositoryInterface $noCatRepository;
    private OperationsRepositoryInterface $opsRepository;
    private array                         $report;
    private Carbon                        $start;

    /**
     * CategoryReportGenerator constructor.
     */
    public function __construct()
    {
        $this->opsRepository   = app(OperationsRepositoryInterface::class);
        $this->noCatRepository = app(NoCategoryRepositoryInterface::class);
    }

    public function getReport(): array
    {
        return $this->report;
    }

    /**
     * Generate the array required to show the overview of categories on the
     * default report.
     */
    public function operations(): void
    {
        $earnedWith = $this->opsRepository->listIncome($this->start, $this->end, $this->accounts);
        $spentWith  = $this->opsRepository->listExpenses($this->start, $this->end, $this->accounts);

        // also transferred out and transferred into these accounts in this category:
        $transferredIn  = $this->opsRepository->listTransferredIn($this->start, $this->end, $this->accounts);
        $transferredOut = $this->opsRepository->listTransferredOut($this->start, $this->end, $this->accounts);

        $earnedWithout = $this->noCatRepository->listIncome($this->start, $this->end, $this->accounts);
        $spentWithout  = $this->noCatRepository->listExpenses($this->start, $this->end, $this->accounts);

        $this->report = [
            'categories' => [],
            'sums'       => [],
        ];

        // needs four for-each loops.
        foreach ([$earnedWith, $spentWith, $earnedWithout, $spentWithout, $transferredIn, $transferredOut] as $data) {
            $this->processOpsArray($data);
        }
    }

    /**
     * Process one of the spent arrays from the operations method.
     */
    private function processOpsArray(array $data): void
    {
        /**
         * @var int   $currencyId
         * @var array $currencyRow
         */
        foreach ($data as $currencyId => $currencyRow) {
            $this->processCurrencyArray($currencyId, $currencyRow);
        }
    }

    private function processCurrencyArray(int $currencyId, array $currencyRow): void
    {
        $this->report['sums'][$currencyId] ??= [
            'spent'                   => '0',
            'earned'                  => '0',
            'sum'                     => '0',
            'currency_id'             => $currencyRow['currency_id'],
            'currency_symbol'         => $currencyRow['currency_symbol'],
            'currency_name'           => $currencyRow['currency_name'],
            'currency_code'           => $currencyRow['currency_code'],
            'currency_decimal_places' => $currencyRow['currency_decimal_places'],
        ];

        /**
         * @var int   $categoryId
         * @var array $categoryRow
         */
        foreach ($currencyRow['categories'] as $categoryId => $categoryRow) {
            $this->processCategoryRow($currencyId, $currencyRow, $categoryId, $categoryRow);
        }
    }

    private function processCategoryRow(int $currencyId, array $currencyRow, int $categoryId, array $categoryRow): void
    {
        $key                              = sprintf('%s-%s', $currencyId, $categoryId);
        $this->report['categories'][$key] ??= [
            'id'                      => $categoryId,
            'title'                   => $categoryRow['name'],
            'currency_id'             => $currencyRow['currency_id'],
            'currency_symbol'         => $currencyRow['currency_symbol'],
            'currency_name'           => $currencyRow['currency_name'],
            'currency_code'           => $currencyRow['currency_code'],
            'currency_decimal_places' => $currencyRow['currency_decimal_places'],
            'spent'                   => '0',
            'earned'                  => '0',
            'sum'                     => '0',
        ];
        // loop journals:
        foreach ($categoryRow['transaction_journals'] as $journal) {
            // sum of sums
            $this->report['sums'][$currencyId]['sum'] = bcadd($this->report['sums'][$currencyId]['sum'], $journal['amount']);
            // sum of spent:
            $this->report['sums'][$currencyId]['spent'] = -1 === bccomp($journal['amount'], '0') ? bcadd(
                $this->report['sums'][$currencyId]['spent'],
                $journal['amount']
            ) : $this->report['sums'][$currencyId]['spent'];
            // sum of earned
            $this->report['sums'][$currencyId]['earned'] = 1 === bccomp($journal['amount'], '0') ? bcadd(
                $this->report['sums'][$currencyId]['earned'],
                $journal['amount']
            ) : $this->report['sums'][$currencyId]['earned'];

            // sum of category
            $this->report['categories'][$key]['sum'] = bcadd($this->report['categories'][$key]['sum'], $journal['amount']);
            // total spent in category
            $this->report['categories'][$key]['spent'] = -1 === bccomp($journal['amount'], '0') ? bcadd(
                $this->report['categories'][$key]['spent'],
                $journal['amount']
            ) : $this->report['categories'][$key]['spent'];
            // total earned in category
            $this->report['categories'][$key]['earned'] = 1 === bccomp($journal['amount'], '0') ? bcadd(
                $this->report['categories'][$key]['earned'],
                $journal['amount']
            ) : $this->report['categories'][$key]['earned'];
        }
    }

    public function setAccounts(Collection $accounts): void
    {
        $this->accounts = $accounts;
    }

    public function setEnd(Carbon $end): void
    {
        $this->end = $end;
    }

    public function setStart(Carbon $start): void
    {
        $this->start = $start;
    }

    public function setUser(User $user): void
    {
        $this->noCatRepository->setUser($user);
        $this->opsRepository->setUser($user);
    }
}
