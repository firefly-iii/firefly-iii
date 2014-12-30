<?php
if (!function_exists('mf')) {
    /**
     * @param      $amount
     * @param bool $coloured
     *
     * @return string
     */
    function mf($amount, $coloured = true)
    {
        $currencySymbol = getCurrencySymbol();

        return mfc($currencySymbol, $amount, $coloured);


    }
}

if (!function_exists('mft')) {
    /**
     * @param \Transaction $transaction
     * @param bool        $coloured
     *
     * @return string
     */
    function mft(\Transaction $transaction, $coloured = true)
    {
        $symbol = $transaction->transactionJournal->transactionCurrency->symbol;
        $amount = floatval($transaction->amount);

        return mfc($symbol, $amount, $coloured);


    }
}

if (!function_exists('mfj')) {
    /**
     * @param \TransactionJournal $journal
     * @param float $amount
     * @param bool        $coloured
     *
     * @return string
     */
    function mfj(\TransactionJournal $journal, $amount, $coloured = true)
    {
        $symbol = $journal->transactionCurrency->symbol;

        return mfc($symbol, $amount, $coloured);


    }
}

if (!function_exists('mfc')) {
    /**
     * @param string $symbol
     * @param float  $amount
     * @param bool   $coloured
     *
     * @return string
     */
    function mfc($symbol, $amount, $coloured = true)
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
}

if (!function_exists('getCurrencySymbol')) {
    /**
     * @return string
     */
    function getCurrencySymbol()
    {
        if (defined('FFCURRENCYSYMBOL')) {
            return FFCURRENCYSYMBOL;
        }
        if (Cache::has('FFCURRENCYSYMBOL')) {
            define('FFCURRENCYSYMBOL', Cache::get('FFCURRENCYSYMBOL'));

            return FFCURRENCYSYMBOL;
        }

        /** @var \FireflyIII\Database\TransactionCurrency\TransactionCurrency $currencies */
        $currencies = App::make('FireflyIII\Database\TransactionCurrency\TransactionCurrency');

        /** @var \FireflyIII\Shared\Preferences\Preferences $preferences */
        $preferences = App::make('FireflyIII\Shared\Preferences\Preferences');

        $currencyPreference = $preferences->get('currencyPreference', 'EUR');
        $currency           = $currencies->findByCode($currencyPreference->data);

        Cache::forever('FFCURRENCYSYMBOL', $currency->symbol);

        define('FFCURRENCYSYMBOL', $currency->symbol);

        return $currency->symbol;
    }
}

if (!function_exists('getCurrencyCode')) {
    /**
     * @return string
     */
    function getCurrencyCode()
    {
        if (defined('FFCURRENCYCODE')) {
            return FFCURRENCYCODE;
        }
        if (Cache::has('FFCURRENCYCODE')) {
            define('FFCURRENCYCODE', Cache::get('FFCURRENCYCODE'));

            return FFCURRENCYCODE;
        }

        /** @var \FireflyIII\Database\TransactionCurrency\TransactionCurrency $currencies */
        $currencies = App::make('FireflyIII\Database\TransactionCurrency\TransactionCurrency');

        /** @var \FireflyIII\Shared\Preferences\Preferences $preferences */
        $preferences = App::make('FireflyIII\Shared\Preferences\Preferences');

        $currencyPreference = $preferences->get('currencyPreference', 'EUR');
        $currency           = $currencies->findByCode($currencyPreference->data);

        Cache::forever('FFCURRENCYCODE', $currency->code);

        define('FFCURRENCYCODE', $currency->code);

        return $currency->code;
    }
}

if (!function_exists('boolstr')) {
    /**
     * @param $boolean
     *
     * @return string
     */
    function boolstr($boolean)
    {
        if (is_bool($boolean) && $boolean === true) {
            return 'BOOLEAN TRUE';
        }
        if (is_bool($boolean) && $boolean === false) {
            return 'BOOLEAN FALSE';
        }

        return 'NO BOOLEAN: ' . $boolean;
    }
}