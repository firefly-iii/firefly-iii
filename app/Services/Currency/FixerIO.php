<?php
/**
 * FixerIO.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace FireflyIII\Services\Currency;


use Carbon\Carbon;
use FireflyIII\Models\CurrencyExchangeRate;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\User;
use Log;
use Requests;
use Requests_Exception;

/**
 * Class FixerIO
 *
 * @package FireflyIII\Services\Currency
 */
class FixerIO implements ExchangeRateInterface
{
    /** @var  User */
    protected $user;

    public function getRate(TransactionCurrency $fromCurrency, TransactionCurrency $toCurrency, Carbon $date): CurrencyExchangeRate
    {
        $uri        = sprintf('https://api.fixer.io/%s?base=%s&symbols=%s', $date->format('Y-m-d'), $fromCurrency->code, $toCurrency->code);
        $statusCode = -1;
        $body       = '';
        try {
            $result     = Requests::get($uri);
            $statusCode = $result->status_code;
            $body       = $result->body;
        } catch (Requests_Exception $e) {
            // don't care about error
            $body = sprintf('Requests_Exception: %s', $e->getMessage());
        }
        // Requests_Exception
        $rate    = 1.0;
        $content = null;
        if ($statusCode !== 200) {
            Log::error(sprintf('Something went wrong. Received error code %d and body "%s" from FixerIO.', $statusCode, $body));
        }
        // get rate from body:
        if ($statusCode === 200) {
            $content = json_decode($body, true);
        }
        if (!is_null($content)) {
            $code = $toCurrency->code;
            $rate = isset($content['rates'][$code]) ? $content['rates'][$code] : '1';
        }

        // create new currency exchange rate object:
        $exchangeRate = new CurrencyExchangeRate;
        $exchangeRate->user()->associate($this->user);
        $exchangeRate->fromCurrency()->associate($fromCurrency);
        $exchangeRate->toCurrency()->associate($toCurrency);
        $exchangeRate->date = $date;
        $exchangeRate->rate = $rate;
        $exchangeRate->save();

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
