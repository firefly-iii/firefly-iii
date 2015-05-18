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
        if (defined('FFCURRENCYSYMBOL')) {
            return FFCURRENCYSYMBOL;
        }

        $currencyPreference = Prefs::get('currencyPreference', 'EUR');
        $currency           = TransactionCurrency::whereCode($currencyPreference->data)->first();

        define('FFCURRENCYSYMBOL', $currency->symbol);

        return $currency->symbol;
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
                return '<span style="color:#999">' . $symbol . ' ' . $string . '</span>';
            }
            if ($amount > 0) {
                return '<span class="text-success">' . $symbol . ' ' . $string . '</span>';
            }

            return '<span class="text-danger">' . $symbol . ' ' . $string . '</span>';
        }

        // &#8364;
        return $symbol . ' ' . $string;
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
        if (is_null($journal->symbol)) {
            $symbol = $journal->transactionCurrency->symbol;
        } else {
            $symbol = $journal->symbol;
        }
        $amount = $journal->amount;
        if ($journal->transactionType->type == 'Withdrawal') {
            $amount = $amount * -1;
        }
        if ($journal->transactionType->type == 'Transfer' && $coloured) {
            return '<span class="text-info">' . $this->formatWithSymbol($symbol, $amount, false) . '</span>';
        }
        if ($journal->transactionType->type == 'Transfer' && !$coloured) {
            return $this->formatWithSymbol($symbol, $amount, false);
        }

        return $this->formatWithSymbol($symbol, $amount, $coloured);
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
        if (defined('FFCURRENCYCODE')) {
            return FFCURRENCYCODE;
        }


        $currencyPreference = Prefs::get('currencyPreference', 'EUR');

        $currency = TransactionCurrency::whereCode($currencyPreference->data)->first();
        if ($currency) {

            define('FFCURRENCYCODE', $currency->code);

            return $currency->code;
        }

        return 'EUR';
    }

    /**
     * @return mixed|static
     */
    public function getDefaultCurrency()
    {
        $currencyPreference = Prefs::get('currencyPreference', 'EUR');
        $currency           = TransactionCurrency::whereCode($currencyPreference->data)->first();

        return $currency;
    }
}
