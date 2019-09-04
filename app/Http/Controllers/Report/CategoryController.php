<?php
/**
 * CategoryController.php
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

namespace FireflyIII\Http\Controllers\Report;

use Carbon\Carbon;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Models\Account;
use FireflyIII\Models\Category;
use FireflyIII\Repositories\Category\CategoryRepositoryInterface;
use FireflyIII\Repositories\Category\NoCategoryRepositoryInterface;
use FireflyIII\Repositories\Category\OperationsRepositoryInterface;
use FireflyIII\Support\CacheProperties;
use FireflyIII\Support\Http\Controllers\BasicDataSupport;
use Illuminate\Contracts\View\Factory;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Log;
use Throwable;

/**
 * Class CategoryController.
 */
class CategoryController extends Controller
{
    use BasicDataSupport;


    /** @var OperationsRepositoryInterface */
    private $opsRepository;

    /**
     * ExpenseReportController constructor.
     *
     * @codeCoverageIgnore
     */
    public function __construct()
    {
        parent::__construct();
        $this->middleware(
            function ($request, $next) {
                $this->opsRepository = app(OperationsRepositoryInterface::class);

                return $next($request);
            }
        );
    }

    /**
     * @param Collection $accounts
     * @param Collection $categories
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return Factory|View
     */
    public function accountPerCategory(Collection $accounts, Collection $categories, Carbon $start, Carbon $end)
    {
        $spent  = $this->opsRepository->listExpenses($start, $end, $accounts, $categories);
        $earned = $this->opsRepository->listIncome($start, $end, $accounts, $categories);
        $report = [];
        /** @var Account $account */
        foreach ($accounts as $account) {
            $accountId          = $account->id;
            $report[$accountId] = $report[$accountId] ?? [
                    'name'       => $account->name,
                    'id'         => $account->id,
                    'iban'       => $account->iban,
                    'currencies' => [],
                ];
        }

        // loop expenses.
        foreach ($spent as $currency) {
            $currencyId = $currency['currency_id'];

            /** @var array $category */
            foreach ($currency['categories'] as $category) {
                foreach ($category['transaction_journals'] as $journal) {
                    $sourceAccountId                                     = $journal['source_account_id'];
                    $report[$sourceAccountId]['currencies'][$currencyId] = $report[$sourceAccountId]['currencies'][$currencyId] ?? [
                            'currency_id'             => $currency['currency_id'],
                            'currency_symbol'         => $currency['currency_symbol'],
                            'currency_name'           => $currency['currency_name'],
                            'currency_decimal_places' => $currency['currency_decimal_places'],
                            'categories'              => [],
                        ];

                    $report[$sourceAccountId]['currencies'][$currencyId]['categories'][$category['id']]
                                                                                                                 = $report[$sourceAccountId]['currencies'][$currencyId]['categories'][$category['id']]
                                                                                                                   ??
                                                                                                                   [
                                                                                                                       'spent'  => '0',
                                                                                                                       'earned' => '0',
                                                                                                                       'sum'    => '0',
                                                                                                                   ];
                    $report[$sourceAccountId]['currencies'][$currencyId]['categories'][$category['id']]['spent'] = bcadd(
                        $report[$sourceAccountId]['currencies'][$currencyId]['categories'][$category['id']]['spent'], $journal['amount']
                    );
                    $report[$sourceAccountId]['currencies'][$currencyId]['categories'][$category['id']]['sum']   = bcadd(
                        $report[$sourceAccountId]['currencies'][$currencyId]['categories'][$category['id']]['sum'], $journal['amount']
                    );
                }
            }
        }


        // loop income.
        foreach ($earned as $currency) {
            $currencyId = $currency['currency_id'];

            /** @var array $category */
            foreach ($currency['categories'] as $category) {
                foreach ($category['transaction_journals'] as $journal) {
                    $destinationId = $journal['destination_account_id'];
                    $report[$destinationId]['currencies'][$currencyId]
                                   = $report[$destinationId]['currencies'][$currencyId]
                                     ?? [
                                         'currency_id'             => $currency['currency_id'],
                                         'currency_symbol'         => $currency['currency_symbol'],
                                         'currency_name'           => $currency['currency_name'],
                                         'currency_decimal_places' => $currency['currency_decimal_places'],
                                         'categories'              => [],
                                     ];


                    $report[$destinationId]['currencies'][$currencyId]['categories'][$category['id']]
                                                                                                                = $report[$destinationId]['currencies'][$currencyId]['categories'][$category['id']]
                                                                                                                  ??
                                                                                                                  [
                                                                                                                      'spent'  => '0',
                                                                                                                      'earned' => '0',
                                                                                                                      'sum'    => '0',
                                                                                                                  ];
                    $report[$destinationId]['currencies'][$currencyId]['categories'][$category['id']]['earned'] = bcadd(
                        $report[$destinationId]['currencies'][$currencyId]['categories'][$category['id']]['earned'], $journal['amount']
                    );
                    $report[$destinationId]['currencies'][$currencyId]['categories'][$category['id']]['sum']    = bcadd(
                        $report[$destinationId]['currencies'][$currencyId]['categories'][$category['id']]['sum'], $journal['amount']
                    );
                }
            }
        }

        return view('reports.category.partials.account-per-category', compact('report', 'categories'));
    }

    /**
     * @param Collection $accounts
     * @param Collection $categories
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return Factory|View
     */
    public function accounts(Collection $accounts, Collection $categories, Carbon $start, Carbon $end)
    {
        $spent  = $this->opsRepository->listExpenses($start, $end, $accounts, $categories);
        $earned = $this->opsRepository->listIncome($start, $end, $accounts, $categories);
        $report = [];
        $sums   = [];
        /** @var Account $account */
        foreach ($accounts as $account) {
            $accountId          = $account->id;
            $report[$accountId] = $report[$accountId] ?? [
                    'name'       => $account->name,
                    'id'         => $account->id,
                    'iban'       => $account->iban,
                    'currencies' => [],
                ];
        }

        // loop expenses.
        foreach ($spent as $currency) {
            $currencyId        = $currency['currency_id'];
            $sums[$currencyId] = $sums[$currencyId] ?? [
                    'currency_id'             => $currency['currency_id'],
                    'currency_symbol'         => $currency['currency_symbol'],
                    'currency_name'           => $currency['currency_name'],
                    'currency_decimal_places' => $currency['currency_decimal_places'],
                    'spent_sum'               => '0',
                    'earned_sum'              => '0',
                    'total_sum'               => '0',
                ];
            foreach ($currency['categories'] as $category) {
                foreach ($category['transaction_journals'] as $journal) {
                    $sourceAccountId                                              = $journal['source_account_id'];
                    $report[$sourceAccountId]['currencies'][$currencyId]          = $report[$sourceAccountId]['currencies'][$currencyId] ?? [
                            'currency_id'             => $currency['currency_id'],
                            'currency_symbol'         => $currency['currency_symbol'],
                            'currency_name'           => $currency['currency_name'],
                            'currency_decimal_places' => $currency['currency_decimal_places'],
                            'spent'                   => '0',
                            'earned'                  => '0',
                            'sum'                     => '0',
                        ];
                    $report[$sourceAccountId]['currencies'][$currencyId]['spent'] = bcadd(
                        $report[$sourceAccountId]['currencies'][$currencyId]['spent'], $journal['amount']
                    );
                    $report[$sourceAccountId]['currencies'][$currencyId]['sum']   = bcadd(
                        $report[$sourceAccountId]['currencies'][$currencyId]['sum'], $journal['amount']
                    );
                    $sums[$currencyId]['spent_sum']                               = bcadd($sums[$currencyId]['spent_sum'], $journal['amount']);
                    $sums[$currencyId]['total_sum']                               = bcadd($sums[$currencyId]['total_sum'], $journal['amount']);
                }
            }
        }

        // loop income.
        foreach ($earned as $currency) {
            $currencyId        = $currency['currency_id'];
            $sums[$currencyId] = $sums[$currencyId] ?? [
                    'currency_id'             => $currency['currency_id'],
                    'currency_symbol'         => $currency['currency_symbol'],
                    'currency_name'           => $currency['currency_name'],
                    'currency_decimal_places' => $currency['currency_decimal_places'],
                    'spent_sum'               => '0',
                    'earned_sum'              => '0',
                    'total_sum'               => '0',
                ];
            foreach ($currency['categories'] as $category) {
                foreach ($category['transaction_journals'] as $journal) {
                    $destinationAccountId                                               = $journal['destination_account_id'];
                    $report[$destinationAccountId]['currencies'][$currencyId]           = $report[$destinationAccountId]['currencies'][$currencyId] ?? [
                            'currency_id'             => $currency['currency_id'],
                            'currency_symbol'         => $currency['currency_symbol'],
                            'currency_name'           => $currency['currency_name'],
                            'currency_decimal_places' => $currency['currency_decimal_places'],
                            'spent'                   => '0',
                            'earned'                  => '0',
                            'sum'                     => '0',
                        ];
                    $report[$destinationAccountId]['currencies'][$currencyId]['earned'] = bcadd(
                        $report[$destinationAccountId]['currencies'][$currencyId]['earned'], $journal['amount']
                    );
                    $report[$destinationAccountId]['currencies'][$currencyId]['sum']    = bcadd(
                        $report[$destinationAccountId]['currencies'][$currencyId]['sum'], $journal['amount']
                    );
                    $sums[$currencyId]['earned_sum']                                    = bcadd($sums[$currencyId]['earned_sum'], $journal['amount']);
                    $sums[$currencyId]['total_sum']                                     = bcadd($sums[$currencyId]['total_sum'], $journal['amount']);
                }
            }
        }

        return view('reports.category.partials.accounts', compact('sums', 'report'));
    }

    /**
     * @param Collection $accounts
     * @param Collection $categories
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return array|string
     */
    public function avgExpenses(Collection $accounts, Collection $categories, Carbon $start, Carbon $end)
    {
        $spent  = $this->opsRepository->listExpenses($start, $end, $accounts, $categories);
        $result = [];
        foreach ($spent as $currency) {
            $currencyId = $currency['currency_id'];
            foreach ($currency['categories'] as $category) {
                foreach ($category['transaction_journals'] as $journal) {
                    $destinationId = $journal['destination_account_id'];
                    $key           = sprintf('%d-%d', $destinationId, $currency['currency_id']);
                    $result[$key]  = $result[$key] ?? [
                            'transactions'             => 0,
                            'sum'                      => '0',
                            'avg'                      => '0',
                            'avg_float'                => 0,
                            'destination_account_name' => $journal['destination_account_name'],
                            'destination_account_id'   => $journal['destination_account_id'],
                            'currency_id'              => $currency['currency_id'],
                            'currency_name'            => $currency['currency_name'],
                            'currency_symbol'          => $currency['currency_symbol'],
                            'currency_decimal_places'  => $currency['currency_decimal_places'],
                        ];
                    $result[$key]['transactions']++;
                    $result[$key]['sum']       = bcadd($journal['amount'], $result[$key]['sum']);
                    $result[$key]['avg']       = bcdiv($result[$key]['sum'], (string)$result[$key]['transactions']);
                    $result[$key]['avg_float'] = (float)$result[$key]['avg'];
                }
            }
        }
        // sort by amount_float
        // sort temp array by amount.
        $amounts = array_column($result, 'avg_float');
        array_multisort($amounts, SORT_ASC, $result);

        try {
            $result = view('reports.category.partials.avg-expenses', compact('result'))->render();
            // @codeCoverageIgnoreStart
        } catch (Throwable $e) {
            Log::debug(sprintf('Could not render reports.partials.budget-period: %s', $e->getMessage()));
            $result = sprintf('Could not render view: %s', $e->getMessage());
        }

        return $result;
    }

    /**
     * @param Collection $accounts
     * @param Collection $categories
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return array|string
     */
    public function avgIncome(Collection $accounts, Collection $categories, Carbon $start, Carbon $end)
    {
        $spent  = $this->opsRepository->listIncome($start, $end, $accounts, $categories);
        $result = [];
        foreach ($spent as $currency) {
            $currencyId = $currency['currency_id'];
            foreach ($currency['categories'] as $category) {
                foreach ($category['transaction_journals'] as $journal) {
                    $sourceId     = $journal['source_account_id'];
                    $key          = sprintf('%d-%d', $sourceId, $currency['currency_id']);
                    $result[$key] = $result[$key] ?? [
                            'transactions'            => 0,
                            'sum'                     => '0',
                            'avg'                     => '0',
                            'avg_float'               => 0,
                            'source_account_name'     => $journal['source_account_name'],
                            'source_account_id'       => $journal['source_account_id'],
                            'currency_id'             => $currency['currency_id'],
                            'currency_name'           => $currency['currency_name'],
                            'currency_symbol'         => $currency['currency_symbol'],
                            'currency_decimal_places' => $currency['currency_decimal_places'],
                        ];
                    $result[$key]['transactions']++;
                    $result[$key]['sum']       = bcadd($journal['amount'], $result[$key]['sum']);
                    $result[$key]['avg']       = bcdiv($result[$key]['sum'], (string)$result[$key]['transactions']);
                    $result[$key]['avg_float'] = (float)$result[$key]['avg'];
                }
            }
        }
        // sort by amount_float
        // sort temp array by amount.
        $amounts = array_column($result, 'avg_float');
        array_multisort($amounts, SORT_DESC, $result);

        try {
            $result = view('reports.category.partials.avg-income', compact('result'))->render();
            // @codeCoverageIgnoreStart
        } catch (Throwable $e) {
            Log::debug(sprintf('Could not render reports.partials.budget-period: %s', $e->getMessage()));
            $result = sprintf('Could not render view: %s', $e->getMessage());
        }

        return $result;
    }

    /**
     * @param Collection $accounts
     * @param Collection $categories
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return Factory|View
     */
    public function categories(Collection $accounts, Collection $categories, Carbon $start, Carbon $end)
    {
        $spent  = $this->opsRepository->listExpenses($start, $end, $accounts, $categories);
        $earned = $this->opsRepository->listIncome($start, $end, $accounts, $categories);
        $sums   = [];
        $report = [];
        /** @var Category $category */
        foreach ($categories as $category) {
            $categoryId          = $category->id;
            $report[$categoryId] = $report[$categoryId] ?? [
                    'name'       => $category->name,
                    'id'         => $category->id,
                    'currencies' => [],
                ];
        }
        foreach ($spent as $currency) {
            $currencyId        = $currency['currency_id'];
            $sums[$currencyId] = $sums[$currencyId] ?? [
                    'currency_id'             => $currency['currency_id'],
                    'currency_symbol'         => $currency['currency_symbol'],
                    'currency_name'           => $currency['currency_name'],
                    'currency_decimal_places' => $currency['currency_decimal_places'],
                    'earned_sum'              => '0',
                    'spent_sum'               => '0',
                    'total_sum'               => '0',
                ];
            /** @var array $category */
            foreach ($currency['categories'] as $category) {
                $categoryId = $category['id'];

                foreach ($category['transaction_journals'] as $journal) {
                    // add currency info to report array:
                    $report[$categoryId]['currencies'][$currencyId]          = $report[$categoryId]['currencies'][$currencyId] ?? [
                            'spent'                   => '0',
                            'earned'                  => '0',
                            'sum'                     => '0',
                            'currency_id'             => $currency['currency_id'],
                            'currency_symbol'         => $currency['currency_symbol'],
                            'currency_name'           => $currency['currency_name'],
                            'currency_decimal_places' => $currency['currency_decimal_places'],
                        ];
                    $report[$categoryId]['currencies'][$currencyId]['spent'] = bcadd(
                        $report[$categoryId]['currencies'][$currencyId]['spent'], $journal['amount']
                    );
                    $report[$categoryId]['currencies'][$currencyId]['sum']   = bcadd(
                        $report[$categoryId]['currencies'][$currencyId]['sum'], $journal['amount']
                    );

                    $sums[$currencyId]['spent_sum'] = bcadd($sums[$currencyId]['spent_sum'], $journal['amount']);
                    $sums[$currencyId]['total_sum'] = bcadd($sums[$currencyId]['total_sum'], $journal['amount']);
                }
            }
        }

        foreach ($earned as $currency) {
            $currencyId        = $currency['currency_id'];
            $sums[$currencyId] = $sums[$currencyId] ?? [
                    'currency_id'             => $currency['currency_id'],
                    'currency_symbol'         => $currency['currency_symbol'],
                    'currency_name'           => $currency['currency_name'],
                    'currency_decimal_places' => $currency['currency_decimal_places'],
                    'earned_sum'              => '0',
                    'spent_sum'               => '0',
                    'total_sum'               => '0',
                ];
            /** @var array $category */
            foreach ($currency['categories'] as $category) {
                $categoryId = $category['id'];

                foreach ($category['transaction_journals'] as $journal) {
                    // add currency info to report array:
                    $report[$categoryId]['currencies'][$currencyId]           = $report[$categoryId]['currencies'][$currencyId] ?? [
                            'spent'                   => '0',
                            'earned'                  => '0',
                            'sum'                     => '0',
                            'currency_id'             => $currency['currency_id'],
                            'currency_symbol'         => $currency['currency_symbol'],
                            'currency_name'           => $currency['currency_name'],
                            'currency_decimal_places' => $currency['currency_decimal_places'],
                        ];
                    $report[$categoryId]['currencies'][$currencyId]['earned'] = bcadd(
                        $report[$categoryId]['currencies'][$currencyId]['earned'], $journal['amount']
                    );
                    $report[$categoryId]['currencies'][$currencyId]['sum']    = bcadd(
                        $report[$categoryId]['currencies'][$currencyId]['sum'], $journal['amount']
                    );

                    $sums[$currencyId]['earned_sum'] = bcadd($sums[$currencyId]['earned_sum'], $journal['amount']);
                    $sums[$currencyId]['total_sum']  = bcadd($sums[$currencyId]['total_sum'], $journal['amount']);
                }
            }
        }

        return view('reports.category.partials.categories', compact('sums', 'report'));
    }

    /**
     * Show overview of expenses in category.
     *
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return mixed|string
     */
    public function expenses(Collection $accounts, Carbon $start, Carbon $end)
    {
        $cache = new CacheProperties;
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty('category-period-expenses-report');
        $cache->addProperty($accounts->pluck('id')->toArray());
        if ($cache->has()) {
            return $cache->get(); // @codeCoverageIgnore
        }
        /** @var CategoryRepositoryInterface $repository */
        $repository = app(CategoryRepositoryInterface::class);

        /** @var OperationsRepositoryInterface $opsRepository */
        $opsRepository = app(OperationsRepositoryInterface::class);

        /** @var NoCategoryRepositoryInterface $noCatRepos */
        $noCatRepos = app(NoCategoryRepositoryInterface::class);

        // depending on the carbon format (a reliable way to determine the general date difference)
        // change the "listOfPeriods" call so the entire period gets included correctly.
        $format = app('navigation')->preferredCarbonFormat($start, $end);

        if ('Y' === $format) {
            $start->startOfYear();
        }
        if ('Y-m' === $format) {
            $start->startOfMonth();
        }

        $periods = app('navigation')->listOfPeriods($start, $end);
        $data    = [];
        $with    = $opsRepository->listExpenses($start, $end, $accounts);
        $without = $noCatRepos->listExpenses($start, $end, $accounts);

        foreach ($with as $currencyId => $currencyRow) {
            foreach ($currencyRow['categories'] as $categoryId => $categoryRow) {
                $key        = sprintf('%d-%d', $currencyId, $categoryId);
                $data[$key] = $data[$key] ?? [
                        'id'                      => $categoryRow['id'],
                        'title'                   => sprintf('%s (%s)', $categoryRow['name'], $currencyRow['currency_name']),
                        'currency_id'             => $currencyRow['currency_id'],
                        'currency_symbol'         => $currencyRow['currency_symbol'],
                        'currency_name'           => $currencyRow['currency_name'],
                        'currency_code'           => $currencyRow['currency_code'],
                        'currency_decimal_places' => $currencyRow['currency_decimal_places'],
                        'sum'                     => '0',
                        'entries'                 => [],

                    ];
                foreach ($categoryRow['transaction_journals'] as $journalId => $journal) {
                    $date                         = $journal['date']->format($format);
                    $data[$key]['entries'][$date] = $data[$key]['entries'][$date] ?? '0';
                    $data[$key]['entries'][$date] = bcadd($data[$key]['entries'][$date], $journal['amount']);
                    $data[$key]['sum']            = bcadd($data[$key]['sum'], $journal['amount']);
                }
            }
        }
        foreach ($without as $currencyId => $currencyRow) {
            $key        = sprintf('0-%d', $currencyId);
            $data[$key] = $data[$key] ?? [
                    'id'                      => 0,
                    'title'                   => sprintf('%s (%s)', trans('firefly.noCategory'), $currencyRow['currency_name']),
                    'currency_id'             => $currencyRow['currency_id'],
                    'currency_symbol'         => $currencyRow['currency_symbol'],
                    'currency_name'           => $currencyRow['currency_name'],
                    'currency_code'           => $currencyRow['currency_code'],
                    'currency_decimal_places' => $currencyRow['currency_decimal_places'],
                    'sum'                     => '0',
                    'entries'                 => [],
                ];
            foreach ($currencyRow['transaction_journals'] as $journalId => $journal) {
                $date                         = $journal['date']->format($format);
                $data[$key]['entries'][$date] = $data[$key]['entries'][$date] ?? '0';
                $data[$key]['entries'][$date] = bcadd($data[$key]['entries'][$date], $journal['amount']);
                $data[$key]['sum']            = bcadd($data[$key]['sum'], $journal['amount']);
            }
        }
        $cache->store($data);

        $report = $data;

        try {
            $result = view('reports.partials.category-period', compact('report', 'periods'))->render();
            // @codeCoverageIgnoreStart
        } catch (Throwable $e) {
            Log::error(sprintf('Could not render category::expenses: %s', $e->getMessage()));
            $result = sprintf('An error prevented Firefly III from rendering: %s. Apologies.', $e->getMessage());
        }
        // @codeCoverageIgnoreEnd

        $cache->store($result);

        return $result;
    }

    /**
     * Show overview of income in category.
     *
     * @param Collection $accounts
     *
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return string
     */
    public function income(Collection $accounts, Carbon $start, Carbon $end): string
    {
        $cache = new CacheProperties;
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty('category-period-income-report');
        $cache->addProperty($accounts->pluck('id')->toArray());
        if ($cache->has()) {
            return $cache->get(); // @codeCoverageIgnore
        }

        /** @var OperationsRepositoryInterface $opsRepository */
        $opsRepository = app(OperationsRepositoryInterface::class);

        /** @var NoCategoryRepositoryInterface $noCatRepos */
        $noCatRepos = app(NoCategoryRepositoryInterface::class);

        // depending on the carbon format (a reliable way to determine the general date difference)
        // change the "listOfPeriods" call so the entire period gets included correctly.
        $format = app('navigation')->preferredCarbonFormat($start, $end);

        if ('Y' === $format) {
            $start->startOfYear();
        }
        if ('Y-m' === $format) {
            $start->startOfMonth();
        }

        $periods = app('navigation')->listOfPeriods($start, $end);
        $data    = [];
        $with    = $opsRepository->listIncome($start, $end, $accounts);
        $without = $noCatRepos->listIncome($start, $end, $accounts);

        foreach ($with as $currencyId => $currencyRow) {
            foreach ($currencyRow['categories'] as $categoryId => $categoryRow) {
                $key        = sprintf('%d-%d', $currencyId, $categoryId);
                $data[$key] = $data[$key] ?? [
                        'id'                      => $categoryRow['id'],
                        'title'                   => sprintf('%s (%s)', $categoryRow['name'], $currencyRow['currency_name']),
                        'currency_id'             => $currencyRow['currency_id'],
                        'currency_symbol'         => $currencyRow['currency_symbol'],
                        'currency_name'           => $currencyRow['currency_name'],
                        'currency_code'           => $currencyRow['currency_code'],
                        'currency_decimal_places' => $currencyRow['currency_decimal_places'],
                        'sum'                     => '0',
                        'entries'                 => [],

                    ];
                foreach ($categoryRow['transaction_journals'] as $journalId => $journal) {
                    $date                         = $journal['date']->format($format);
                    $data[$key]['entries'][$date] = $data[$key]['entries'][$date] ?? '0';
                    $data[$key]['entries'][$date] = bcadd($data[$key]['entries'][$date], $journal['amount']);
                    $data[$key]['sum']            = bcadd($data[$key]['sum'], $journal['amount']);
                }
            }
        }
        foreach ($without as $currencyId => $currencyRow) {
            $key        = sprintf('0-%d', $currencyId);
            $data[$key] = $data[$key] ?? [
                    'id'                      => 0,
                    'title'                   => sprintf('%s (%s)', trans('firefly.noCategory'), $currencyRow['currency_name']),
                    'currency_id'             => $currencyRow['currency_id'],
                    'currency_symbol'         => $currencyRow['currency_symbol'],
                    'currency_name'           => $currencyRow['currency_name'],
                    'currency_code'           => $currencyRow['currency_code'],
                    'currency_decimal_places' => $currencyRow['currency_decimal_places'],
                    'sum'                     => '0',
                    'entries'                 => [],
                ];
            foreach ($currencyRow['transaction_journals'] as $journalId => $journal) {
                $date                         = $journal['date']->format($format);
                $data[$key]['entries'][$date] = $data[$key]['entries'][$date] ?? '0';
                $data[$key]['entries'][$date] = bcadd($data[$key]['entries'][$date], $journal['amount']);
                $data[$key]['sum']            = bcadd($data[$key]['sum'], $journal['amount']);
            }
        }
        $cache->store($data);

        $report = $data;

        try {
            $result = view('reports.partials.category-period', compact('report', 'periods'))->render();
            // @codeCoverageIgnoreStart
        } catch (Throwable $e) {
            Log::error(sprintf('Could not render category::expenses: %s', $e->getMessage()));
            $result = sprintf('An error prevented Firefly III from rendering: %s. Apologies.', $e->getMessage());
        }
        // @codeCoverageIgnoreEnd

        $cache->store($result);

        return $result;
    }

    /**
     * Show overview of operations.
     *
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return mixed|string
     *
     */
    public function operations(Collection $accounts, Carbon $start, Carbon $end)
    {
        // chart properties for cache:
        $cache = new CacheProperties;
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty('category-report');
        $cache->addProperty($accounts->pluck('id')->toArray());
        if ($cache->has()) {
            return $cache->get(); // @codeCoverageIgnore
        }

        /** @var CategoryRepositoryInterface $repository */
        $repository = app(CategoryRepositoryInterface::class);

        /** @var OperationsRepositoryInterface $opsRepository */
        $opsRepository = app(OperationsRepositoryInterface::class);

        /** @var NoCategoryRepositoryInterface $noCatRepository */
        $noCatRepository = app(NoCategoryRepositoryInterface::class);

        $categories    = $repository->getCategories();
        $earnedWith    = $opsRepository->listIncome($start, $end, $accounts);
        $spentWith     = $opsRepository->listExpenses($start, $end, $accounts);
        $earnedWithout = $noCatRepository->listIncome($start, $end, $accounts);
        $spentWithout  = $noCatRepository->listExpenses($start, $end, $accounts);

        $report = [
            'categories' => [],
            'sums'       => [],
        ];

        // needs four for-each loops.
        // TODO improve this.
        foreach ([$earnedWith, $spentWith] as $data) {
            foreach ($data as $currencyId => $currencyRow) {
                $report['sums'][$currencyId] = $report['sums'][$currencyId] ?? [
                        'spent'                   => '0',
                        'earned'                  => '0',
                        'sum'                     => '0',
                        'currency_id'             => $currencyRow['currency_id'],
                        'currency_symbol'         => $currencyRow['currency_symbol'],
                        'currency_name'           => $currencyRow['currency_name'],
                        'currency_code'           => $currencyRow['currency_code'],
                        'currency_decimal_places' => $currencyRow['currency_decimal_places'],
                    ];


                foreach ($currencyRow['categories'] as $categoryId => $categoryRow) {
                    $key                        = sprintf('%s-%s', $currencyId, $categoryId);
                    $report['categories'][$key] = $report['categories'][$key] ?? [
                            'id'                      => $categoryId,
                            'title'                   => sprintf('%s (%s)', $categoryRow['name'], $currencyRow['currency_name']),
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
                        $report['sums'][$currencyId]['sum'] = bcadd($report['sums'][$currencyId]['sum'], $journal['amount']);
                        // sum of spent:
                        $report['sums'][$currencyId]['spent'] = -1 === bccomp($journal['amount'], '0') ? bcadd(
                            $report['sums'][$currencyId]['spent'], $journal['amount']
                        ) : $report['sums'][$currencyId]['spent'];
                        // sum of earned
                        $report['sums'][$currencyId]['earned'] = 1 === bccomp($journal['amount'], '0') ? bcadd(
                            $report['sums'][$currencyId]['earned'], $journal['amount']
                        ) : $report['sums'][$currencyId]['earned'];

                        // sum of category
                        $report['categories'][$key]['sum'] = bcadd($report['categories'][$key]['sum'], $journal['amount']);
                        // total spent in category
                        $report['categories'][$key]['spent'] = -1 === bccomp($journal['amount'], '0') ? bcadd(
                            $report['categories'][$key]['spent'], $journal['amount']
                        ) : $report['categories'][$key]['spent'];
                        // total earned in category
                        $report['categories'][$key]['earned'] = 1 === bccomp($journal['amount'], '0') ? bcadd(
                            $report['categories'][$key]['earned'], $journal['amount']
                        ) : $report['categories'][$key]['earned'];
                    }
                }
            }
        }
        foreach ([$earnedWithout, $spentWithout] as $data) {
            foreach ($data as $currencyId => $currencyRow) {
                $report['sums'][$currencyId] = $report['sums'][$currencyId] ?? [
                        'spent'                   => '0',
                        'earned'                  => '0',
                        'sum'                     => '0',
                        'currency_id'             => $currencyRow['currency_id'],
                        'currency_symbol'         => $currencyRow['currency_symbol'],
                        'currency_name'           => $currencyRow['currency_name'],
                        'currency_code'           => $currencyRow['currency_code'],
                        'currency_decimal_places' => $currencyRow['currency_decimal_places'],
                    ];
                $key                         = sprintf('%s-0', $currencyId);
                $report['categories'][$key]  = $report['categories'][$key] ?? [
                        'id'                      => 0,
                        'title'                   => sprintf('%s (%s)', trans('firefly.noCategory'), $currencyRow['currency_name']),
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
                foreach ($currencyRow['transaction_journals'] as $journal) {
                    // sum of all
                    $report['sums'][$currencyId]['sum'] = bcadd($report['sums'][$currencyId]['sum'], $journal['amount']);

                    // sum of spent:
                    $report['sums'][$currencyId]['spent'] = -1 === bccomp($journal['amount'], '0') ? bcadd(
                        $report['sums'][$currencyId]['spent'], $journal['amount']
                    ) : $report['sums'][$currencyId]['spent'];
                    // sum of earned
                    $report['sums'][$currencyId]['earned'] = 1 === bccomp($journal['amount'], '0') ? bcadd(
                        $report['sums'][$currencyId]['earned'], $journal['amount']
                    ) : $report['sums'][$currencyId]['earned'];

                    // sum of category
                    $report['categories'][$key]['sum'] = bcadd($report['categories'][$key]['sum'], $journal['amount']);
                    // total spent in no category
                    $report['categories'][$key]['spent'] = -1 === bccomp($journal['amount'], '0') ? bcadd(
                        $report['categories'][$key]['spent'], $journal['amount']
                    ) : $report['categories'][$key]['spent'];
                    // total earned in no category
                    $report['categories'][$key]['earned'] = 1 === bccomp($journal['amount'], '0') ? bcadd(
                        $report['categories'][$key]['earned'], $journal['amount']
                    ) : $report['categories'][$key]['earned'];
                }
            }
        }

        // @codeCoverageIgnoreStart
        try {
            $result = view('reports.partials.categories', compact('report'))->render();
            $cache->store($result);
        } catch (Throwable $e) {
            Log::error(sprintf('Could not render category::expenses: %s', $e->getMessage()));
            $result = sprintf('An error prevented Firefly III from rendering: %s. Apologies.', $e->getMessage());
        }

        // @codeCoverageIgnoreEnd

        return $result;
    }

    /**
     * @param Collection $accounts
     * @param Collection $categories
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return array|string
     */
    public function topExpenses(Collection $accounts, Collection $categories, Carbon $start, Carbon $end)
    {
        $spent  = $this->opsRepository->listExpenses($start, $end, $accounts, $categories);
        $result = [];
        foreach ($spent as $currency) {
            $currencyId = $currency['currency_id'];
            foreach ($currency['categories'] as $category) {
                foreach ($category['transaction_journals'] as $journal) {
                    $result[] = [
                        'description'              => $journal['description'],
                        'transaction_group_id'     => $journal['transaction_group_id'],
                        'amount_float'             => (float)$journal['amount'],
                        'amount'                   => $journal['amount'],
                        'date'                     => $journal['date']->formatLocalized($this->monthAndDayFormat),
                        'destination_account_name' => $journal['destination_account_name'],
                        'destination_account_id'   => $journal['destination_account_id'],
                        'currency_id'              => $currency['currency_id'],
                        'currency_name'            => $currency['currency_name'],
                        'currency_symbol'          => $currency['currency_symbol'],
                        'currency_decimal_places'  => $currency['currency_decimal_places'],
                        'category_id'              => $category['id'],
                        'category_name'            => $category['name'],
                    ];
                }
            }
        }
        // sort by amount_float
        // sort temp array by amount.
        $amounts = array_column($result, 'amount_float');
        array_multisort($amounts, SORT_ASC, $result);

        try {
            $result = view('reports.category.partials.top-expenses', compact('result'))->render();
            // @codeCoverageIgnoreStart
        } catch (Throwable $e) {
            Log::debug(sprintf('Could not render reports.partials.budget-period: %s', $e->getMessage()));
            $result = sprintf('Could not render view: %s', $e->getMessage());
        }

        return $result;
    }

    /**
     * @param Collection $accounts
     * @param Collection $categories
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return array|string
     */
    public function topIncome(Collection $accounts, Collection $categories, Carbon $start, Carbon $end)
    {
        $spent  = $this->opsRepository->listIncome($start, $end, $accounts, $categories);
        $result = [];
        foreach ($spent as $currency) {
            $currencyId = $currency['currency_id'];
            foreach ($currency['categories'] as $category) {
                foreach ($category['transaction_journals'] as $journal) {
                    $result[] = [
                        'description'             => $journal['description'],
                        'transaction_group_id'    => $journal['transaction_group_id'],
                        'amount_float'            => (float)$journal['amount'],
                        'amount'                  => $journal['amount'],
                        'date'                    => $journal['date']->formatLocalized($this->monthAndDayFormat),
                        'source_account_name'     => $journal['source_account_name'],
                        'source_account_id'       => $journal['source_account_id'],
                        'currency_id'             => $currency['currency_id'],
                        'currency_name'           => $currency['currency_name'],
                        'currency_symbol'         => $currency['currency_symbol'],
                        'currency_decimal_places' => $currency['currency_decimal_places'],
                        'category_id'             => $category['id'],
                        'category_name'           => $category['name'],
                    ];
                }
            }
        }
        // sort by amount_float
        // sort temp array by amount.
        $amounts = array_column($result, 'amount_float');
        array_multisort($amounts, SORT_DESC, $result);

        try {
            $result = view('reports.category.partials.top-income', compact('result'))->render();
            // @codeCoverageIgnoreStart
        } catch (Throwable $e) {
            Log::debug(sprintf('Could not render reports.partials.budget-period: %s', $e->getMessage()));
            $result = sprintf('Could not render view: %s', $e->getMessage());
        }

        return $result;
    }

    /**
     * @param array $array
     *
     * @return bool
     */
    private function noAmountInArray(array $array): bool
    {
        if (0 === count($array)) {
            return true;
        }

        return false;
    }


}
