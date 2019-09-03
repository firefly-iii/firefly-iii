<?php
/**
 * CategoryReportController.php
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

namespace FireflyIII\Http\Controllers\Chart;

use Carbon\Carbon;
use FireflyIII\Generator\Chart\Basic\GeneratorInterface;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Models\Category;
use FireflyIII\Repositories\Category\OperationsRepositoryInterface;
use FireflyIII\Support\Http\Controllers\AugumentData;
use FireflyIII\Support\Http\Controllers\TransactionCalculation;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;

/**
 * Separate controller because many helper functions are shared.
 *
 * Class CategoryReportController
 */
class CategoryReportController extends Controller
{
    use AugumentData, TransactionCalculation;

    /** @var GeneratorInterface Chart generation methods. */
    private $generator;
    /** @var OperationsRepositoryInterface */
    private $opsRepository;

    /**
     * CategoryReportController constructor.
     *
     * @codeCoverageIgnore
     */
    public function __construct()
    {
        parent::__construct();
        $this->middleware(
            function ($request, $next) {
                $this->generator     = app(GeneratorInterface::class);
                $this->opsRepository = app(OperationsRepositoryInterface::class);

                return $next($request);
            }
        );
    }

    //
    //    /**
    //     * Chart for expenses grouped by expense account.
    //     *
    //     * TODO this chart is not multi-currency aware.
    //     *
    //     * @param Collection $accounts
    //     * @param Collection $categories
    //     * @param Carbon     $start
    //     * @param Carbon     $end
    //     * @param string     $others
    //     *
    //     * @return JsonResponse
    //     */
    //    public function accountExpense(Collection $accounts, Collection $categories, Carbon $start, Carbon $end, string $others): JsonResponse
    //    {
    //        /** @var MetaPieChartInterface $helper */
    //        $helper = app(MetaPieChartInterface::class);
    //        $helper->setAccounts($accounts)->setCategories($categories)->setStart($start)->setEnd($end)->setCollectOtherObjects(1 === (int)$others);
    //
    //        $chartData = $helper->generate('expense', 'account');
    //        $data      = $this->generator->pieChart($chartData);
    //
    //        return response()->json($data);
    //    }

    //
    //    /**
    //     * Chart for income grouped by revenue account.
    //     *
    //     * TODO this chart is not multi-currency aware.
    //     *
    //     * @param Collection $accounts
    //     * @param Collection $categories
    //     * @param Carbon     $start
    //     * @param Carbon     $end
    //     * @param string     $others
    //     *
    //     * @return JsonResponse
    //     */
    //    public function accountIncome(Collection $accounts, Collection $categories, Carbon $start, Carbon $end, string $others): JsonResponse
    //    {
    //        /** @var MetaPieChartInterface $helper */
    //        $helper = app(MetaPieChartInterface::class);
    //        $helper->setAccounts($accounts);
    //        $helper->setCategories($categories);
    //        $helper->setStart($start);
    //        $helper->setEnd($end);
    //        $helper->setCollectOtherObjects(1 === (int)$others);
    //        $chartData = $helper->generate('income', 'account');
    //        $data      = $this->generator->pieChart($chartData);
    //
    //        return response()->json($data);
    //    }

    /**
     * @param Collection $accounts
     * @param Collection $categories
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return JsonResponse
     */
    public function budgetExpense(Collection $accounts, Collection $categories, Carbon $start, Carbon $end): JsonResponse
    {
        $result = [];
        $spent  = $this->opsRepository->listExpenses($start, $end, $accounts, $categories);

        // loop expenses.
        foreach ($spent as $currency) {
            /** @var array $category */
            foreach ($currency['categories'] as $category) {
                foreach ($category['transaction_journals'] as $journal) {
                    $objectName               = $journal['budget_name'] ?? trans('firefly.no_budget');
                    $title                    = sprintf('%s (%s)', $objectName, $currency['currency_name']);
                    $result[$title]           = $result[$title] ?? [
                            'amount'          => '0',
                            'currency_symbol' => $currency['currency_symbol'],
                        ];
                    $amount                   = app('steam')->positive($journal['amount']);
                    $result[$title]['amount'] = bcadd($result[$title]['amount'], $amount);
                }
            }
        }

        $data = $this->generator->multiCurrencyPieChart($result);

        return response()->json($data);
    }

    /**
     * @param Collection $accounts
     * @param Collection $categories
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return JsonResponse
     */
    public function categoryExpense(Collection $accounts, Collection $categories, Carbon $start, Carbon $end): JsonResponse
    {
        $result = [];
        $spent  = $this->opsRepository->listExpenses($start, $end, $accounts, $categories);

        // loop expenses.
        foreach ($spent as $currency) {
            /** @var array $category */
            foreach ($currency['categories'] as $category) {
                $title          = sprintf('%s (%s)', $category['name'], $currency['currency_name']);
                $result[$title] = $result[$title] ?? [
                        'amount'          => '0',
                        'currency_symbol' => $currency['currency_symbol'],
                    ];
                foreach ($category['transaction_journals'] as $journal) {
                    $amount                   = app('steam')->positive($journal['amount']);
                    $result[$title]['amount'] = bcadd($result[$title]['amount'], $amount);
                }
            }
        }

        $data = $this->generator->multiCurrencyPieChart($result);

        return response()->json($data);
    }

    /**
     * @param Collection $accounts
     * @param Collection $categories
     * @param Carbon     $start
     * @param Carbon     $end
     * @param string     $others
     *
     * @return JsonResponse
     *
     */
    public function categoryIncome(Collection $accounts, Collection $categories, Carbon $start, Carbon $end): JsonResponse
    {
        $result = [];
        $earned = $this->opsRepository->listIncome($start, $end, $accounts, $categories);

        // loop expenses.
        foreach ($earned as $currency) {
            /** @var array $category */
            foreach ($currency['categories'] as $category) {
                $title          = sprintf('%s (%s)', $category['name'], $currency['currency_name']);
                $result[$title] = $result[$title] ?? [
                        'amount'          => '0',
                        'currency_symbol' => $currency['currency_symbol'],
                    ];
                foreach ($category['transaction_journals'] as $journal) {
                    $amount                   = app('steam')->positive($journal['amount']);
                    $result[$title]['amount'] = bcadd($result[$title]['amount'], $amount);
                }
            }
        }

        $data = $this->generator->multiCurrencyPieChart($result);

        return response()->json($data);
    }

    /**
     * @param Collection $accounts
     * @param Collection $categories
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return JsonResponse
     */
    public function destinationExpense(Collection $accounts, Collection $categories, Carbon $start, Carbon $end): JsonResponse
    {
        $result = [];
        $spent  = $this->opsRepository->listExpenses($start, $end, $accounts, $categories);

        // loop expenses.
        foreach ($spent as $currency) {
            /** @var array $category */
            foreach ($currency['categories'] as $category) {
                foreach ($category['transaction_journals'] as $journal) {
                    $objectName               = $journal['destination_account_name'] ?? trans('firefly.empty');
                    $title                    = sprintf('%s (%s)', $objectName, $currency['currency_name']);
                    $result[$title]           = $result[$title] ?? [
                            'amount'          => '0',
                            'currency_symbol' => $currency['currency_symbol'],
                        ];
                    $amount                   = app('steam')->positive($journal['amount']);
                    $result[$title]['amount'] = bcadd($result[$title]['amount'], $amount);
                }
            }
        }

        $data = $this->generator->multiCurrencyPieChart($result);

        return response()->json($data);
    }

    /**
     * @param Collection $accounts
     * @param Collection $categories
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return JsonResponse
     */
    public function destinationIncome(Collection $accounts, Collection $categories, Carbon $start, Carbon $end): JsonResponse
    {
        $result = [];
        $spent  = $this->opsRepository->listIncome($start, $end, $accounts, $categories);

        // loop expenses.
        foreach ($spent as $currency) {
            /** @var array $category */
            foreach ($currency['categories'] as $category) {
                foreach ($category['transaction_journals'] as $journal) {
                    $objectName               = $journal['destination_account_name'] ?? trans('firefly.empty');
                    $title                    = sprintf('%s (%s)', $objectName, $currency['currency_name']);
                    $result[$title]           = $result[$title] ?? [
                            'amount'          => '0',
                            'currency_symbol' => $currency['currency_symbol'],
                        ];
                    $amount                   = app('steam')->positive($journal['amount']);
                    $result[$title]['amount'] = bcadd($result[$title]['amount'], $amount);
                }
            }
        }

        $data = $this->generator->multiCurrencyPieChart($result);

        return response()->json($data);
    }

    /**
     * @param Collection $accounts
     * @param Category   $category
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return JsonResponse
     *
     */
    public function mainChart(Collection $accounts, Category $category, Carbon $start, Carbon $end): JsonResponse
    {
        $chartData = [];
        $spent     = $this->opsRepository->listExpenses($start, $end, $accounts, new Collection([$category]));
        $earned    = $this->opsRepository->listIncome($start, $end, $accounts, new Collection([$category]));
        $format    = app('navigation')->preferredCarbonLocalizedFormat($start, $end);

        // loop expenses.
        foreach ($spent as $currency) {
            // add things to chart Data for each currency:
            $spentKey             = sprintf('%d-spent', $currency['currency_id']);
            $chartData[$spentKey] = $chartData[$spentKey] ?? [
                    'label'           => sprintf(
                        '%s (%s)', (string)trans('firefly.spent_in_specific_category', ['category' => $category->name]), $currency['currency_name']
                    ),
                    'type'            => 'bar',
                    'currency_symbol' => $currency['currency_symbol'],
                    'currency_id'     => $currency['currency_id'],
                    'entries'         => $this->makeEntries($start, $end),
                ];

            foreach ($currency['categories'] as $currentCategory) {
                foreach ($currentCategory['transaction_journals'] as $journal) {
                    $key                                   = $journal['date']->formatLocalized($format);
                    $amount                                = app('steam')->positive($journal['amount']);
                    $chartData[$spentKey]['entries'][$key] = $chartData[$spentKey]['entries'][$key] ?? '0';
                    $chartData[$spentKey]['entries'][$key] = bcadd($chartData[$spentKey]['entries'][$key], $amount);
                }
            }
        }

        // loop income.
        foreach ($earned as $currency) {
            // add things to chart Data for each currency:
            $spentKey             = sprintf('%d-earned', $currency['currency_id']);
            $chartData[$spentKey] = $chartData[$spentKey] ?? [
                    'label'           => sprintf(
                        '%s (%s)', (string)trans('firefly.earned_in_specific_category', ['category' => $category->name]), $currency['currency_name']
                    ),
                    'type'            => 'bar',
                    'currency_symbol' => $currency['currency_symbol'],
                    'currency_id'     => $currency['currency_id'],
                    'entries'         => $this->makeEntries($start, $end),
                ];

            foreach ($currency['categories'] as $currentCategory) {
                foreach ($currentCategory['transaction_journals'] as $journal) {
                    $key                                   = $journal['date']->formatLocalized($format);
                    $amount                                = app('steam')->positive($journal['amount']);
                    $chartData[$spentKey]['entries'][$key] = $chartData[$spentKey]['entries'][$key] ?? '0';
                    $chartData[$spentKey]['entries'][$key] = bcadd($chartData[$spentKey]['entries'][$key], $amount);
                }
            }
        }

        $data = $this->generator->multiSet($chartData);

        return response()->json($data);
    }

    /**
     * @param Collection $accounts
     * @param Collection $categories
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return JsonResponse
     */
    public function sourceExpense(Collection $accounts, Collection $categories, Carbon $start, Carbon $end): JsonResponse
    {
        $result = [];
        $spent  = $this->opsRepository->listExpenses($start, $end, $accounts, $categories);

        // loop expenses.
        foreach ($spent as $currency) {
            /** @var array $category */
            foreach ($currency['categories'] as $category) {
                foreach ($category['transaction_journals'] as $journal) {
                    $objectName               = $journal['source_account_name'] ?? trans('firefly.empty');
                    $title                    = sprintf('%s (%s)', $objectName, $currency['currency_name']);
                    $result[$title]           = $result[$title] ?? [
                            'amount'          => '0',
                            'currency_symbol' => $currency['currency_symbol'],
                        ];
                    $amount                   = app('steam')->positive($journal['amount']);
                    $result[$title]['amount'] = bcadd($result[$title]['amount'], $amount);
                }
            }
        }

        $data = $this->generator->multiCurrencyPieChart($result);

        return response()->json($data);
    }

    /**
     * @param Collection $accounts
     * @param Collection $categories
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return JsonResponse
     */
    public function sourceIncome(Collection $accounts, Collection $categories, Carbon $start, Carbon $end): JsonResponse
    {
        $result = [];
        $earned = $this->opsRepository->listIncome($start, $end, $accounts, $categories);

        // loop expenses.
        foreach ($earned as $currency) {
            /** @var array $category */
            foreach ($currency['categories'] as $category) {
                foreach ($category['transaction_journals'] as $journal) {
                    $objectName               = $journal['source_account_name'] ?? trans('firefly.empty');
                    $title                    = sprintf('%s (%s)', $objectName, $currency['currency_name']);
                    $result[$title]           = $result[$title] ?? [
                            'amount'          => '0',
                            'currency_symbol' => $currency['currency_symbol'],
                        ];
                    $amount                   = app('steam')->positive($journal['amount']);
                    $result[$title]['amount'] = bcadd($result[$title]['amount'], $amount);
                }
            }
        }

        $data = $this->generator->multiCurrencyPieChart($result);

        return response()->json($data);
    }

    /**
     * TODO duplicate function
     *
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return array
     */
    private function makeEntries(Carbon $start, Carbon $end): array
    {
        $return         = [];
        $format         = app('navigation')->preferredCarbonLocalizedFormat($start, $end);
        $preferredRange = app('navigation')->preferredRangeFormat($start, $end);
        $currentStart   = clone $start;
        while ($currentStart <= $end) {
            $currentEnd   = app('navigation')->endOfPeriod($currentStart, $preferredRange);
            $key          = $currentStart->formatLocalized($format);
            $return[$key] = '0';
            $currentStart = clone $currentEnd;
            $currentStart->addDay()->startOfDay();
        }

        return $return;
    }

}
