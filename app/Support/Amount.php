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
use NumberFormatter;
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
     * @param TransactionCurrency $format
     * @param string              $amount
     * @param bool                $coloured
     *
     * @return string
     */
    public function formatAnything(TransactionCurrency $format, string $amount, bool $coloured = true): string
    {
        $locale    = setlocale(LC_MONETARY, 0);
        $float     = round($amount, 2);
        $formatter = new NumberFormatter($locale, NumberFormatter::CURRENCY);
        $result    = $formatter->formatCurrency($float, $format->code);

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
     *
     * @param \FireflyIII\Models\TransactionJournal $journal
     * @param bool                                  $coloured
     *
     * @return string
     */
    public function formatJournal(TransactionJournal $journal, bool $coloured = true): string
    {
        $locale       = setlocale(LC_MONETARY, 0);
        $float        = round(TransactionJournal::amount($journal), 2);
        $formatter    = new NumberFormatter($locale, NumberFormatter::CURRENCY);
        $currencyCode = $journal->transaction_currency_code ?? $journal->transactionCurrency->code;
        $result       = $formatter->formatCurrency($float, $currencyCode);

        if ($coloured === true && $float === 0.00) {
            return '<span style="color:#999">' . $result . '</span>'; // always grey.
        }
        if (!$coloured) {
            return $result;
        }
        if (!$journal->isTransfer()) {
            if ($float > 0) {
                return '<span class="text-success">' . $result . '</span>';
            }

            return '<span class="text-danger">' . $result . '</span>';
        } else {
            return '<span class="text-info">' . $result . '</span>';
        }
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
     * This method will properly format the given number, in color or "black and white",
     * as a currency, given two things: the currency required and the currency code.
     *
     * @param string $code
     * @param string $amount
     * @param bool   $coloured
     *
     * @return string
     */
    public function formatWithCode(string $code, string $amount, bool $coloured = true): string
    {
        $locale    = setlocale(LC_MONETARY, 0);
        $float     = round($amount, 2);
        $formatter = new NumberFormatter($locale, NumberFormatter::CURRENCY);
        $result    = $formatter->formatCurrency($float, $code);

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
