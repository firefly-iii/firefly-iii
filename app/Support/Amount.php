<?php
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
                return '<span class="text-success" title="' . e($float) . '">' . $result . '</span>';
            } else {
                if ($amount < 0) {
                    return '<span class="text-danger" title="' . e($float) . '">' . $result . '</span>';
                }
            }

            return '<span style="color:#999" title="' . e($float) . '">' . $result . '</span>';


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

        return $this->formatAnything($currency, $transaction->amount, $coloured);
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
            $currencyPreference = Prefs::get('currencyPreference', env('DEFAULT_CURRENCY', 'EUR'));

            $currency = TransactionCurrency::whereCode($currencyPreference->data)->first();
            if ($currency) {

                $cache->store($currency->code);

                return $currency->code;
            }
            $cache->store(env('DEFAULT_CURRENCY', 'EUR'));

            return env('DEFAULT_CURRENCY', 'EUR'); // @codeCoverageIgnore
        }
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
            $currencyPreference = Prefs::get('currencyPreference', env('DEFAULT_CURRENCY', 'EUR'));
            $currency           = TransactionCurrency::whereCode($currencyPreference->data)->first();

            $cache->store($currency->symbol);

            return $currency->symbol;
        }
    }

    /**
     * @return TransactionCurrency
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
