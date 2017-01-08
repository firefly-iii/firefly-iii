<?php
/**
 * Amount.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Support;

use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Models\TransactionJournal;
use Illuminate\Support\Collection;
use Log;
use Preferences as Prefs;

/**
 * Class Amount
 *
 * @package FireflyIII\Support
 */
class Amount
{

    /**
     * @param string $amount
     * @param bool   $coloured
     *
     * @return string
     */
    public function format(string $amount, bool $coloured = true): string
    {
        return $this->formatAnything($this->getDefaultCurrency(), $amount, $coloured);
    }

    /**
     * This method will properly format the given number, in color or "black and white",
     * as a currency, given two things: the currency required and the current locale.
     *
     * @param \FireflyIII\Models\TransactionCurrency $format
     * @param string                                 $amount
     * @param bool                                   $coloured
     *
     * @return string
     */
    public function formatAnything(TransactionCurrency $format, string $amount, bool $coloured = true): string
    {
        $locale = explode(',', trans('config.locale'));
        $locale = array_map('trim', $locale);
        setlocale(LC_MONETARY, $locale);
        $float     = round($amount, 12);
        $info      = localeconv();
        $formatted = number_format($float, $format->decimal_places, $info['mon_decimal_point'], $info['mon_thousands_sep']);

        // some complicated switches to format the amount correctly:
        $precedes  = $amount < 0 ? $info['n_cs_precedes'] : $info['p_cs_precedes'];
        $separated = $amount < 0 ? $info['n_sep_by_space'] : $info['p_sep_by_space'];
        $space     = $separated ? ' ' : '';
        $result    = $format->symbol . $space . $formatted;

        if (!$precedes) {
            $result = $space . $formatted . $format->symbol;
        }

        if ($coloured === true) {

            if ($amount > 0) {
                return sprintf('<span class="text-success">%s</span>', $result);
            } else {
                if ($amount < 0) {
                    return sprintf('<span class="text-danger">%s</span>', $result);
                }
            }

            return sprintf('<span style="color:#999">%s</span>', $result);


        }

        return $result;
    }

    /**
     * Used in many places (unfortunately).
     *
     * @param string $currencyCode
     * @param string $amount
     * @param bool   $coloured
     *
     * @return string
     */
    public function formatByCode(string $currencyCode, string $amount, bool $coloured = true): string
    {
        $currency = TransactionCurrency::whereCode($currencyCode)->first();

        return $this->formatAnything($currency, $amount, $coloured);
    }

    /**
     *
     * @param \FireflyIII\Models\TransactionJournal $journal
     * @param bool                                  $coloured
     *
     * @return string
     */
    public function formatJournal(TransactionJournal $journal, bool $coloured = true): string
    {
        $currency = $journal->transactionCurrency;

        return $this->formatAnything($currency, TransactionJournal::amount($journal), $coloured);
    }

    /**
     * @param Transaction $transaction
     * @param bool        $coloured
     *
     * @return string
     */
    public function formatTransaction(Transaction $transaction, bool $coloured = true)
    {
        $currency = $transaction->transactionJournal->transactionCurrency;

        return $this->formatAnything($currency, strval($transaction->amount), $coloured);
    }

    /**
     * @return Collection
     */
    public function getAllCurrencies(): Collection
    {
        return TransactionCurrency::orderBy('code', 'ASC')->get();
    }

    /**
     * @return string
     */
    public function getCurrencyCode(): string
    {

        $cache = new CacheProperties;
        $cache->addProperty('getCurrencyCode');
        if ($cache->has()) {
            return $cache->get();
        } else {
            $currencyPreference = Prefs::get('currencyPreference', config('firefly.default_currency', 'EUR'));

            $currency = TransactionCurrency::whereCode($currencyPreference->data)->first();
            if ($currency) {

                $cache->store($currency->code);

                return $currency->code;
            }
            $cache->store(config('firefly.default_currency', 'EUR'));

            return config('firefly.default_currency', 'EUR');
        }
    }

    /**
     * @return string
     */
    public function getCurrencySymbol(): string
    {
        $cache = new CacheProperties;
        $cache->addProperty('getCurrencySymbol');
        if ($cache->has()) {
            return $cache->get();
        } else {
            $currencyPreference = Prefs::get('currencyPreference', config('firefly.default_currency', 'EUR'));
            $currency           = TransactionCurrency::whereCode($currencyPreference->data)->first();

            $cache->store($currency->symbol);

            return $currency->symbol;
        }
    }

    /**
     * @return \FireflyIII\Models\TransactionCurrency
     */
    public function getDefaultCurrency(): TransactionCurrency
    {
        $cache = new CacheProperties;
        $cache->addProperty('getDefaultCurrency');
        if ($cache->has()) {
            return $cache->get();
        }
        $currencyPreference = Prefs::get('currencyPreference', config('firefly.default_currency', 'EUR'));
        $currency           = TransactionCurrency::whereCode($currencyPreference->data)->first();
        $cache->store($currency);

        return $currency;
    }
}
