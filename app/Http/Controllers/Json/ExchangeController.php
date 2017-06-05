<?php
/**
 * ExchangeController.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
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