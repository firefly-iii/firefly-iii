<?php
/**
 * RatesApiIOv1.php
 * Copyright (c) 2019 thegrumpydictator@gmail.com
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

namespace FireflyIII\Services\Currency;

use Carbon\Carbon;
use Exception;
use FireflyIII\Models\CurrencyExchangeRate;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\User;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Log;

/**
 * Class RatesApiIOv1.
 * @codeCoverageIgnore
 */
class RatesApiIOv1 implements ExchangeRateInterface
{
    /** @var User */
    protected $user;

    /**
     * Constructor.
     */
    public function __construct()
    {
        if ('testing' === config('app.env')) {
            Log::warning(sprintf('%s should not be instantiated in the TEST environment!', get_class($this)));
        }
    }

    /**
     * @param TransactionCurrency $fromCurrency
     * @param TransactionCurrency $toCurrency
     * @param Carbon              $date
     *
     * @return CurrencyExchangeRate
     */
    public function getRate(TransactionCurrency $fromCurrency, TransactionCurrency $toCurrency, Carbon $date): CurrencyExchangeRate
    {
        // create new exchange rate with default values.
        $rate         = 0;
        $exchangeRate = new CurrencyExchangeRate;
        $exchangeRate->user()->associate($this->user);
        $exchangeRate->fromCurrency()->associate($fromCurrency);
        $exchangeRate->toCurrency()->associate($toCurrency);
        $exchangeRate->date       = $date;
        $exchangeRate->rate       = $rate;
        $exchangeRate->updated_at = new Carbon;
        $exchangeRate->created_at = new Carbon;

        // build URI
        $uri = sprintf(
            'https://api.ratesapi.io/api/%s?base=%s&symbols=%s',
            $date->format('Y-m-d'), $fromCurrency->code, $toCurrency->code
        );
        Log::debug(sprintf('Going to request exchange rate using URI %s', $uri));
        $client = new Client;
        try {
            $res        = $client->request('GET', $uri);
            $statusCode = $res->getStatusCode();
            $body       = $res->getBody()->getContents();
        } catch (GuzzleException|Exception $e) {
            // don't care about error
            $body       = sprintf('Guzzle exception: %s', $e->getMessage());
            $statusCode = 500;
        }
        Log::debug(sprintf('Result status code is %d', $statusCode));
        Log::debug(sprintf('Result body is: %s', $body));

        $content = null;
        if (200 !== $statusCode) {
            Log::error(sprintf('Something went wrong. Received error code %d and body "%s" from RatesApiIO.', $statusCode, $body));
        }
        $success = false;
        // get rate from body:
        if (200 === $statusCode) {
            $content = json_decode($body, true);
            $success = true;
        }
        if (null !== $content && true === $success) {
            $code = $toCurrency->code;
            $rate = (float)($content['rates'][$code] ?? 0);
            Log::debug('Got the following rates from RatesApi: ', $content['rates'] ?? []);
        }

        $exchangeRate->rate = $rate;
        if (0.0 !== $rate) {
            Log::debug('Rate is not zero, save it!');
            $exchangeRate->save();
        }

        return $exchangeRate;
    }

    /**
     * @param User $user
     *
     * @return mixed|void
     */
    public function setUser(User $user)
    {
        $this->user = $user;
    }
}
