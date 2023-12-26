<?php

/*
 * BalanceController.php
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

namespace FireflyIII\Api\V2\Controllers\Chart;

use Carbon\Carbon;
use FireflyIII\Api\V2\Controllers\Controller;
use FireflyIII\Api\V2\Request\Chart\BalanceChartRequest;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Helpers\Collector\GroupCollectorInterface;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Models\TransactionType;
use FireflyIII\Support\Http\Api\AccountBalanceGrouped;
use FireflyIII\Support\Http\Api\CleansChartData;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;

/**
 * Class BalanceController
 */
class BalanceController extends Controller
{
    use CleansChartData;

    /**
     * The code is practically a duplicate of ReportController::operations.
     *
     * Currency is up to the account/transactions in question, but conversion to the default
     * currency is possible.
     *
     * If the transaction being processed is already in native currency OR if the
     * foreign amount is in the native currency, the amount will not be converted.
     *
     * TODO validate and set user_group_id
     * TODO collector set group, not user
     *
     * @throws FireflyException
     */
    public function balance(BalanceChartRequest $request): JsonResponse
    {
        $params = $request->getAll();

        /** @var Carbon $start */
        $start = $this->parameters->get('start');

        /** @var Carbon $end */
        $end = $this->parameters->get('end');
        $end->endOfDay();

        /** @var Collection $accounts */
        $accounts       = $params['accounts'];

        /** @var string $preferredRange */
        $preferredRange = $params['period'];

        // prepare for currency conversion and data collection:
        /** @var TransactionCurrency $default */
        $default    = app('amount')->getDefaultCurrency();

        // get journals for entire period:
        /** @var GroupCollectorInterface $collector */
        $collector = app(GroupCollectorInterface::class);
        $collector->setRange($start, $end)->withAccountInformation();
        $collector->setXorAccounts($accounts);
        $collector->setTypes([TransactionType::WITHDRAWAL, TransactionType::DEPOSIT, TransactionType::RECONCILIATION, TransactionType::TRANSFER]);
        $journals = $collector->getExtractedJournals();

        $object = new AccountBalanceGrouped();
        $object->setPreferredRange($preferredRange);
        $object->setDefault($default);
        $object->setAccounts($accounts);
        $object->setJournals($journals);
        $object->setStart($start);
        $object->setEnd($end);
        $object->groupByCurrencyAndPeriod();
        $chartData = $object->convertToChartData();

        return response()->json($this->clean($chartData));
    }
}
