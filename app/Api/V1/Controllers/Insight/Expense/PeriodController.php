<?php

/*
 * PeriodController.php
 * Copyright (c) 2021 james@firefly-iii.org
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

namespace FireflyIII\Api\V1\Controllers\Insight\Expense;

use FireflyIII\Api\V1\Controllers\Controller;
use FireflyIII\Api\V1\Requests\Insight\GenericRequest;
use FireflyIII\Enums\TransactionTypeEnum;
use FireflyIII\Helpers\Collector\GroupCollectorInterface;
use FireflyIII\Support\Facades\Amount;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * Class PeriodController
 */
class PeriodController extends Controller
{
    /**
     * This endpoint is documented at:
     * https://api-docs.firefly-iii.org/?urls.primaryName=2.0.0%20(v1)#/insight/insightExpenseTotal
     */
    public function total(GenericRequest $request): JsonResponse
    {
        $accounts        = $request->getAssetAccounts();
        $start           = $request->getStart();
        $end             = $request->getEnd();
        $response        = [];
        $convertToNative = Amount::convertToNative();
        $default         = Amount::getDefaultCurrency();

        // collect all expenses in this period (regardless of type)
        $collector       = app(GroupCollectorInterface::class);
        $collector->setTypes([TransactionTypeEnum::WITHDRAWAL->value])->setRange($start, $end)->setSourceAccounts($accounts);
        $genericSet      = $collector->getExtractedJournals();
        foreach ($genericSet as $journal) {
            // same code as many other sumExpense methods. I think this needs some kind of generic method.
            $amount                                    = '0';
            $currencyId                                = (int) $journal['currency_id'];
            $currencyCode                              = $journal['currency_code'];
            if ($convertToNative) {
                $amount = Amount::getAmountFromJournal($journal);
                if ($default->id !== (int) $journal['currency_id'] && $default->id !== (int) $journal['foreign_currency_id']) {
                    $currencyId   = $default->id;
                    $currencyCode = $default->code;
                }
                if ($default->id !== (int) $journal['currency_id'] && $default->id === (int) $journal['foreign_currency_id']) {
                    $currencyId   = $journal['foreign_currency_id'];
                    $currencyCode = $journal['foreign_currency_code'];
                }
                Log::debug(sprintf('[a] Add amount %s %s', $currencyCode, $amount));
            }
            if (!$convertToNative) {
                // ignore the amount in foreign currency.
                Log::debug(sprintf('[b] Add amount %s %s', $currencyCode, $journal['amount']));
                $amount = $journal['amount'];
            }


            $response[$currencyId] ??= [
                'difference'       => '0',
                'difference_float' => 0,
                'currency_id'      => (string) $currencyId,
                'currency_code'    => $currencyCode,
            ];
            $response[$currencyId]['difference']       = bcadd($response[$currencyId]['difference'], $amount);
            $response[$currencyId]['difference_float'] = (float) $response[$currencyId]['difference']; // intentional float
        }

        return response()->json(array_values($response));
    }
}
