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
use FireflyIII\Api\V1\Requests\DateRangeRequest;
use FireflyIII\Enums\UserRoleEnum;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\Budget;
use FireflyIII\Models\BudgetLimit;
use FireflyIII\Repositories\Budget\BudgetLimitRepositoryInterface;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Repositories\Budget\OperationsRepositoryInterface;
use FireflyIII\Support\Facades\Amount;
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
    private BudgetRepositoryInterface       $repository;

    public function __construct()
    {
        parent::__construct();
        $this->middleware(
            function ($request, $next) {
                $this->validateUserGroup($request);
                $this->repository    = app(BudgetRepositoryInterface::class);
                $this->blRepository  = app(BudgetLimitRepositoryInterface::class);
                $this->opsRepository = app(OperationsRepositoryInterface::class);
                $this->repository->setUserGroup($this->userGroup);
                $this->opsRepository->setUserGroup($this->userGroup);
                $this->blRepository->setUserGroup($this->userGroup);
                $this->repository->setUser($this->user);
                $this->opsRepository->setUser($this->user);
                $this->blRepository->setUser($this->user);

                return $next($request);
            }
        );
    }

    /**
     * TODO see autocomplete/accountcontroller
     *
     * @throws FireflyException
     */
    public function overview(DateRangeRequest $request): JsonResponse
    {
        /** @var Carbon $start */
        $start   = $request->attributes->get('start');

        /** @var Carbon $end */
        $end     = $request->attributes->get('end');

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
        $limits     = $this->blRepository->getBudgetLimits($budget, $start, $end);
        $rows       = [];
        $spent      = $this->opsRepository->listExpenses($start, $end, null, new Collection()->push($budget));
        $expenses   = $this->processExpenses($budget->id, $spent, $start, $end);
        $converter  = new ExchangeRateConverter();
        $currencies = [$this->primaryCurrency->id => $this->primaryCurrency];

        /**
         * @var int   $currencyId
         * @var array $row
         */
        foreach ($expenses as $currencyId => $row) {
            // budgeted, left and overspent are now 0.
            $limit               = $this->filterLimit($currencyId, $limits);

            // primary currency entries
            $row['pc_budgeted']  = '0';
            $row['pc_spent']     = '0';
            $row['pc_left']      = '0';
            $row['pc_overspent'] = '0';

            if ($limit instanceof BudgetLimit) {
                $row['budgeted']  = $limit->amount;
                $row['left']      = bcsub((string) $row['budgeted'], bcmul((string) $row['spent'], '-1'));
                $row['overspent'] = bcmul($row['left'], '-1');
                $row['left']      = 1 === bccomp($row['left'], '0') ? $row['left'] : '0';
                $row['overspent'] = 1 === bccomp($row['overspent'], '0') ? $row['overspent'] : '0';
            }

            // convert data if necessary.
            if (true === $this->convertToPrimary && $currencyId !== $this->primaryCurrency->id) {
                $currencies[$currencyId] ??= Amount::getTransactionCurrencyById($currencyId);
                $row['pc_budgeted']  = $converter->convert($currencies[$currencyId], $this->primaryCurrency, $start, $row['budgeted']);
                $row['pc_spent']     = $converter->convert($currencies[$currencyId], $this->primaryCurrency, $start, $row['spent']);
                $row['pc_left']      = $converter->convert($currencies[$currencyId], $this->primaryCurrency, $start, $row['left']);
                $row['pc_overspent'] = $converter->convert($currencies[$currencyId], $this->primaryCurrency, $start, $row['overspent']);
            }
            if (true === $this->convertToPrimary && $currencyId === $this->primaryCurrency->id) {
                $row['pc_budgeted']  = $row['budgeted'];
                $row['pc_spent']     = $row['spent'];
                $row['pc_left']      = $row['left'];
                $row['pc_overspent'] = $row['overspent'];
            }
            $rows[]              = $row;
        }


        // is always an array
        $return     = [];
        foreach ($rows as $row) {
            $current  = [
                'label'                           => $budget->name,
                'currency_id'                     => (string)$row['currency_id'],
                'currency_name'                   => $row['currency_name'],
                'currency_code'                   => $row['currency_code'],
                'currency_decimal_places'         => $row['currency_decimal_places'],

                'primary_currency_id'             => (string)$this->primaryCurrency->id,
                'primary_currency_name'           => $this->primaryCurrency->name,
                'primary_currency_code'           => $this->primaryCurrency->code,
                'primary_currency_symbol'         => $this->primaryCurrency->symbol,
                'primary_currency_decimal_places' => $this->primaryCurrency->decimal_places,

                'period'                          => null,
                'date'                            => $row['start'],
                'start_date'                      => $row['start'],
                'end_date'                        => $row['end'],
                'yAxisID'                         => 0,
                'type'                            => 'bar',
                'entries'                         => [
                    'budgeted'  => $row['budgeted'],
                    'spent'     => $row['spent'],
                    'left'      => $row['left'],
                    'overspent' => $row['overspent'],
                ],
                'pc_entries'                      => [
                    'budgeted'  => $row['pc_budgeted'],
                    'spent'     => $row['pc_spent'],
                    'left'      => $row['pc_left'],
                    'overspent' => $row['pc_overspent'],
                ],
            ];
            $return[] = $current;
        }

        return $return;
    }

    //    /**
    //     * When no budget limits are present, the expenses of the whole period are collected and grouped.
    //     * This is grouped per currency. Because there is no limit set, "left to spend" and "overspent" are empty.
    //     *
    //     * @throws FireflyException
    //     */
    //    private function noBudgetLimits(Budget $budget, Carbon $start, Carbon $end): array
    //    {
    //        $spent = $this->opsRepository->listExpenses($start, $end, null, new Collection()->push($budget));
    //
    //        return $this->processExpenses($budget->id, $spent, $start, $end);
    //    }

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
            $this->currencies[$currencyId] ??= Amount::getTransactionCurrencyById($currencyId);
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
                /** @var numeric-string $amount */
                $amount                       = (string)$journal['amount'];
                $return[$currencyId]['spent'] = bcadd($return[$currencyId]['spent'], $amount);
            }
        }

        return $return;
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
