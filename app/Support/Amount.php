<?php

namespace FireflyIII\Support;

use Cache;
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
        if (\Cache::has('FFCURRENCYSYMBOL')) {
            define('FFCURRENCYSYMBOL', \Cache::get('FFCURRENCYSYMBOL'));

            return FFCURRENCYSYMBOL;
        }

        $currencyPreference = Prefs::get('currencyPreference', 'EUR');
        $currency           = TransactionCurrency::whereCode($currencyPreference->data)->first();

        \Cache::forever('FFCURRENCYSYMBOL', $currency->symbol);

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
     * @return string
     *
     * @param TransactionJournal $journal
     */
    public function formatJournal(TransactionJournal $journal, $coloured = true)
    {
        $showPositive = true;
        if (is_null($journal->symbol)) {
            $symbol = $journal->transactionCurrency->symbol;
        } else {
            $symbol = $journal->symbol;
        }
        $amount = 0;

        if (is_null($journal->type)) {
            $type = $journal->transactionType->type;
        } else {
            $type = $journal->type;
        }

        if ($type == 'Withdrawal') {
            $showPositive = false;
        }

        foreach ($journal->transactions as $t) {
            if (floatval($t->amount) > 0 && $showPositive === true) {
                $amount = floatval($t->amount);
                break;
            }
            if (floatval($t->amount) < 0 && $showPositive === false) {
                $amount = floatval($t->amount);
            }
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

        $currency           = TransactionCurrency::whereCode($currencyPreference->data)->first();
        if ($currency) {

            Cache::forever('FFCURRENCYCODE', $currency->code);
            define('FFCURRENCYCODE', $currency->code);

            return $currency->code;
        }

        return 'EUR';
    }

    public function getDefaultCurrency()
    {
        $currencyPreference = Prefs::get('currencyPreference', 'EUR');
        $currency           = TransactionCurrency::whereCode($currencyPreference->data)->first();

        return $currency;
    }
}
