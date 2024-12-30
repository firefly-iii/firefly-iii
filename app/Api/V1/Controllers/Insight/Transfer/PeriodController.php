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

namespace FireflyIII\Api\V1\Controllers\Insight\Transfer;

use FireflyIII\Api\V1\Controllers\Controller;
use FireflyIII\Api\V1\Requests\Insight\GenericRequest;
use FireflyIII\Helpers\Collector\GroupCollectorInterface;
use FireflyIII\Models\TransactionType;
use FireflyIII\Support\Facades\Amount;
use Illuminate\Http\JsonResponse;

/**
 * Class PeriodController
 */
class PeriodController extends Controller
{
    /**
     * This endpoint is documented at:
     * https://api-docs.firefly-iii.org/?urls.primaryName=2.0.0%20(v1)#/insight/insightTransferTotal
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
        $collector->setTypes([TransactionType::TRANSFER])->setRange($start, $end)->setDestinationAccounts($accounts);
        $genericSet      = $collector->getExtractedJournals();
        foreach ($genericSet as $journal) {
            // currency
            $currencyId                                = $journal['currency_id'];
            $currencyCode                              = $journal['currency_code'];
            $field                                     = $convertToNative && $currencyId !== $default->id ? 'native_amount' : 'amount';

            // perhaps use default currency instead?
            if ($convertToNative && $journal['currency_id'] !== $default->id) {
                $currencyId   = $default->id;
                $currencyCode = $default->code;
            }
            // use foreign amount when the foreign currency IS the default currency.
            if ($convertToNative && $journal['currency_id'] !== $default->id && $default->id === $journal['foreign_currency_id']) {
                $field = 'foreign_amount';
            }

            $response[$currencyId] ??= [
                'difference'       => '0',
                'difference_float' => 0,
                'currency_id'      => (string) $currencyId,
                'currency_code'    => $currencyCode,
            ];
            $response[$currencyId]['difference']       = bcadd($response[$currencyId]['difference'], app('steam')->positive($journal[$field]));
            $response[$currencyId]['difference_float'] = (float) $response[$currencyId]['difference'];

        }

        return response()->json(array_values($response));
    }
}
