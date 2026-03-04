<?php

/**
 * TagReportController.php
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
use FireflyIII\Models\Tag;
use FireflyIII\Repositories\Tag\OperationsRepositoryInterface;
use FireflyIII\Support\Facades\Navigation;
use FireflyIII\Support\Http\Controllers\AugumentData;
use FireflyIII\Support\Http\Controllers\ResolvesJournalAmountAndCurrency;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;

/**
 * Class TagReportController
 */
final class TagReportController extends Controller
{
    use AugumentData;
    use ResolvesJournalAmountAndCurrency;

    private GeneratorInterface $generator;

    private OperationsRepositoryInterface $opsRepository;

    /**
     * TagReportController constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->middleware(function ($request, $next) {
            $this->generator     = app(GeneratorInterface::class);
            $this->opsRepository = app(OperationsRepositoryInterface::class);

            return $next($request);
        });
    }

    public function budgetExpense(Collection $accounts, Collection $tags, Carbon $start, Carbon $end): JsonResponse
    {
        $result = [];
        $spent  = $this->opsRepository->listExpenses($start, $end, $accounts, $tags);

        // loop expenses.
        foreach ($spent as $currency) {
            /** @var array $tag */
            foreach ($currency['tags'] as $tag) {
                foreach ($tag['transaction_journals'] as $journal) {
                    $journalData              = $this->resolveJournalAmountAndCurrency($journal, $currency);
                    $objectName               = $journal['budget_name'] ?? trans('firefly.no_budget');
                    $title                    = sprintf('%s (%s)', $objectName, $journalData['currency_name']);
                    $result[$title] ??= [
                        'amount'          => '0',
                        'currency_symbol' => $journalData['currency_symbol'],
                        'currency_code'   => $journalData['currency_code'],
                    ];
                    $result[$title]['amount'] = bcadd($result[$title]['amount'], $journalData['amount']);
                }
            }
        }

        $data   = $this->generator->multiCurrencyPieChart($result);

        return response()->json($data);
    }

    public function categoryExpense(Collection $accounts, Collection $tags, Carbon $start, Carbon $end): JsonResponse
    {
        $result = [];
        $spent  = $this->opsRepository->listExpenses($start, $end, $accounts, $tags);

        // loop expenses.
        foreach ($spent as $currency) {
            /** @var array $tag */
            foreach ($currency['tags'] as $tag) {
                foreach ($tag['transaction_journals'] as $journal) {
                    $journalData              = $this->resolveJournalAmountAndCurrency($journal, $currency);
                    $objectName               = $journal['category_name'] ?? trans('firefly.no_category');
                    $title                    = sprintf('%s (%s)', $objectName, $journalData['currency_name']);
                    $result[$title] ??= [
                        'amount'          => '0',
                        'currency_symbol' => $journalData['currency_symbol'],
                        'currency_code'   => $journalData['currency_code'],
                    ];
                    $result[$title]['amount'] = bcadd($result[$title]['amount'], $journalData['amount']);
                }
            }
        }

        $data   = $this->generator->multiCurrencyPieChart($result);

        return response()->json($data);
    }

    public function categoryIncome(Collection $accounts, Collection $tags, Carbon $start, Carbon $end): JsonResponse
    {
        $result = [];
        $spent  = $this->opsRepository->listIncome($start, $end, $accounts, $tags);

        // loop expenses.
        foreach ($spent as $currency) {
            /** @var array $tag */
            foreach ($currency['tags'] as $tag) {
                foreach ($tag['transaction_journals'] as $journal) {
                    $journalData              = $this->resolveJournalAmountAndCurrency($journal, $currency);
                    $objectName               = $journal['category_name'] ?? trans('firefly.no_category');
                    $title                    = sprintf('%s (%s)', $objectName, $journalData['currency_name']);
                    $result[$title] ??= [
                        'amount'          => '0',
                        'currency_symbol' => $journalData['currency_symbol'],
                        'currency_code'   => $journalData['currency_code'],
                    ];
                    $result[$title]['amount'] = bcadd($result[$title]['amount'], $journalData['amount']);
                }
            }
        }

        $data   = $this->generator->multiCurrencyPieChart($result);

        return response()->json($data);
    }

    public function destinationExpense(Collection $accounts, Collection $tags, Carbon $start, Carbon $end): JsonResponse
    {
        $result = [];
        $spent  = $this->opsRepository->listExpenses($start, $end, $accounts, $tags);

        // loop expenses.
        foreach ($spent as $currency) {
            /** @var array $tag */
            foreach ($currency['tags'] as $tag) {
                foreach ($tag['transaction_journals'] as $journal) {
                    $journalData              = $this->resolveJournalAmountAndCurrency($journal, $currency);
                    $objectName               = $journal['destination_account_name'] ?? trans('firefly.empty');
                    $title                    = sprintf('%s (%s)', $objectName, $journalData['currency_name']);
                    $result[$title] ??= [
                        'amount'          => '0',
                        'currency_symbol' => $journalData['currency_symbol'],
                        'currency_code'   => $journalData['currency_code'],
                    ];
                    $result[$title]['amount'] = bcadd($result[$title]['amount'], $journalData['amount']);
                }
            }
        }

        $data   = $this->generator->multiCurrencyPieChart($result);

        return response()->json($data);
    }

    public function destinationIncome(Collection $accounts, Collection $tags, Carbon $start, Carbon $end): JsonResponse
    {
        $result = [];
        $spent  = $this->opsRepository->listIncome($start, $end, $accounts, $tags);

        // loop expenses.
        foreach ($spent as $currency) {
            /** @var array $tag */
            foreach ($currency['tags'] as $tag) {
                foreach ($tag['transaction_journals'] as $journal) {
                    $journalData              = $this->resolveJournalAmountAndCurrency($journal, $currency);
                    $objectName               = $journal['destination_account_name'] ?? trans('firefly.empty');
                    $title                    = sprintf('%s (%s)', $objectName, $journalData['currency_name']);
                    $result[$title] ??= [
                        'amount'          => '0',
                        'currency_symbol' => $journalData['currency_symbol'],
                        'currency_code'   => $journalData['currency_code'],
                    ];
                    $result[$title]['amount'] = bcadd($result[$title]['amount'], $journalData['amount']);
                }
            }
        }

        $data   = $this->generator->multiCurrencyPieChart($result);

        return response()->json($data);
    }

    /**
     * Generate main tag overview chart.
     */
    public function mainChart(Collection $accounts, Tag $tag, Carbon $start, Carbon $end): JsonResponse
    {
        $chartData = [];
        $spent     = $this->opsRepository->listExpenses($start, $end, $accounts, new Collection()->push($tag));
        $earned    = $this->opsRepository->listIncome($start, $end, $accounts, new Collection()->push($tag));
        $format    = Navigation::preferredCarbonLocalizedFormat($start, $end);

        // loop expenses.
        foreach ($spent as $currency) {
            // add things to chart Data for each currency:
            foreach ($currency['tags'] as $currentTag) {
                foreach ($currentTag['transaction_journals'] as $journal) {
                    $journalData                           = $this->resolveJournalAmountAndCurrency($journal, $currency);
                    $spentKey                              = sprintf('%d-spent', $journalData['currency_id']);
                    $chartData[$spentKey]                  ??= [
                        'label'           => sprintf(
                            '%s (%s)',
                            (string) trans('firefly.spent_in_specific_tag', ['tag' => $tag->tag]),
                            $journalData['currency_name']
                        ),
                        'type'            => 'bar',
                        'currency_symbol' => $journalData['currency_symbol'],
                        'currency_code'   => $journalData['currency_code'],
                        'currency_id'     => $journalData['currency_id'],
                        'entries'         => $this->makeEntries($start, $end),
                    ];
                    $key                                   = $journal['date']->isoFormat($format);
                    $chartData[$spentKey]['entries'][$key] ??= '0';
                    $chartData[$spentKey]['entries'][$key] = bcadd($chartData[$spentKey]['entries'][$key], $journalData['amount']);
                }
            }
        }

        // loop income.
        foreach ($earned as $currency) {
            // add things to chart Data for each currency:
            foreach ($currency['tags'] as $currentTag) {
                foreach ($currentTag['transaction_journals'] as $journal) {
                    $journalData                           = $this->resolveJournalAmountAndCurrency($journal, $currency);
                    $spentKey                              = sprintf('%d-earned', $journalData['currency_id']);
                    $chartData[$spentKey]                  ??= [
                        'label'           => sprintf(
                            '%s (%s)',
                            (string) trans('firefly.earned_in_specific_tag', ['tag' => $tag->tag]),
                            $journalData['currency_name']
                        ),
                        'type'            => 'bar',
                        'currency_symbol' => $journalData['currency_symbol'],
                        'currency_code'   => $journalData['currency_code'],
                        'currency_id'     => $journalData['currency_id'],
                        'entries'         => $this->makeEntries($start, $end),
                    ];
                    $key                                   = $journal['date']->isoFormat($format);
                    $chartData[$spentKey]['entries'][$key] ??= '0';
                    $chartData[$spentKey]['entries'][$key] = bcadd($chartData[$spentKey]['entries'][$key], $journalData['amount']);
                }
            }
        }

        $data      = $this->generator->multiSet($chartData);

        return response()->json($data);
    }

    public function sourceExpense(Collection $accounts, Collection $tags, Carbon $start, Carbon $end): JsonResponse
    {
        $result = [];
        $spent  = $this->opsRepository->listExpenses($start, $end, $accounts, $tags);

        // loop expenses.
        foreach ($spent as $currency) {
            /** @var array $tag */
            foreach ($currency['tags'] as $tag) {
                foreach ($tag['transaction_journals'] as $journal) {
                    $journalData              = $this->resolveJournalAmountAndCurrency($journal, $currency);
                    $objectName               = $journal['source_account_name'] ?? trans('firefly.empty');
                    $title                    = sprintf('%s (%s)', $objectName, $journalData['currency_name']);
                    $result[$title] ??= [
                        'amount'          => '0',
                        'currency_symbol' => $journalData['currency_symbol'],
                        'currency_code'   => $journalData['currency_code'],
                    ];
                    $result[$title]['amount'] = bcadd($result[$title]['amount'], $journalData['amount']);
                }
            }
        }

        $data   = $this->generator->multiCurrencyPieChart($result);

        return response()->json($data);
    }

    public function sourceIncome(Collection $accounts, Collection $tags, Carbon $start, Carbon $end): JsonResponse
    {
        $result = [];
        $earned = $this->opsRepository->listIncome($start, $end, $accounts, $tags);

        // loop expenses.
        foreach ($earned as $currency) {
            /** @var array $tag */
            foreach ($currency['tags'] as $tag) {
                foreach ($tag['transaction_journals'] as $journal) {
                    $journalData              = $this->resolveJournalAmountAndCurrency($journal, $currency);
                    $objectName               = $journal['source_account_name'] ?? trans('firefly.empty');
                    $title                    = sprintf('%s (%s)', $objectName, $journalData['currency_name']);
                    $result[$title] ??= [
                        'amount'          => '0',
                        'currency_symbol' => $journalData['currency_symbol'],
                        'currency_code'   => $journalData['currency_code'],
                    ];
                    $result[$title]['amount'] = bcadd($result[$title]['amount'], $journalData['amount']);
                }
            }
        }

        $data   = $this->generator->multiCurrencyPieChart($result);

        return response()->json($data);
    }

    public function tagExpense(Collection $accounts, Collection $tags, Carbon $start, Carbon $end): JsonResponse
    {
        $result = [];
        $spent  = $this->opsRepository->listExpenses($start, $end, $accounts, $tags);

        // loop expenses.
        foreach ($spent as $currency) {
            /** @var array $tag */
            foreach ($currency['tags'] as $tag) {
                foreach ($tag['transaction_journals'] as $journal) {
                    $journalData              = $this->resolveJournalAmountAndCurrency($journal, $currency);
                    $title                    = sprintf('%s (%s)', $tag['name'], $journalData['currency_name']);
                    $result[$title] ??= [
                        'amount'          => '0',
                        'currency_symbol' => $journalData['currency_symbol'],
                        'currency_code'   => $journalData['currency_code'],
                    ];
                    $result[$title]['amount'] = bcadd($result[$title]['amount'], $journalData['amount']);
                }
            }
        }
        $data   = $this->generator->multiCurrencyPieChart($result);

        return response()->json($data);
    }

    public function tagIncome(Collection $accounts, Collection $tags, Carbon $start, Carbon $end): JsonResponse
    {
        $result = [];
        $earned = $this->opsRepository->listIncome($start, $end, $accounts, $tags);

        // loop expenses.
        foreach ($earned as $currency) {
            /** @var array $tag */
            foreach ($currency['tags'] as $tag) {
                foreach ($tag['transaction_journals'] as $journal) {
                    $journalData              = $this->resolveJournalAmountAndCurrency($journal, $currency);
                    $title                    = sprintf('%s (%s)', $tag['name'], $journalData['currency_name']);
                    $result[$title] ??= [
                        'amount'          => '0',
                        'currency_symbol' => $journalData['currency_symbol'],
                        'currency_code'   => $journalData['currency_code'],
                    ];
                    $result[$title]['amount'] = bcadd($result[$title]['amount'], $journalData['amount']);
                }
            }
        }
        $data   = $this->generator->multiCurrencyPieChart($result);

        return response()->json($data);
    }

    /**
     * TODO duplicate function
     */
    private function makeEntries(Carbon $start, Carbon $end): array
    {
        $return         = [];
        $format         = Navigation::preferredCarbonLocalizedFormat($start, $end);
        $preferredRange = Navigation::preferredRangeFormat($start, $end);
        $currentStart   = clone $start;
        while ($currentStart <= $end) {
            $currentEnd   = Navigation::endOfPeriod($currentStart, $preferredRange);
            $key          = $currentStart->isoFormat($format);
            $return[$key] = '0';
            $currentStart = clone $currentEnd;
            $currentStart->addDay()->startOfDay();
        }

        return $return;
    }
}
