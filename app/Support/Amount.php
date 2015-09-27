<?php

namespace FireflyIII\Support;

use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Models\TransactionJournal;
use Illuminate\Support\Collection;
use Preferences as Prefs;

/**
 * Class Amount
 *
 * @package FireflyIII\Support
 */
class Amount
{
    /**
     * @param      $amount
     * @param bool $coloured
     *
     * @return string
     */
    public function format($amount, $coloured = true)
    {
        $currencySymbol = $this->getCurrencySymbol();

        return $this->formatWithSymbol($currencySymbol, $amount, $coloured);


    }

    /**
     * @return string
     */
    public function getCurrencySymbol()
    {
        $cache = new CacheProperties;
        $cache->addProperty('getCurrencySymbol');
        if ($cache->has()) {
            return $cache->get();
        } else {
            $currencyPreference = Prefs::get('currencyPreference', 'EUR');
            $currency           = TransactionCurrency::whereCode($currencyPreference->data)->first();

            $cache->store($currency->symbol);

            return $currency->symbol;
        }
    }

    /**
     * @param string $symbol
     * @param float  $amount
     * @param bool   $coloured
     *
     * @return string
     */
    public function formatWithSymbol($symbol, $amount, $coloured = true)
    {
        $amount = floatval($amount);
        $amount = round($amount, 2);
        $string = number_format($amount, 2, ',', '.');

        if ($coloured === true) {
            if ($amount === 0.0) {
                return '<span style="color:#999">' . $symbol . '&nbsp;' . $string . '</span>';
            }
            if ($amount > 0) {
                return '<span class="text-success">' . $symbol . '&nbsp;' . $string . '</span>';
            }

            return '<span class="text-danger">' . $symbol . '&nbsp;' . $string . '</span>';
        }

        return $symbol . '&nbsp;' . $string;
    }

    /**
     *
     * @param TransactionJournal $journal
     * @param bool               $coloured
     *
     * @return string
     */
    public function formatJournal(TransactionJournal $journal, $coloured = true)
    {
        $cache = new CacheProperties;
        $cache->addProperty($journal->id);
        $cache->addProperty('formatJournal');

        if ($cache->has()) {
            return $cache->get(); // @codeCoverageIgnore
        }


        if (is_null($journal->symbol)) {
            $symbol = $journal->transactionCurrency->symbol;
        } else {
            $symbol = $journal->symbol;
        }
        $amount = $journal->amount_positive;
        if ($journal->transactionType->type == 'Transfer' && $coloured) {
            $txt = '<span class="text-info">' . $this->formatWithSymbol($symbol, $amount, false) . '</span>';
            $cache->store($txt);

            return $txt;
        }
        if ($journal->transactionType->type == 'Transfer' && !$coloured) {
            $txt = $this->formatWithSymbol($symbol, $amount, false);
            $cache->store($txt);

            return $txt;
        }

        $txt = $this->formatWithSymbol($symbol, $amount, $coloured);
        $cache->store($txt);

        return $txt;
    }

    /**
     * @param Transaction $transaction
     * @param bool        $coloured
     *
     * @return string
     */
    public function formatTransaction(Transaction $transaction, $coloured = true)
    {
        $symbol = $transaction->transactionJournal->transactionCurrency->symbol;
        $amount = floatval($transaction->amount);

        return $this->formatWithSymbol($symbol, $amount, $coloured);
    }

    /**
     * @return Collection
     */
    public function getAllCurrencies()
    {
        return TransactionCurrency::orderBy('code', 'ASC')->get();
    }

    /**
     * @return string
     */
    public function getCurrencyCode()
    {

        $cache = new CacheProperties;
        $cache->addProperty('getCurrencyCode');
        if ($cache->has()) {
            return $cache->get();
        } else {
            $currencyPreference = Prefs::get('currencyPreference', 'EUR');

            $currency = TransactionCurrency::whereCode($currencyPreference->data)->first();
            if ($currency) {

                $cache->store($currency->code);

                return $currency->code;
            }
            $cache->store('EUR');

            return 'EUR'; // @codeCoverageIgnore
        }
    }

    /**
     * @return mixed|static
     */
    public function getDefaultCurrency()
    {
        $cache = new CacheProperties;
        $cache->addProperty('getDefaultCurrency');
        if ($cache->has()) {
            return $cache->get();
        }
        $currencyPreference = Prefs::get('currencyPreference', 'EUR');
        $currency           = TransactionCurrency::whereCode($currencyPreference->data)->first();
        $cache->store($currency);

        return $currency;
    }
}
