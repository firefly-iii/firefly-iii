<?php
/**
 * ExchangeController.php
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
 * along with Firefly III.  If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace FireflyIII\Http\Controllers\Json;

use Carbon\Carbon;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use FireflyIII\Services\Currency\ExchangeRateInterface;
use Illuminate\Http\Request;
use Log;
use Response;

/**
 * Class ExchangeController
 *
 * @package FireflyIII\Http\Controllers\Json
 */
class ExchangeController extends Controller
{
    /**
     * @param Request             $request
     * @param TransactionCurrency $fromCurrency
     * @param TransactionCurrency $toCurrency
     * @param Carbon              $date
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getRate(Request $request, TransactionCurrency $fromCurrency, TransactionCurrency $toCurrency, Carbon $date)
    {
        /** @var CurrencyRepositoryInterface $repository */
        $repository = app(CurrencyRepositoryInterface::class);
        $rate       = $repository->getExchangeRate($fromCurrency, $toCurrency, $date);
        if (is_null($rate->id)) {
            Log::debug(sprintf('No cached exchange rate in database for %s to %s on %s', $fromCurrency->code, $toCurrency->code, $date->format('Y-m-d')));
            $preferred = env('EXCHANGE_RATE_SERVICE', config('firefly.preferred_exchange_service'));
            $class     = config('firefly.currency_exchange_services.' . $preferred);
            /** @var ExchangeRateInterface $object */
            $object = app($class);
            $object->setUser(auth()->user());
            $rate = $object->getRate($fromCurrency, $toCurrency, $date);
        }
        $return           = $rate->toArray();
        $return['amount'] = null;
        if (!is_null($request->get('amount'))) {
            // assume amount is in "from" currency:
            $return['amount'] = bcmul($request->get('amount'), strval($rate->rate), 12);
            // round to toCurrency decimal places:
            $return['amount'] = round($return['amount'], $toCurrency->decimal_places);
        }

        return Response::json($return);
    }
}
