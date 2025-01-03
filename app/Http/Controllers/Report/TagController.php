<?php

/**
 * TagController.php
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

namespace FireflyIII\Http\Controllers\Report;

use Carbon\Carbon;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Models\Account;
use FireflyIII\Models\Tag;
use FireflyIII\Repositories\Tag\OperationsRepositoryInterface;
use Illuminate\Contracts\View\Factory;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * Class TagController
 */
class TagController extends Controller
{
    private OperationsRepositoryInterface $opsRepository;

    /**
     * ExpenseReportController constructor.
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
     * @return Factory|View
     *
     * @SuppressWarnings("PHPMD.ExcessiveMethodLength")
     */
    public function accountPerTag(Collection $accounts, Collection $tags, Carbon $start, Carbon $end)
    {
        $spent  = $this->opsRepository->listExpenses($start, $end, $accounts, $tags);
        $earned = $this->opsRepository->listIncome($start, $end, $accounts, $tags);
        $report = [];

        /** @var Account $account */
        foreach ($accounts as $account) {
            $accountId = $account->id;
            $report[$accountId] ??= [
                'name'       => $account->name,
                'id'         => $account->id,
                'iban'       => $account->iban,
                'currencies' => [],
            ];
        }

        // loop expenses.
        foreach ($spent as $currency) {
            $currencyId = $currency['currency_id'];

            /** @var array $tag */
            foreach ($currency['tags'] as $tag) {
                $tagId = $tag['id'];
                foreach ($tag['transaction_journals'] as $journal) {
                    $sourceAccountId                                                              = $journal['source_account_id'];
                    $report[$sourceAccountId]['currencies'][$currencyId]                          ??= [
                        'currency_id'             => $currency['currency_id'],
                        'currency_symbol'         => $currency['currency_symbol'],
                        'currency_name'           => $currency['currency_name'],
                        'currency_decimal_places' => $currency['currency_decimal_places'],
                        'tags'                    => [],
                    ];

                    $report[$sourceAccountId]['currencies'][$currencyId]['tags'][$tagId]
                                                                                                  ??= [
                                                                                                      'spent'  => '0',
                                                                                                      'earned' => '0',
                                                                                                      'sum'    => '0',
                                                                                                  ];
                    $report[$sourceAccountId]['currencies'][$currencyId]['tags'][$tagId]['spent'] = bcadd(
                        $report[$sourceAccountId]['currencies'][$currencyId]['tags'][$tagId]['spent'],
                        $journal['amount']
                    );
                    $report[$sourceAccountId]['currencies'][$currencyId]['tags'][$tagId]['sum']   = bcadd(
                        $report[$sourceAccountId]['currencies'][$currencyId]['tags'][$tagId]['sum'],
                        $journal['amount']
                    );
                }
            }
        }
        // loop income.
        foreach ($earned as $currency) {
            $currencyId = $currency['currency_id'];

            /** @var array $tag */
            foreach ($currency['tags'] as $tag) {
                $tagId = $tag['id'];
                foreach ($tag['transaction_journals'] as $journal) {
                    $destinationId                                                               = $journal['destination_account_id'];
                    $report[$destinationId]['currencies'][$currencyId]
                                                                                                 ??= [
                                                                                                     'currency_id'             => $currency['currency_id'],
                                                                                                     'currency_symbol'         => $currency['currency_symbol'],
                                                                                                     'currency_name'           => $currency['currency_name'],
                                                                                                     'currency_decimal_places' => $currency['currency_decimal_places'],
                                                                                                     'tags'                    => [],
                                                                                                 ];
                    $report[$destinationId]['currencies'][$currencyId]['tags'][$tagId]
                                                                                                 ??= [
                                                                                                     'spent'  => '0',
                                                                                                     'earned' => '0',
                                                                                                     'sum'    => '0',
                                                                                                 ];
                    $report[$destinationId]['currencies'][$currencyId]['tags'][$tagId]['earned'] = bcadd(
                        $report[$destinationId]['currencies'][$currencyId]['tags'][$tagId]['earned'],
                        $journal['amount']
                    );
                    $report[$destinationId]['currencies'][$currencyId]['tags'][$tagId]['sum']    = bcadd(
                        $report[$destinationId]['currencies'][$currencyId]['tags'][$tagId]['sum'],
                        $journal['amount']
                    );
                }
            }
        }

        return view('reports.tag.partials.account-per-tag', compact('report', 'tags'));
    }

    /**
     * @return Factory|View
     *
     * @SuppressWarnings("PHPMD.ExcessiveMethodLength")
     */
    public function accounts(Collection $accounts, Collection $tags, Carbon $start, Carbon $end)
    {
        $spent  = $this->opsRepository->listExpenses($start, $end, $accounts, $tags);
        $earned = $this->opsRepository->listIncome($start, $end, $accounts, $tags);
        $report = [];
        $sums   = [];

        /** @var Account $account */
        foreach ($accounts as $account) {
            $accountId = $account->id;
            $report[$accountId] ??= [
                'name'       => $account->name,
                'id'         => $account->id,
                'iban'       => $account->iban,
                'currencies' => [],
            ];
        }

        // loop expenses.
        foreach ($spent as $currency) {
            $currencyId = $currency['currency_id'];
            $sums[$currencyId] ??= [
                'currency_id'             => $currency['currency_id'],
                'currency_symbol'         => $currency['currency_symbol'],
                'currency_name'           => $currency['currency_name'],
                'currency_decimal_places' => $currency['currency_decimal_places'],
                'spent_sum'               => '0',
                'earned_sum'              => '0',
                'total_sum'               => '0',
            ];
            foreach ($currency['tags'] as $tag) {
                foreach ($tag['transaction_journals'] as $journal) {
                    $sourceAccountId                                              = $journal['source_account_id'];
                    $report[$sourceAccountId]['currencies'][$currencyId] ??= [
                        'currency_id'             => $currency['currency_id'],
                        'currency_symbol'         => $currency['currency_symbol'],
                        'currency_name'           => $currency['currency_name'],
                        'currency_decimal_places' => $currency['currency_decimal_places'],
                        'spent'                   => '0',
                        'earned'                  => '0',
                        'sum'                     => '0',
                    ];
                    $report[$sourceAccountId]['currencies'][$currencyId]['spent'] = bcadd(
                        $report[$sourceAccountId]['currencies'][$currencyId]['spent'],
                        $journal['amount']
                    );
                    $report[$sourceAccountId]['currencies'][$currencyId]['sum']   = bcadd(
                        $report[$sourceAccountId]['currencies'][$currencyId]['sum'],
                        $journal['amount']
                    );
                    $sums[$currencyId]['spent_sum']                               = bcadd($sums[$currencyId]['spent_sum'], $journal['amount']);
                    $sums[$currencyId]['total_sum']                               = bcadd($sums[$currencyId]['total_sum'], $journal['amount']);
                }
            }
        }

        // loop income.
        foreach ($earned as $currency) {
            $currencyId = $currency['currency_id'];
            $sums[$currencyId] ??= [
                'currency_id'             => $currency['currency_id'],
                'currency_symbol'         => $currency['currency_symbol'],
                'currency_name'           => $currency['currency_name'],
                'currency_decimal_places' => $currency['currency_decimal_places'],
                'spent_sum'               => '0',
                'earned_sum'              => '0',
                'total_sum'               => '0',
            ];
            foreach ($currency['tags'] as $tag) {
                foreach ($tag['transaction_journals'] as $journal) {
                    $destinationAccountId                                               = $journal['destination_account_id'];
                    $report[$destinationAccountId]['currencies'][$currencyId] ??= [
                        'currency_id'             => $currency['currency_id'],
                        'currency_symbol'         => $currency['currency_symbol'],
                        'currency_name'           => $currency['currency_name'],
                        'currency_decimal_places' => $currency['currency_decimal_places'],
                        'spent'                   => '0',
                        'earned'                  => '0',
                        'sum'                     => '0',
                    ];
                    $report[$destinationAccountId]['currencies'][$currencyId]['earned'] = bcadd(
                        $report[$destinationAccountId]['currencies'][$currencyId]['earned'],
                        $journal['amount']
                    );
                    $report[$destinationAccountId]['currencies'][$currencyId]['sum']    = bcadd(
                        $report[$destinationAccountId]['currencies'][$currencyId]['sum'],
                        $journal['amount']
                    );
                    $sums[$currencyId]['earned_sum']                                    = bcadd($sums[$currencyId]['earned_sum'], $journal['amount']);
                    $sums[$currencyId]['total_sum']                                     = bcadd($sums[$currencyId]['total_sum'], $journal['amount']);
                }
            }
        }

        return view('reports.tag.partials.accounts', compact('sums', 'report'));
    }

    /**
     * @return string
     *
     * @throws FireflyException
     */
    public function avgExpenses(Collection $accounts, Collection $tags, Carbon $start, Carbon $end)
    {
        $spent   = $this->opsRepository->listExpenses($start, $end, $accounts, $tags);
        $result  = [];
        foreach ($spent as $currency) {
            foreach ($currency['tags'] as $tag) {
                foreach ($tag['transaction_journals'] as $journal) {
                    $destinationId             = $journal['destination_account_id'];
                    $key                       = sprintf('%d-%d', $destinationId, $currency['currency_id']);
                    $result[$key] ??= [
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
                    ++$result[$key]['transactions'];
                    $result[$key]['sum']       = bcadd($journal['amount'], $result[$key]['sum']);
                    $result[$key]['avg']       = bcdiv($result[$key]['sum'], (string) $result[$key]['transactions']);
                    $result[$key]['avg_float'] = (float) $result[$key]['avg'];
                }
            }
        }
        // sort by amount_float
        // sort temp array by amount.
        $amounts = array_column($result, 'avg_float');
        array_multisort($amounts, SORT_ASC, $result);

        try {
            $result = view('reports.tag.partials.avg-expenses', compact('result'))->render();
        } catch (\Throwable $e) {
            app('log')->debug(sprintf('Could not render reports.partials.budget-period: %s', $e->getMessage()));
            $result = sprintf('Could not render view: %s', $e->getMessage());

            throw new FireflyException($result, 0, $e);
        }

        return $result;
    }

    /**
     * @return string
     *
     * @throws FireflyException
     */
    public function avgIncome(Collection $accounts, Collection $tags, Carbon $start, Carbon $end)
    {
        $spent   = $this->opsRepository->listIncome($start, $end, $accounts, $tags);
        $result  = [];
        foreach ($spent as $currency) {
            foreach ($currency['tags'] as $tag) {
                foreach ($tag['transaction_journals'] as $journal) {
                    $sourceId                  = $journal['source_account_id'];
                    $key                       = sprintf('%d-%d', $sourceId, $currency['currency_id']);
                    $result[$key] ??= [
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
                    ++$result[$key]['transactions'];
                    $result[$key]['sum']       = bcadd($journal['amount'], $result[$key]['sum']);
                    $result[$key]['avg']       = bcdiv($result[$key]['sum'], (string) $result[$key]['transactions']);
                    $result[$key]['avg_float'] = (float) $result[$key]['avg'];
                }
            }
        }
        // sort by amount_float
        // sort temp array by amount.
        $amounts = array_column($result, 'avg_float');
        array_multisort($amounts, SORT_DESC, $result);

        try {
            $result = view('reports.tag.partials.avg-income', compact('result'))->render();
        } catch (\Throwable $e) {
            app('log')->debug(sprintf('Could not render reports.partials.budget-period: %s', $e->getMessage()));
            $result = sprintf('Could not render view: %s', $e->getMessage());

            throw new FireflyException($result, 0, $e);
        }

        return $result;
    }

    /**
     * @return Factory|View
     *
     * @SuppressWarnings("PHPMD.ExcessiveMethodLength")
     */
    public function tags(Collection $accounts, Collection $tags, Carbon $start, Carbon $end)
    {
        $spent  = $this->opsRepository->listExpenses($start, $end, $accounts, $tags);
        $earned = $this->opsRepository->listIncome($start, $end, $accounts, $tags);
        $sums   = [];
        $report = [];

        /** @var Tag $tag */
        foreach ($tags as $tag) {
            $tagId = $tag->id;
            $report[$tagId] ??= [
                'name'       => $tag->tag,
                'id'         => $tag->id,
                'currencies' => [],
            ];
        }
        foreach ($spent as $currency) {
            $currencyId = $currency['currency_id'];
            $sums[$currencyId] ??= [
                'currency_id'             => $currency['currency_id'],
                'currency_symbol'         => $currency['currency_symbol'],
                'currency_name'           => $currency['currency_name'],
                'currency_decimal_places' => $currency['currency_decimal_places'],
                'earned_sum'              => '0',
                'spent_sum'               => '0',
                'total_sum'               => '0',
            ];

            /** @var array $tag */
            foreach ($currency['tags'] as $tag) {
                $tagId = $tag['id'];
                if (!array_key_exists($tagId, $report)) {
                    continue;
                }
                foreach ($tag['transaction_journals'] as $journal) {
                    // add currency info to report array:
                    $report[$tagId]['currencies'][$currencyId] ??= [
                        'spent'                   => '0',
                        'earned'                  => '0',
                        $tagId                    => $tagId,
                        'sum'                     => '0',
                        'currency_id'             => $currency['currency_id'],
                        'currency_symbol'         => $currency['currency_symbol'],
                        'currency_name'           => $currency['currency_name'],
                        'currency_decimal_places' => $currency['currency_decimal_places'],
                    ];
                    $report[$tagId]['currencies'][$currencyId]['spent'] = bcadd($report[$tagId]['currencies'][$currencyId]['spent'], $journal['amount']);
                    $report[$tagId]['currencies'][$currencyId]['sum']   = bcadd($report[$tagId]['currencies'][$currencyId]['sum'], $journal['amount']);
                    $sums[$currencyId]['spent_sum']                     = bcadd($sums[$currencyId]['spent_sum'], $journal['amount']);
                    $sums[$currencyId]['total_sum']                     = bcadd($sums[$currencyId]['total_sum'], $journal['amount']);
                }
            }
        }

        foreach ($earned as $currency) {
            $currencyId = $currency['currency_id'];
            $sums[$currencyId] ??= [
                'currency_id'             => $currency['currency_id'],
                'currency_symbol'         => $currency['currency_symbol'],
                'currency_name'           => $currency['currency_name'],
                'currency_decimal_places' => $currency['currency_decimal_places'],
                'earned_sum'              => '0',
                'spent_sum'               => '0',
                'total_sum'               => '0',
            ];

            /** @var array $tag */
            foreach ($currency['tags'] as $tag) {
                $tagId = $tag['id'];
                if (!array_key_exists($tagId, $report)) {
                    continue;
                }
                foreach ($tag['transaction_journals'] as $journal) {
                    // add currency info to report array:
                    $report[$tagId]['currencies'][$currencyId] ??= [
                        'spent'                   => '0',
                        'earned'                  => '0',
                        'sum'                     => '0',
                        $tagId                    => $tagId,
                        'currency_id'             => $currency['currency_id'],
                        'currency_symbol'         => $currency['currency_symbol'],
                        'currency_name'           => $currency['currency_name'],
                        'currency_decimal_places' => $currency['currency_decimal_places'],
                    ];
                    $report[$tagId]['currencies'][$currencyId]['earned'] = bcadd($report[$tagId]['currencies'][$currencyId]['earned'], $journal['amount']);
                    $report[$tagId]['currencies'][$currencyId]['sum']    = bcadd($report[$tagId]['currencies'][$currencyId]['sum'], $journal['amount']);
                    $sums[$currencyId]['earned_sum']                     = bcadd($sums[$currencyId]['earned_sum'], $journal['amount']);
                    $sums[$currencyId]['total_sum']                      = bcadd($sums[$currencyId]['total_sum'], $journal['amount']);
                }
            }
        }

        return view('reports.tag.partials.tags', compact('sums', 'report'));
    }

    /**
     * @return string
     *
     * @throws FireflyException
     */
    public function topExpenses(Collection $accounts, Collection $tags, Carbon $start, Carbon $end)
    {
        $spent   = $this->opsRepository->listExpenses($start, $end, $accounts, $tags);
        $result  = [];
        foreach ($spent as $currency) {
            foreach ($currency['tags'] as $tag) {
                foreach ($tag['transaction_journals'] as $journal) {
                    $result[] = [
                        'description'              => $journal['description'],
                        'transaction_group_id'     => $journal['transaction_group_id'],
                        'amount_float'             => (float) $journal['amount'],
                        'amount'                   => $journal['amount'],
                        'date'                     => $journal['date']->isoFormat($this->monthAndDayFormat),
                        'date_sort'                => $journal['date']->format('Y-m-d'),
                        'destination_account_name' => $journal['destination_account_name'],
                        'destination_account_id'   => $journal['destination_account_id'],
                        'currency_id'              => $currency['currency_id'],
                        'currency_name'            => $currency['currency_name'],
                        'currency_symbol'          => $currency['currency_symbol'],
                        'currency_decimal_places'  => $currency['currency_decimal_places'],
                        'tag_id'                   => $tag['id'],
                        'tag_name'                 => $tag['name'],
                    ];
                }
            }
        }
        // sort by amount_float
        // sort temp array by amount.
        $amounts = array_column($result, 'amount_float');
        array_multisort($amounts, SORT_ASC, $result);

        try {
            $result = view('reports.tag.partials.top-expenses', compact('result'))->render();
        } catch (\Throwable $e) {
            app('log')->debug(sprintf('Could not render reports.partials.budget-period: %s', $e->getMessage()));
            $result = sprintf('Could not render view: %s', $e->getMessage());

            throw new FireflyException($result, 0, $e);
        }

        return $result;
    }

    /**
     * @return string
     *
     * @throws FireflyException
     */
    public function topIncome(Collection $accounts, Collection $tags, Carbon $start, Carbon $end)
    {
        $spent   = $this->opsRepository->listIncome($start, $end, $accounts, $tags);
        $result  = [];
        foreach ($spent as $currency) {
            foreach ($currency['tags'] as $tag) {
                foreach ($tag['transaction_journals'] as $journal) {
                    $result[] = [
                        'description'             => $journal['description'],
                        'transaction_group_id'    => $journal['transaction_group_id'],
                        'amount_float'            => (float) $journal['amount'], // intentional float.
                        'amount'                  => $journal['amount'],
                        'date'                    => $journal['date']->isoFormat($this->monthAndDayFormat),
                        'date_sort'               => $journal['date']->format('Y-m-d'),
                        'source_account_name'     => $journal['source_account_name'],
                        'source_account_id'       => $journal['source_account_id'],
                        'currency_id'             => $currency['currency_id'],
                        'currency_name'           => $currency['currency_name'],
                        'currency_symbol'         => $currency['currency_symbol'],
                        'currency_decimal_places' => $currency['currency_decimal_places'],
                        'tag_id'                  => $tag['id'],
                        'tag_name'                => $tag['name'],
                    ];
                }
            }
        }
        // sort by amount_float
        // sort temp array by amount.
        $amounts = array_column($result, 'amount_float');
        array_multisort($amounts, SORT_DESC, $result);

        try {
            $result = view('reports.tag.partials.top-income', compact('result'))->render();
        } catch (\Throwable $e) {
            app('log')->debug(sprintf('Could not render reports.partials.budget-period: %s', $e->getMessage()));
            $result = sprintf('Could not render view: %s', $e->getMessage());

            throw new FireflyException($result, 0, $e);
        }

        return $result;
    }
}
