<?php

/**
 * TransactionController.php
 * Copyright (c) 2020 james@firefly-iii.org
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
use FireflyIII\Helpers\Collector\GroupCollectorInterface;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Models\TransactionType;
use FireflyIII\Support\CacheProperties;
use Illuminate\Http\JsonResponse;

/**
 * Class TransactionController
 */
class TransactionController extends Controller
{
    /** @var GeneratorInterface Chart generation methods. */
    protected $generator;

    /**
     * TransactionController constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->generator = app(GeneratorInterface::class);
    }

    /**
     * @return JsonResponse
     */
    public function budgets(Carbon $start, Carbon $end)
    {
        $cache     = new CacheProperties();
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty('chart.transactions.budgets');
        if ($cache->has()) {
            return response()->json($cache->get());
        }

        /** @var GroupCollectorInterface $collector */
        $collector = app(GroupCollectorInterface::class);
        $collector->setRange($start, $end);
        $collector->withBudgetInformation();
        $collector->setTypes([TransactionType::WITHDRAWAL]);

        $result    = $collector->getExtractedJournals();
        $data      = [];

        // group by category.
        /** @var array $journal */
        foreach ($result as $journal) {
            $budget                 = $journal['budget_name'] ?? (string) trans('firefly.no_budget');
            $title                  = sprintf('%s (%s)', $budget, $journal['currency_symbol']);
            $data[$title] ??= [
                'amount'          => '0',
                'currency_symbol' => $journal['currency_symbol'],
                'currency_code'   => $journal['currency_code'],
            ];
            $data[$title]['amount'] = bcadd($data[$title]['amount'], $journal['amount']);
        }
        $chart     = $this->generator->multiCurrencyPieChart($data);
        $cache->store($chart);

        return response()->json($chart);
    }

    /**
     * @return JsonResponse
     */
    public function categories(string $objectType, Carbon $start, Carbon $end)
    {
        $cache     = new CacheProperties();
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty($objectType);
        $cache->addProperty('chart.transactions.categories');
        if ($cache->has()) {
            return response()->json($cache->get());
        }

        /** @var GroupCollectorInterface $collector */
        $collector = app(GroupCollectorInterface::class);
        $collector->setRange($start, $end);
        $collector->withCategoryInformation();

        if ('withdrawal' === $objectType) {
            $collector->setTypes([TransactionType::WITHDRAWAL]);
        }
        if ('deposit' === $objectType) {
            $collector->setTypes([TransactionType::DEPOSIT]);
        }
        if ('transfer' === $objectType || 'transfers' === $objectType) {
            $collector->setTypes([TransactionType::TRANSFER]);
        }

        $result    = $collector->getExtractedJournals();
        $data      = [];

        // group by category.
        /** @var array $journal */
        foreach ($result as $journal) {
            $category               = $journal['category_name'] ?? (string) trans('firefly.no_category');
            $title                  = sprintf('%s (%s)', $category, $journal['currency_symbol']);
            $data[$title] ??= [
                'amount'          => '0',
                'currency_symbol' => $journal['currency_symbol'],
                'currency_code'   => $journal['currency_code'],
            ];
            $data[$title]['amount'] = bcadd($data[$title]['amount'], $journal['amount']);
        }
        $chart     = $this->generator->multiCurrencyPieChart($data);
        $cache->store($chart);

        return response()->json($chart);
    }

    /**
     * @return JsonResponse
     */
    public function destinationAccounts(string $objectType, Carbon $start, Carbon $end)
    {
        $cache     = new CacheProperties();
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty($objectType);
        $cache->addProperty('chart.transactions.destinations');
        if ($cache->has()) {
            return response()->json($cache->get());
        }

        /** @var GroupCollectorInterface $collector */
        $collector = app(GroupCollectorInterface::class);
        $collector->setRange($start, $end);
        $collector->withAccountInformation();

        if ('withdrawal' === $objectType) {
            $collector->setTypes([TransactionType::WITHDRAWAL]);
        }
        if ('deposit' === $objectType) {
            $collector->setTypes([TransactionType::DEPOSIT]);
        }
        if ('transfer' === $objectType || 'transfers' === $objectType) {
            $collector->setTypes([TransactionType::TRANSFER]);
        }

        $result    = $collector->getExtractedJournals();
        $data      = [];

        // group by category.
        /** @var array $journal */
        foreach ($result as $journal) {
            $name                   = $journal['destination_account_name'];
            $title                  = sprintf('%s (%s)', $name, $journal['currency_symbol']);
            $data[$title] ??= [
                'amount'          => '0',
                'currency_symbol' => $journal['currency_symbol'],
                'currency_code'   => $journal['currency_code'],
            ];
            $data[$title]['amount'] = bcadd($data[$title]['amount'], $journal['amount']);
        }
        $chart     = $this->generator->multiCurrencyPieChart($data);
        $cache->store($chart);

        return response()->json($chart);
    }

    /**
     * @return JsonResponse
     */
    public function sourceAccounts(string $objectType, Carbon $start, Carbon $end)
    {
        $cache     = new CacheProperties();
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty($objectType);
        $cache->addProperty('chart.transactions.sources');
        if ($cache->has()) {
            return response()->json($cache->get());
        }

        /** @var GroupCollectorInterface $collector */
        $collector = app(GroupCollectorInterface::class);
        $collector->setRange($start, $end);
        $collector->withAccountInformation();

        if ('withdrawal' === $objectType) {
            $collector->setTypes([TransactionType::WITHDRAWAL]);
        }
        if ('deposit' === $objectType) {
            $collector->setTypes([TransactionType::DEPOSIT]);
        }
        if ('transfer' === $objectType || 'transfers' === $objectType) {
            $collector->setTypes([TransactionType::TRANSFER]);
        }

        $result    = $collector->getExtractedJournals();
        $data      = [];

        // group by category.
        /** @var array $journal */
        foreach ($result as $journal) {
            $name                   = $journal['source_account_name'];
            $title                  = sprintf('%s (%s)', $name, $journal['currency_symbol']);
            $data[$title] ??= [
                'amount'          => '0',
                'currency_symbol' => $journal['currency_symbol'],
                'currency_code'   => $journal['currency_code'],
            ];
            $data[$title]['amount'] = bcadd($data[$title]['amount'], $journal['amount']);
        }
        $chart     = $this->generator->multiCurrencyPieChart($data);
        $cache->store($chart);

        return response()->json($chart);
    }
}
