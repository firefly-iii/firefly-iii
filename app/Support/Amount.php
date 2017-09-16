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

declare(strict_types=1);

namespace FireflyIII\Support;

use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\Transaction as TransactionModel;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionType;
use FireflyIII\User;
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
     * bool $sepBySpace is $localeconv['n_sep_by_space']
     * int $signPosn = $localeconv['n_sign_posn']
     * string $sign = $localeconv['negative_sign']
     * bool $csPrecedes = $localeconv['n_cs_precedes']
     *
     * @param bool   $sepBySpace
     * @param int    $signPosn
     * @param string $sign
     * @param bool   $csPrecedes
     *
     * @return string
     */
    public static function getAmountJsConfig(bool $sepBySpace, int $signPosn, string $sign, bool $csPrecedes): string
    {
        // negative first:
        $space = ' ';

        // require space between symbol and amount?
        if (!$sepBySpace) {
            $space = ''; // no
        }

        // there are five possible positions for the "+" or "-" sign (if it is even used)
        // pos_a and pos_e could be the ( and ) symbol.
        $posA = ''; // before everything
        $posB = ''; // before currency symbol
        $posC = ''; // after currency symbol
        $posD = ''; // before amount
        $posE = ''; // after everything

        // format would be (currency before amount)
        // AB%sC_D%vE
        // or:
        // AD%v_B%sCE (amount before currency)
        // the _ is the optional space


        // switch on how to display amount:
        switch ($signPosn) {
            default:
            case 0:
                // ( and ) around the whole thing
                $posA = '(';
                $posE = ')';
                break;
            case 1:
                // The sign string precedes the quantity and currency_symbol
                $posA = $sign;
                break;
            case 2:
                // The sign string succeeds the quantity and currency_symbol
                $posE = $sign;
                break;
            case 3:
                // The sign string immediately precedes the currency_symbol
                $posB = $sign;
                break;
            case 4:
                // The sign string immediately succeeds the currency_symbol
                $posC = $sign;
        }

        // default is amount before currency
        $format = $posA . $posD . '%v' . $space . $posB . '%s' . $posC . $posE;

        if ($csPrecedes) {
            // alternative is currency before amount
            $format = $posA . $posB . '%s' . $posC . $space . $posD . '%v' . $posE;
        }

        return $format;
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
        $formatted = number_format($float, intval($format->decimal_places), $info['mon_decimal_point'], $info['mon_thousands_sep']);

        // some complicated switches to format the amount correctly:
        $precedes  = $amount < 0 ? $info['n_cs_precedes'] : $info['p_cs_precedes'];
        $separated = $amount < 0 ? $info['n_sep_by_space'] : $info['p_sep_by_space'];
        $space     = $separated ? ' ' : '';
        $result    = $format->symbol . $space . $formatted;

        if (!$precedes) {
            $result = $formatted . $space . $format->symbol;
        }

        if ($coloured === true) {

            if ($amount > 0) {
                return sprintf('<span class="text-success">%s</span>', $result);
            }
            if ($amount < 0) {
                return sprintf('<span class="text-danger">%s</span>', $result);
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
            return $cache->get(); // @codeCoverageIgnore
        } else {
            $currencyPreference = Prefs::get('currencyPreference', config('firefly.default_currency', 'EUR'));

            $currency = TransactionCurrency::where('code', $currencyPreference->data)->first();
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
            return $cache->get(); // @codeCoverageIgnore
        }
        $currencyPreference = Prefs::get('currencyPreference', config('firefly.default_currency', 'EUR'));
        $currency           = TransactionCurrency::where('code', $currencyPreference->data)->first();

        $cache->store($currency->symbol);

        return $currency->symbol;
    }

    /**
     * @return \FireflyIII\Models\TransactionCurrency
     * @throws FireflyException
     */
    public function getDefaultCurrency(): TransactionCurrency
    {
        $user = auth()->user();

        return $this->getDefaultCurrencyByUser($user);
    }

    /**
     * @param User $user
     *
     * @return \FireflyIII\Models\TransactionCurrency
     * @throws FireflyException
     */
    public function getDefaultCurrencyByUser(User $user): TransactionCurrency
    {
        $cache = new CacheProperties;
        $cache->addProperty('getDefaultCurrency');
        $cache->addProperty($user->id);
        if ($cache->has()) {
            return $cache->get(); // @codeCoverageIgnore
        }
        $currencyPreference = Prefs::getForUser($user, 'currencyPreference', config('firefly.default_currency', 'EUR'));
        $currency           = TransactionCurrency::where('code', $currencyPreference->data)->first();
        if (is_null($currency)) {
            throw new FireflyException(sprintf('No currency found with code "%s"', $currencyPreference->data));
        }
        $cache->store($currency);

        return $currency;
    }

    /**
     * This method returns the correct format rules required by accounting.js,
     * the library used to format amounts in charts.
     *
     * @param array $config
     *
     * @return array
     */
    public function getJsConfig(array $config): array
    {
        $negative = self::getAmountJsConfig($config['n_sep_by_space'] === 1, $config['n_sign_posn'], $config['negative_sign'], $config['n_cs_precedes'] === 1);
        $positive = self::getAmountJsConfig($config['p_sep_by_space'] === 1, $config['p_sign_posn'], $config['positive_sign'], $config['p_cs_precedes'] === 1);

        return [
            'pos'  => $positive,
            'neg'  => $negative,
            'zero' => $positive,
        ];
    }

    /**
     * @param TransactionJournal $journal
     * @param bool               $coloured
     *
     * @return string
     */
    public function journalAmount(TransactionJournal $journal, bool $coloured = true): string
    {
        $amounts      = [];
        $transactions = $journal->transactions()->where('amount', '>', 0)->get();
        /** @var TransactionModel $transaction */
        foreach ($transactions as $transaction) {
            // model some fields to fit "transactionAmount()":
            $transaction->transaction_amount          = $transaction->amount;
            $transaction->transaction_foreign_amount  = $transaction->foreign_amount;
            $transaction->transaction_type_type       = $journal->transactionType->type;
            $transaction->transaction_currency_symbol = $transaction->transactionCurrency->symbol;
            $transaction->transaction_currency_dp     = $transaction->transactionCurrency->decimal_places;
            if (!is_null($transaction->foreign_currency_id)) {
                $transaction->foreign_currency_symbol = $transaction->foreignCurrency->symbol;
                $transaction->foreign_currency_dp     = $transaction->foreignCurrency->decimal_places;
            }

            $amounts[] = $this->transactionAmount($transaction, $coloured);
        }

        return join(' / ', $amounts);

    }

    /**
     * This formats a transaction, IF that transaction has been "collected" using the JournalCollector.
     *
     * @param TransactionModel $transaction
     * @param bool             $coloured
     *
     * @return string
     */
    public function transactionAmount(TransactionModel $transaction, bool $coloured = true): string
    {
        $amount = bcmul(app('steam')->positive(strval($transaction->transaction_amount)), '-1');

        $format = '%s';

        if ($transaction->transaction_type_type === TransactionType::DEPOSIT) {
            $amount = bcmul($amount, '-1');
        }

        if ($transaction->transaction_type_type === TransactionType::TRANSFER) {
            $amount   = app('steam')->positive($amount);
            $coloured = false;
            $format   = '<span class="text-info">%s</span>';
        }
        if ($transaction->transaction_type_type === TransactionType::OPENING_BALANCE) {
            $amount = strval($transaction->transaction_amount);
        }

        $currency                 = new TransactionCurrency;
        $currency->symbol         = $transaction->transaction_currency_symbol;
        $currency->decimal_places = $transaction->transaction_currency_dp;
        $str                      = sprintf($format, $this->formatAnything($currency, $amount, $coloured));


        if (!is_null($transaction->transaction_foreign_amount)) {
            $amount = bcmul(app('steam')->positive(strval($transaction->transaction_foreign_amount)), '-1');
            if ($transaction->transaction_type_type === TransactionType::DEPOSIT) {
                $amount = bcmul($amount, '-1');
            }


            if ($transaction->transaction_type_type === TransactionType::TRANSFER) {
                $amount   = app('steam')->positive($amount);
                $coloured = false;
                $format   = '<span class="text-info">%s</span>';
            }

            $currency                 = new TransactionCurrency;
            $currency->symbol         = $transaction->foreign_currency_symbol;
            $currency->decimal_places = $transaction->foreign_currency_dp;
            $str                      .= ' (' . sprintf($format, $this->formatAnything($currency, $amount, $coloured)) . ')';
        }

        return $str;
    }
}
