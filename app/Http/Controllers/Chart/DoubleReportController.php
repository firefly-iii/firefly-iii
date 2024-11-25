<?php

/**
 * DoubleReportController.php
 * Copyright (c) 2019 james@firefly-iii.org
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

namespace FireflyIII\Http\Controllers\Chart;

use Carbon\Carbon;
use FireflyIII\Generator\Chart\Basic\GeneratorInterface;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Models\Account;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Account\OperationsRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;

/**
 * Class DoubleReportController
 */
class DoubleReportController extends Controller
{
    /** @var GeneratorInterface Chart generation methods. */
    private $generator;

    /** @var OperationsRepositoryInterface */
    private $opsRepository;

    /** @var AccountRepositoryInterface */
    private $repository;

    /**
     * CategoryReportController constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->middleware(
            function ($request, $next) {
                $this->generator     = app(GeneratorInterface::class);
                $this->opsRepository = app(OperationsRepositoryInterface::class);
                $this->repository    = app(AccountRepositoryInterface::class);

                return $next($request);
            }
        );
    }

    public function budgetExpense(Collection $accounts, Collection $others, Carbon $start, Carbon $end): JsonResponse
    {
        $result   = [];
        $joined   = $this->repository->expandWithDoubles($others);
        $accounts = $accounts->merge($joined);
        $expenses = $this->opsRepository->listExpenses($start, $end, $accounts);

        // loop expenses.
        foreach ($expenses as $currency) {
            foreach ($currency['transaction_journals'] as $journal) {
                $categoryName             = $journal['budget_name'] ?? trans('firefly.no_budget');
                $title                    = sprintf('%s (%s)', $categoryName, $currency['currency_name']);
                $result[$title] ??= [
                    'amount'          => '0',
                    'currency_symbol' => $currency['currency_symbol'],
                    'currency_code'   => $currency['currency_code'],
                ];
                $amount                   = app('steam')->positive($journal['amount']);
                $result[$title]['amount'] = bcadd($result[$title]['amount'], $amount);
            }
        }

        $data     = $this->generator->multiCurrencyPieChart($result);

        return response()->json($data);
    }

    public function categoryExpense(Collection $accounts, Collection $others, Carbon $start, Carbon $end): JsonResponse
    {
        $result   = [];
        $joined   = $this->repository->expandWithDoubles($others);
        $accounts = $accounts->merge($joined);
        $spent    = $this->opsRepository->listExpenses($start, $end, $accounts);

        // loop expenses.
        foreach ($spent as $currency) {
            foreach ($currency['transaction_journals'] as $journal) {
                $categoryName             = $journal['category_name'] ?? trans('firefly.no_category');
                $title                    = sprintf('%s (%s)', $categoryName, $currency['currency_name']);
                $result[$title] ??= [
                    'amount'          => '0',
                    'currency_symbol' => $currency['currency_symbol'],
                    'currency_code'   => $currency['currency_code'],
                ];
                $amount                   = app('steam')->positive($journal['amount']);
                $result[$title]['amount'] = bcadd($result[$title]['amount'], $amount);
            }
        }

        $data     = $this->generator->multiCurrencyPieChart($result);

        return response()->json($data);
    }

    public function categoryIncome(Collection $accounts, Collection $others, Carbon $start, Carbon $end): JsonResponse
    {
        $result   = [];
        $joined   = $this->repository->expandWithDoubles($others);
        $accounts = $accounts->merge($joined);
        $earned   = $this->opsRepository->listIncome($start, $end, $accounts);

        // loop income.
        foreach ($earned as $currency) {
            foreach ($currency['transaction_journals'] as $journal) {
                $categoryName             = $journal['category_name'] ?? trans('firefly.no_category');
                $title                    = sprintf('%s (%s)', $categoryName, $currency['currency_name']);
                $result[$title] ??= [
                    'amount'          => '0',
                    'currency_symbol' => $currency['currency_symbol'],
                    'currency_code'   => $currency['currency_code'],
                ];
                $amount                   = app('steam')->positive($journal['amount']);
                $result[$title]['amount'] = bcadd($result[$title]['amount'], $amount);
            }
        }

        $data     = $this->generator->multiCurrencyPieChart($result);

        return response()->json($data);
    }

    public function mainChart(Collection $accounts, Account $account, Carbon $start, Carbon $end): JsonResponse
    {
        $chartData = [];

        $opposing  = $this->repository->expandWithDoubles(new Collection([$account]));
        $accounts  = $accounts->merge($opposing);
        $spent     = $this->opsRepository->listExpenses($start, $end, $accounts);
        $earned    = $this->opsRepository->listIncome($start, $end, $accounts);
        $format    = app('navigation')->preferredCarbonLocalizedFormat($start, $end);

        // loop expenses.
        foreach ($spent as $currency) {
            // add things to chart Data for each currency:
            $spentKey = sprintf('%d-spent', $currency['currency_id']);
            $name     = $this->getCounterpartName($accounts, $account->id, $account->name, $account->iban);

            $chartData[$spentKey] ??= [
                'label'           => sprintf(
                    '%s (%s)',
                    (string)trans('firefly.spent_in_specific_double', ['account' => $name]),
                    $currency['currency_name']
                ),
                'type'            => 'bar',
                'currency_symbol' => $currency['currency_symbol'],
                'currency_code'   => $currency['currency_code'],
                'currency_id'     => $currency['currency_id'],
                'entries'         => $this->makeEntries($start, $end),
            ];

            foreach ($currency['transaction_journals'] as $journal) {
                $key                                   = $journal['date']->isoFormat($format);
                $amount                                = app('steam')->positive($journal['amount']);
                $chartData[$spentKey]['entries'][$key] ??= '0';
                $chartData[$spentKey]['entries'][$key] = bcadd($chartData[$spentKey]['entries'][$key], $amount);
            }
        }
        // loop income.
        foreach ($earned as $currency) {
            // add things to chart Data for each currency:
            $earnedKey = sprintf('%d-earned', $currency['currency_id']);
            $name      = $this->getCounterpartName($accounts, $account->id, $account->name, $account->iban);

            $chartData[$earnedKey] ??= [
                'label'           => sprintf(
                    '%s (%s)',
                    (string)trans('firefly.earned_in_specific_double', ['account' => $name]),
                    $currency['currency_name']
                ),
                'type'            => 'bar',
                'currency_symbol' => $currency['currency_symbol'],
                'currency_code'   => $currency['currency_code'],
                'currency_id'     => $currency['currency_id'],
                'entries'         => $this->makeEntries($start, $end),
            ];

            foreach ($currency['transaction_journals'] as $journal) {
                $key                                    = $journal['date']->isoFormat($format);
                $amount                                 = app('steam')->positive($journal['amount']);
                $chartData[$earnedKey]['entries'][$key] ??= '0';
                $chartData[$earnedKey]['entries'][$key] = bcadd($chartData[$earnedKey]['entries'][$key], $amount);
            }
        }

        $data      = $this->generator->multiSet($chartData);

        return response()->json($data);
    }

    /**
     * TODO duplicate function
     */
    private function getCounterpartName(Collection $accounts, int $id, string $name, ?string $iban): string
    {
        /** @var Account $account */
        foreach ($accounts as $account) {
            if ($account->name === $name && $account->id !== $id) {
                return $account->name;
            }
            if (null !== $account->iban && $account->iban === $iban && $account->id !== $id) {
                return $account->iban;
            }
        }

        return $name;
    }

    /**
     * TODO duplicate function
     */
    private function makeEntries(Carbon $start, Carbon $end): array
    {
        $return         = [];
        $format         = app('navigation')->preferredCarbonLocalizedFormat($start, $end);
        $preferredRange = app('navigation')->preferredRangeFormat($start, $end);
        $currentStart   = clone $start;
        while ($currentStart <= $end) {
            $currentEnd   = app('navigation')->endOfPeriod($currentStart, $preferredRange);
            $key          = $currentStart->isoFormat($format);
            $return[$key] = '0';
            $currentStart = clone $currentEnd;
            $currentStart->addDay()->startOfDay();
        }

        return $return;
    }

    public function tagExpense(Collection $accounts, Collection $others, Carbon $start, Carbon $end): JsonResponse
    {
        $result           = [];
        $joined           = $this->repository->expandWithDoubles($others);
        $accounts         = $accounts->merge($joined);
        $expenses         = $this->opsRepository->listExpenses($start, $end, $accounts);
        $includedJournals = [];
        // loop expenses.
        foreach ($expenses as $currency) {
            foreach ($currency['transaction_journals'] as $journal) {
                $journalId = $journal['transaction_journal_id'];

                // no tags? also deserves a sport
                if (0 === count($journal['tags'])) {
                    $includedJournals[]       = $journalId;
                    // do something
                    $tagName                  = trans('firefly.no_tags');
                    $title                    = sprintf('%s (%s)', $tagName, $currency['currency_name']);
                    $result[$title] ??= [
                        'amount'          => '0',
                        'currency_symbol' => $currency['currency_symbol'],
                        'currency_code'   => $currency['currency_code'],
                    ];
                    $amount                   = app('steam')->positive($journal['amount']);
                    $result[$title]['amount'] = bcadd($result[$title]['amount'], $amount);
                }

                // loop each tag:
                /** @var array $tag */
                foreach ($journal['tags'] as $tag) {
                    if (in_array($journalId, $includedJournals, true)) {
                        continue;
                    }
                    $includedJournals[]       = $journalId;
                    // do something
                    $tagName                  = $tag['name'];
                    $title                    = sprintf('%s (%s)', $tagName, $currency['currency_name']);
                    $result[$title] ??= [
                        'amount'          => '0',
                        'currency_symbol' => $currency['currency_symbol'],
                        'currency_code'   => $currency['currency_code'],
                    ];
                    $amount                   = app('steam')->positive($journal['amount']);
                    $result[$title]['amount'] = bcadd($result[$title]['amount'], $amount);
                }
            }
        }

        $data             = $this->generator->multiCurrencyPieChart($result);

        return response()->json($data);
    }

    public function tagIncome(Collection $accounts, Collection $others, Carbon $start, Carbon $end): JsonResponse
    {
        $result           = [];
        $joined           = $this->repository->expandWithDoubles($others);
        $accounts         = $accounts->merge($joined);
        $income           = $this->opsRepository->listIncome($start, $end, $accounts);
        $includedJournals = [];
        // loop income.
        foreach ($income as $currency) {
            foreach ($currency['transaction_journals'] as $journal) {
                $journalId = $journal['transaction_journal_id'];

                // no tags? also deserves a sport
                if (0 === count($journal['tags'])) {
                    $includedJournals[]       = $journalId;
                    // do something
                    $tagName                  = trans('firefly.no_tags');
                    $title                    = sprintf('%s (%s)', $tagName, $currency['currency_name']);
                    $result[$title] ??= [
                        'amount'          => '0',
                        'currency_symbol' => $currency['currency_symbol'],
                        'currency_code'   => $currency['currency_code'],
                    ];
                    $amount                   = app('steam')->positive($journal['amount']);
                    $result[$title]['amount'] = bcadd($result[$title]['amount'], $amount);
                }

                // loop each tag:
                /** @var array $tag */
                foreach ($journal['tags'] as $tag) {
                    if (in_array($journalId, $includedJournals, true)) {
                        continue;
                    }
                    $includedJournals[]       = $journalId;
                    // do something
                    $tagName                  = $tag['name'];
                    $title                    = sprintf('%s (%s)', $tagName, $currency['currency_name']);
                    $result[$title] ??= [
                        'amount'          => '0',
                        'currency_symbol' => $currency['currency_symbol'],
                        'currency_code'   => $currency['currency_code'],
                    ];
                    $amount                   = app('steam')->positive($journal['amount']);
                    $result[$title]['amount'] = bcadd($result[$title]['amount'], $amount);
                }
            }
        }

        $data             = $this->generator->multiCurrencyPieChart($result);

        return response()->json($data);
    }
}
