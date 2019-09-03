<?php
/**
 * BudgetReportHelper.php
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
declare(strict_types=1);

namespace FireflyIII\Helpers\Report;

use Carbon\Carbon;
use FireflyIII\Models\Budget;
use FireflyIII\Models\BudgetLimit;
use FireflyIII\Repositories\Budget\BudgetLimitRepositoryInterface;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Repositories\Budget\NoBudgetRepositoryInterface;
use FireflyIII\Repositories\Budget\OperationsRepositoryInterface;
use Illuminate\Support\Collection;
use Log;

/**
 * Class BudgetReportHelper.
 *
 * @codeCoverageIgnore
 */
class BudgetReportHelper implements BudgetReportHelperInterface
{
    /** @var BudgetLimitRepositoryInterface */
    private $blRepository;
    /** @var NoBudgetRepositoryInterface */
    private $noBudgetRepository;
    /** @var OperationsRepositoryInterface */
    private $opsRepository;
    /** @var BudgetRepositoryInterface The budget repository interface. */
    private $repository;

    /**
     * BudgetReportHelper constructor.
     */
    public function __construct()
    {
        $this->repository         = app(BudgetRepositoryInterface::class);
        $this->blRepository       = app(BudgetLimitRepositoryInterface::class);
        $this->opsRepository      = app(OperationsRepositoryInterface::class);
        $this->noBudgetRepository = app(NoBudgetRepositoryInterface::class);
        if ('testing' === config('app.env')) {
            Log::warning(sprintf('%s should not be instantiated in the TEST environment!', get_class($this)));
        }

    }

    /**
     * Get the full budget report.
     *
     * TODO one big method is very complex.
     *
     * @param Carbon     $start
     * @param Carbon     $end
     * @param Collection $accounts
     *
     * @return array
     */
    public function getBudgetReport(Carbon $start, Carbon $end, Collection $accounts): array
    {
        $set   = $this->repository->getBudgets();
        $array = [
            'budgets' => [],
            'sums'    => [],
        ];

        /** @var Budget $budget */
        foreach ($set as $budget) {
            $entry = [
                'budget_id'   => $budget->id,
                'budget_name' => $budget->name,
                'no_budget'   => false,
                'rows'        => [],
            ];
            // get multi currency expenses first:
            $budgetLimits    = $this->blRepository->getBudgetLimits($budget, $start, $end);
            $expenses        = $this->opsRepository->spentInPeriodMc(new Collection([$budget]), $accounts, $start, $end);
            $defaultCurrency = app('amount')->getDefaultCurrencyByUser($budget->user);
            Log::debug(sprintf('Default currency for getBudgetReport is %s', $defaultCurrency->code));
            if (0 === count($expenses)) {
                // list the budget limits, basic amounts.
                /** @var BudgetLimit $limit */
                foreach ($budgetLimits as $limit) {
                    $currency = $limit->transactionCurrency ?? $defaultCurrency;
                    Log::debug(sprintf('Default currency for limit #%d is %s', $limit->id, $currency->code));
                    $row = [
                        'limit_id'                => $limit->id,
                        'start_date'              => $limit->start_date,
                        'end_date'                => $limit->end_date,
                        'budgeted'                => $limit->amount,
                        'spent'                   => '0',
                        'left'                    => $limit->amount,
                        'overspent'               => '0',
                        'currency_id'             => $currency->id,
                        'currency_code'           => $currency->code,
                        'currency_name'           => $currency->name,
                        'currency_symbol'         => $currency->symbol,
                        'currency_decimal_places' => $currency->decimal_places,
                    ];

                    $entry['rows'][] = $row;
                }
            }
            foreach ($expenses as $expense) {
                $limit = $this->budgetLimitInCurrency($expense['currency_id'], $budgetLimits);
                $row   = [
                    'limit_id'                => null,
                    'start_date'              => null,
                    'end_date'                => null,
                    'budgeted'                => null,
                    'spent'                   => $expense['amount'],
                    'left'                    => null,
                    'overspent'               => '0',
                    'currency_id'             => $expense['currency_id'],
                    'currency_code'           => $expense['currency_name'],
                    'currency_name'           => $expense['currency_name'],
                    'currency_symbol'         => $expense['currency_symbol'],
                    'currency_decimal_places' => $expense['currency_decimal_places'],
                ];
                if (null !== $limit) {
                    // yes
                    $row['start_date'] = $limit->start_date;
                    $row['end_date']   = $limit->end_date;
                    $row['budgeted']   = $limit->amount;
                    $row['limit_id']   = $limit->id;

                    // less than zero? Set to 0.0
                    $row['left'] = -1 === bccomp(bcadd($limit->amount, $row['spent']), '0') ? '0' : bcadd($limit->amount, $row['spent']);

                    // spent > budgeted? then sum, otherwise other sum
                    $row['overspent'] = 1 === bccomp(bcmul($row['spent'],'-1'), $row['budgeted']) ? bcadd($row['spent'], $row['budgeted']) : '0';
                }
                $entry['rows'][] = $row;
            }
            $array['budgets'][] = $entry;
        }
        $noBudget      = $this->noBudgetRepository->spentInPeriodWoBudgetMc($accounts, $start, $end);
        $noBudgetEntry = [
            'budget_id'   => null,
            'budget_name' => null,
            'no_budget'   => true,
            'rows'        => [],
        ];
        foreach ($noBudget as $row) {
            $noBudgetEntry['rows'][] = [
                'limit_id'                => null,
                'start_date'              => null,
                'end_date'                => null,
                'budgeted'                => null,
                'spent'                   => $row['amount'],
                'left'                    => null,
                'overspent'               => null,
                'currency_id'             => $row['currency_id'],
                'currency_code'           => $row['currency_code'],
                'currency_name'           => $row['currency_name'],
                'currency_symbol'         => $row['currency_symbol'],
                'currency_decimal_places' => $row['currency_decimal_places'],
            ];
        }
        $array['budgets'][] = $noBudgetEntry;

        // fill sums:
        /** @var array $budget */
        foreach ($array['budgets'] as $budget) {
            /** @var array $row */
            foreach ($budget['rows'] as $row) {
                $currencyId                              = $row['currency_id'];
                $array['sums'][$currencyId]              = $array['sums'][$currencyId] ?? [
                        'currency_id'             => $row['currency_id'],
                        'currency_code'           => $row['currency_code'],
                        'currency_name'           => $row['currency_name'],
                        'currency_symbol'         => $row['currency_symbol'],
                        'currency_decimal_places' => $row['currency_decimal_places'],
                        'budgeted'                => '0',
                        'spent'                   => '0',
                        'left'                    => '0',
                        'overspent'               => '0',
                    ];
                $array['sums'][$currencyId]['budgeted']  = bcadd($array['sums'][$currencyId]['budgeted'], $row['budgeted'] ?? '0');
                $array['sums'][$currencyId]['spent']     = bcadd($array['sums'][$currencyId]['spent'], $row['spent'] ?? '0');
                $array['sums'][$currencyId]['left']      = bcadd($array['sums'][$currencyId]['left'], $row['left'] ?? '0');
                $array['sums'][$currencyId]['overspent'] = bcadd($array['sums'][$currencyId]['overspent'], $row['overspent'] ?? '0');
            }
        }

        return $array;
    }

    /**
     * Returns from the collection the budget limit with the indicated currency ID
     *
     * @param int        $currencyId
     * @param Collection $budgetLimits
     *
     * @return BudgetLimit|null
     */
    private function budgetLimitInCurrency(int $currencyId, Collection $budgetLimits): ?BudgetLimit
    {
        return $budgetLimits->first(
            static function (BudgetLimit $limit) use ($currencyId) {
                return $limit->transaction_currency_id === $currencyId;
            }
        );
    }
}
