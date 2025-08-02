<?php

/*
 * BudgetController.php
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

namespace FireflyIII\Api\V1\Controllers\Chart;

use Carbon\Carbon;
use FireflyIII\Api\V1\Controllers\Controller;
use FireflyIII\Api\V1\Requests\Data\DateRequest;
use FireflyIII\Enums\UserRoleEnum;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\Budget;
use FireflyIII\Models\BudgetLimit;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Repositories\Budget\BudgetLimitRepositoryInterface;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Repositories\Budget\OperationsRepositoryInterface;
use FireflyIII\Support\Http\Api\CleansChartData;
use FireflyIII\Support\Http\Api\ExchangeRateConverter;
use FireflyIII\Support\Http\Api\ValidatesUserGroupTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Class BudgetController
 */
class BudgetController extends Controller
{
    use CleansChartData;
    use ValidatesUserGroupTrait;

    protected array $acceptedRoles                      = [UserRoleEnum::READ_ONLY];

    protected OperationsRepositoryInterface $opsRepository;
    private BudgetLimitRepositoryInterface  $blRepository;
    private array                           $currencies = [];
    private TransactionCurrency             $currency;
    private BudgetRepositoryInterface       $repository;

    public function __construct()
    {
        parent::__construct();
        $this->middleware(
            function ($request, $next) {
                $this->repository    = app(BudgetRepositoryInterface::class);
                $this->blRepository  = app(BudgetLimitRepositoryInterface::class);
                $this->opsRepository = app(OperationsRepositoryInterface::class);
                $userGroup           = $this->validateUserGroup($request);
                $this->repository->setUserGroup($userGroup);
                $this->opsRepository->setUserGroup($userGroup);
                $this->blRepository->setUserGroup($userGroup);

                return $next($request);
            }
        );
    }

    /**
     * TODO see autocomplete/accountcontroller
     *
     * @throws FireflyException
     */
    public function dashboard(DateRequest $request): JsonResponse
    {
        $params  = $request->getAll();

        /** @var Carbon $start */
        $start   = $params['start'];

        /** @var Carbon $end */
        $end     = $params['end'];

        // code from FrontpageChartGenerator, but not in separate class
        $budgets = $this->repository->getActiveBudgets();
        $data    = [];

        /** @var Budget $budget */
        foreach ($budgets as $budget) {
            // could return multiple arrays, so merge.
            $data = array_merge($data, $this->processBudget($budget, $start, $end));
        }

        return response()->json($this->clean($data));
    }

    /**
     * @throws FireflyException
     */
    private function processBudget(Budget $budget, Carbon $start, Carbon $end): array
    {
        // get all limits:
        $limits   = $this->blRepository->getBudgetLimits($budget, $start, $end);
        $rows     = [];
        $spent    = $this->opsRepository->listExpenses($start, $end, null, new Collection([$budget]));
        $expenses = $this->processExpenses($budget->id, $spent, $start, $end);

        /**
         * @var int   $currencyId
         * @var array $row
         */
        foreach ($expenses as $currencyId => $row) {
            // budgeted, left and overspent are now 0.
            $limit  = $this->filterLimit($currencyId, $limits);
            if (null !== $limit) {
                $row['budgeted']  = $limit->amount;
                $row['left']      = bcsub($row['budgeted'], bcmul($row['spent'], '-1'));
                $row['overspent'] = bcmul($row['left'], '-1');
                $row['left']      = 1 === bccomp($row['left'], '0') ? $row['left'] : '0';
                $row['overspent'] = 1 === bccomp($row['overspent'], '0') ? $row['overspent'] : '0';
            }
            $rows[] = $row;
        }


        // if no limits
        //        if (0 === $limits->count()) {
        //             return as a single item in an array
        //            $rows = $this->noBudgetLimits($budget, $start, $end);
        //        }

        // is always an array
        $return   = [];
        foreach ($rows as $row) {
            $current  = [
                'label'                   => $budget->name,
                'currency_id'             => (string)$row['currency_id'],
                'currency_code'           => $row['currency_code'],
                'currency_name'           => $row['currency_name'],
                'currency_decimal_places' => $row['currency_decimal_places'],
                'period'                  => null,
                'start'                   => $row['start'],
                'end'                     => $row['end'],
                'entries'                 => [
                    'budgeted'  => $row['budgeted'],
                    'spent'     => $row['spent'],
                    'left'      => $row['left'],
                    'overspent' => $row['overspent'],
                ],
            ];
            $return[] = $current;
        }

        return $return;
    }

    /**
     * When no budget limits are present, the expenses of the whole period are collected and grouped.
     * This is grouped per currency. Because there is no limit set, "left to spend" and "overspent" are empty.
     *
     * @throws FireflyException
     */
    private function noBudgetLimits(Budget $budget, Carbon $start, Carbon $end): array
    {
        $spent = $this->opsRepository->listExpenses($start, $end, null, new Collection([$budget]));

        return $this->processExpenses($budget->id, $spent, $start, $end);
    }

    /**
     * Shared between the "noBudgetLimits" function and "processLimit". Will take a single set of expenses and return
     * its info.
     *
     * @throws FireflyException
     */
    private function processExpenses(int $budgetId, array $spent, Carbon $start, Carbon $end): array
    {
        $return = [];

        /**
         * This array contains the expenses in this budget. Grouped per currency.
         * The grouping is on the main currency only.
         *
         * @var int   $currencyId
         * @var array $block
         */
        foreach ($spent as $currencyId => $block) {
            $this->currencies[$currencyId] ??= TransactionCurrency::find($currencyId);
            $return[$currencyId]           ??= [
                'currency_id'             => (string)$currencyId,
                'currency_code'           => $block['currency_code'],
                'currency_name'           => $block['currency_name'],
                'currency_symbol'         => $block['currency_symbol'],
                'currency_decimal_places' => (int)$block['currency_decimal_places'],
                'start'                   => $start->toAtomString(),
                'end'                     => $end->toAtomString(),
                'budgeted'                => '0',
                'spent'                   => '0',
                'left'                    => '0',
                'overspent'               => '0',
            ];
            $currentBudgetArray = $block['budgets'][$budgetId];

            // var_dump($return);
            /** @var array $journal */
            foreach ($currentBudgetArray['transaction_journals'] as $journal) {
                $return[$currencyId]['spent'] = bcadd($return[$currencyId]['spent'], (string)$journal['amount']);
            }
        }

        return $return;
    }

    /**
     * Function that processes each budget limit (per budget).
     *
     * If you have a budget limit in EUR, only transactions in EUR will be considered.
     * If you have a budget limit in GBP, only transactions in GBP will be considered.
     *
     * If you have a budget limit in EUR, and a transaction in GBP, it will not be considered for the EUR budget limit.
     *
     * @throws FireflyException
     */
    private function budgetLimits(Budget $budget, Collection $limits): array
    {
        Log::debug(sprintf('Now in budgetLimits(#%d)', $budget->id));
        $data = [];

        /** @var BudgetLimit $limit */
        foreach ($limits as $limit) {
            $data = array_merge($data, $this->processLimit($budget, $limit));
        }

        return $data;
    }

    /**
     * @throws FireflyException
     */
    private function processLimit(Budget $budget, BudgetLimit $limit): array
    {
        Log::debug(sprintf('Created new ExchangeRateConverter in %s', __METHOD__));
        $end             = clone $limit->end_date;
        $end->endOfDay();
        $spent           = $this->opsRepository->listExpenses($limit->start_date, $end, null, new Collection([$budget]));
        $limitCurrencyId = $limit->transaction_currency_id;

        /** @var array $entry */
        // only spent the entry where the entry's currency matches the budget limit's currency
        // so $filtered will only have 1 or 0 entries
        $filtered        = array_filter($spent, fn ($entry) => $entry['currency_id'] === $limitCurrencyId);
        $result          = $this->processExpenses($budget->id, $filtered, $limit->start_date, $end);
        if (1 === count($result)) {
            $compare                              = bccomp($limit->amount, (string)app('steam')->positive($result[$limitCurrencyId]['spent']));
            $result[$limitCurrencyId]['budgeted'] = $limit->amount;
            if (1 === $compare) {
                // convert this amount into the primary currency:
                $result[$limitCurrencyId]['left'] = bcadd($limit->amount, (string)$result[$limitCurrencyId]['spent']);
            }
            if ($compare <= 0) {
                $result[$limitCurrencyId]['overspent'] = app('steam')->positive(bcadd($limit->amount, (string)$result[$limitCurrencyId]['spent']));
            }
        }

        return $result;
    }

    private function filterLimit(int $currencyId, Collection $limits): ?BudgetLimit
    {
        $amount    = '0';
        $limit     = null;
        $converter = new ExchangeRateConverter();

        /** @var BudgetLimit $current */
        foreach ($limits as $current) {
            if (true === $this->convertToPrimary) {
                if ($current->transaction_currency_id === $this->primaryCurrency->id) {
                    // simply add it.
                    $amount = bcadd($amount, (string)$current->amount);
                    Log::debug(sprintf('Set amount in limit to %s', $amount));
                }
                if ($current->transaction_currency_id !== $this->primaryCurrency->id) {
                    // convert and then add it.
                    $converted = $converter->convert($current->transactionCurrency, $this->primaryCurrency, $current->start_date, $current->amount);
                    $amount    = bcadd($amount, $converted);
                    Log::debug(sprintf('Budgeted in limit #%d: %s %s, converted to %s %s', $current->id, $current->transactionCurrency->code, $current->amount, $this->primaryCurrency->code, $converted));
                    Log::debug(sprintf('Set amount in limit to %s', $amount));
                }
            }
            if ($current->transaction_currency_id === $currencyId) {
                $limit = $current;
            }
        }
        if (null !== $limit && true === $this->convertToPrimary) {
            // convert and add all amounts.
            $limit->amount = app('steam')->positive($amount);
            Log::debug(sprintf('Final amount in limit with converted amount %s', $limit->amount));
        }

        return $limit;
    }
}
