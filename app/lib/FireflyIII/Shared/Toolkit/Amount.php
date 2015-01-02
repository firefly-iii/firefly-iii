<?php

namespace FireflyIII\Shared\Toolkit;

/**
 * Class Amount
 *
 * @package FireflyIII\Shared\Toolkit
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

        /** @var \FireflyIII\Database\TransactionCurrency\TransactionCurrency $currencies */
        $currencies = \App::make('FireflyIII\Database\TransactionCurrency\TransactionCurrency');

        /** @var \FireflyIII\Shared\Preferences\Preferences $preferences */
        $preferences = \App::make('FireflyIII\Shared\Preferences\Preferences');

        $currencyPreference = $preferences->get('currencyPreference', 'EUR');
        $currency           = $currencies->findByCode($currencyPreference->data);

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
    protected function formatWithSymbol($symbol, $amount, $coloured = true)
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
     * @param \TransactionJournal $journal
     * @param float               $amount
     * @param bool                $coloured
     *
     * @return string
     */
    public function formatJournal(\TransactionJournal $journal, $amount, $coloured = true)
    {
        $symbol = $journal->transactionCurrency->symbol;

        return $this->formatWithSymbol($symbol, $amount, $coloured);


    }

    /**
     * @param \Transaction $transaction
     * @param bool         $coloured
     *
     * @return string
     */
    public function formatTransaction(\Transaction $transaction, $coloured = true)
    {
        $symbol = $transaction->transactionJournal->transactionCurrency->symbol;
        $amount = floatval($transaction->amount);

        return $this->formatWithSymbol($symbol, $amount, $coloured);


    }

    /**
     * @return string
     */
    public function getCurrencyCode()
    {
        if (defined('FFCURRENCYCODE')) {
            return FFCURRENCYCODE;
        }
        if (\Cache::has('FFCURRENCYCODE')) {
            define('FFCURRENCYCODE', \Cache::get('FFCURRENCYCODE'));

            return FFCURRENCYCODE;
        }

        /** @var \FireflyIII\Database\TransactionCurrency\TransactionCurrency $currencies */
        $currencies = \App::make('FireflyIII\Database\TransactionCurrency\TransactionCurrency');

        /** @var \FireflyIII\Shared\Preferences\Preferences $preferences */
        $preferences = \App::make('FireflyIII\Shared\Preferences\Preferences');

        $currencyPreference = $preferences->get('currencyPreference', 'EUR');
        $currency           = $currencies->findByCode($currencyPreference->data);

        \Cache::forever('FFCURRENCYCODE', $currency->code);

        define('FFCURRENCYCODE', $currency->code);

        return $currency->code;
    }

}