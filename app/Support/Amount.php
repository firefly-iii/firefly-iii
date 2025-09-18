<?php

/**
 * Amount.php
 * Copyright (c) 2019 james@firefly-iii.org
 *
 * This file is part of Firefly III (https://github.com/firefly-iii).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace FireflyIII\Support;

use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\UserGroup;
use FireflyIII\Support\Facades\Preferences;
use FireflyIII\Support\Facades\Steam;
use FireflyIII\Support\Singleton\PreferencesSingleton;
use FireflyIII\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use NumberFormatter;

/**
 * Class Amount.
 */
class Amount
{
    /**
     * This method will properly format the given number, in color or "black and white",
     * as a currency, given two things: the currency required and the current locale.
     *
     * @throws FireflyException
     */
    public function formatAnything(TransactionCurrency $format, string $amount, ?bool $coloured = null): string
    {
        return $this->formatFlat($format->symbol, $format->decimal_places, $amount, $coloured);
    }

    /**
     * This method will properly format the given number, in color or "black and white",
     * as a currency, given two things: the currency required and the current locale.
     *
     * @throws FireflyException
     */
    public function formatFlat(string $symbol, int $decimalPlaces, string $amount, ?bool $coloured = null): string
    {
        $locale  = Steam::getLocale();
        $rounded = Steam::bcround($amount, $decimalPlaces);
        $coloured ??= true;

        $fmt     = new NumberFormatter($locale, NumberFormatter::CURRENCY);
        $fmt->setSymbol(NumberFormatter::CURRENCY_SYMBOL, $symbol);
        $fmt->setAttribute(NumberFormatter::MIN_FRACTION_DIGITS, $decimalPlaces);
        $fmt->setAttribute(NumberFormatter::MAX_FRACTION_DIGITS, $decimalPlaces);
        $result  = (string)$fmt->format((float)$rounded); // intentional float

        if (true === $coloured) {
            if (1 === bccomp($rounded, '0')) {
                return sprintf('<span class="text-success money-positive">%s</span>', $result);
            }
            if (-1 === bccomp($rounded, '0')) {
                return sprintf('<span class="text-danger money-negative">%s</span>', $result);
            }

            return sprintf('<span class="money-neutral">%s</span>', $result);
        }

        return $result;
    }

    public function formatByCurrencyId(int $currencyId, string $amount, ?bool $coloured = null): string
    {
        $format = $this->getTransactionCurrencyById($currencyId);

        return $this->formatFlat($format->symbol, $format->decimal_places, $amount, $coloured);
    }

    public function getAllCurrencies(): Collection
    {
        return TransactionCurrency::orderBy('code', 'ASC')->get();
    }

    /**
     * Experimental function to see if we can quickly and quietly get the amount from a journal.
     * This depends on the user's default currency and the wish to have it converted.
     */
    public function getAmountFromJournal(array $journal): string
    {
        $convertToPrimary = $this->convertToPrimary();
        $currency         = $this->getPrimaryCurrency();
        $field            = $convertToPrimary && $currency->id !== $journal['currency_id'] ? 'pc_amount' : 'amount';
        $amount           = $journal[$field] ?? '0';
        // Log::debug(sprintf('Field is %s, amount is %s', $field, $amount));
        // fallback, the transaction has a foreign amount in $currency.
        if ($convertToPrimary && null !== $journal['foreign_amount'] && $currency->id === (int)$journal['foreign_currency_id']) {
            $amount = $journal['foreign_amount'];
            // Log::debug(sprintf('Overruled, amount is now %s', $amount));
        }

        return (string)$amount;
    }

    public function getTransactionCurrencyById(int $currencyId): TransactionCurrency
    {
        $instance = PreferencesSingleton::getInstance();
        $key      = sprintf('transaction_currency_%d', $currencyId);

        /** @var null|TransactionCurrency $pref */
        $pref     = $instance->getPreference($key);
        if (null !== $pref) {
            return $pref;
        }
        $currency = TransactionCurrency::find($currencyId);
        if (null === $currency) {
            $message = sprintf('Could not find a transaction currency with ID #%d in %s', $currencyId, __METHOD__);
            Log::error($message);

            throw new FireflyException($message);
        }
        $instance->setPreference($key, $currency);

        return $currency;
    }

    public function getTransactionCurrencyByCode(string $code): TransactionCurrency
    {
        $instance = PreferencesSingleton::getInstance();
        $key      = sprintf('transaction_currency_%s', $code);

        /** @var null|TransactionCurrency $pref */
        $pref     = $instance->getPreference($key);
        if (null !== $pref) {
            return $pref;
        }
        $currency = TransactionCurrency::whereCode($code)->first();
        if (null === $currency) {
            $message = sprintf('Could not find a transaction currency with code "%s" in %s', $code, __METHOD__);
            Log::error($message);

            throw new FireflyException($message);
        }
        $instance->setPreference($key, $currency);

        return $currency;
    }

    public function convertToPrimary(?User $user = null): bool
    {
        $instance = PreferencesSingleton::getInstance();
        if (!$user instanceof User) {
            $pref = $instance->getPreference('convert_to_primary_no_user');
            if (null === $pref) {
                $res = true === Preferences::get('convert_to_primary', false)->data && true === config('cer.enabled');
                $instance->setPreference('convert_to_primary_no_user', $res);

                return $res;
            }

            return $pref;
        }
        $key      = sprintf('convert_to_primary_%d', $user->id);
        $pref     = $instance->getPreference($key);
        if (null === $pref) {
            $res = true === Preferences::getForUser($user, 'convert_to_primary', false)->data && true === config('cer.enabled');
            $instance->setPreference($key, $res);

            return $res;
        }

        return $pref;
    }

    public function getPrimaryCurrency(): TransactionCurrency
    {
        if (auth()->check()) {
            /** @var User $user */
            $user = auth()->user();
            if (null !== $user->userGroup) {
                return $this->getPrimaryCurrencyByUserGroup($user->userGroup);
            }
        }

        return $this->getSystemCurrency();
    }

    public function getPrimaryCurrencyByUserGroup(UserGroup $userGroup): TransactionCurrency
    {
        $cache   = new CacheProperties();
        $cache->addProperty('getPrimaryCurrencyByGroup');
        $cache->addProperty($userGroup->id);
        if ($cache->has()) {
            return $cache->get();
        }

        /** @var null|TransactionCurrency $primary */
        $primary = $userGroup->currencies()->where('group_default', true)->first();
        if (null === $primary) {
            $primary = $this->getSystemCurrency();
            // could be the user group has no default right now.
            $userGroup->currencies()->sync([$primary->id => ['group_default' => true]]);
        }
        $cache->store($primary);

        return $primary;
    }

    public function getSystemCurrency(): TransactionCurrency
    {
        return TransactionCurrency::whereNull('deleted_at')->where('code', 'EUR')->first();
    }

    /**
     * Experimental function to see if we can quickly and quietly get the amount from a journal.
     * This depends on the user's default currency and the wish to have it converted.
     */
    public function getAmountFromJournalObject(TransactionJournal $journal): string
    {
        $convertToPrimary  = $this->convertToPrimary();
        $currency          = $this->getPrimaryCurrency();
        $field             = $convertToPrimary && $currency->id !== $journal->transaction_currency_id ? 'pc_amount' : 'amount';

        /** @var null|Transaction $sourceTransaction */
        $sourceTransaction = $journal->transactions()->where('amount', '<', 0)->first();
        if (null === $sourceTransaction) {
            return '0';
        }
        $amount            = $sourceTransaction->{$field} ?? '0';
        if ((int)$sourceTransaction->foreign_currency_id === $currency->id) {
            // use foreign amount instead!
            $amount = (string)$sourceTransaction->foreign_amount; // hard coded to be foreign amount.
        }

        return $amount;
    }

    public function getCurrencies(): Collection
    {
        /** @var User $user */
        $user = auth()->user();

        return $user->currencies()->orderBy('code', 'ASC')->get();
    }

    /**
     * This method returns the correct format rules required by accounting.js,
     * the library used to format amounts in charts.
     *
     * Used only in one place.
     *
     * @throws FireflyException
     */
    public function getJsConfig(): array
    {
        $config   = $this->getLocaleInfo();
        $negative = self::getAmountJsConfig($config['n_sep_by_space'], $config['n_sign_posn'], $config['negative_sign'], $config['n_cs_precedes']);
        $positive = self::getAmountJsConfig($config['p_sep_by_space'], $config['p_sign_posn'], $config['positive_sign'], $config['p_cs_precedes']);

        return [
            'mon_decimal_point' => $config['mon_decimal_point'],
            'mon_thousands_sep' => $config['mon_thousands_sep'],
            'format'            => [
                'pos'  => $positive,
                'neg'  => $negative,
                'zero' => $positive,
            ],
        ];
    }

    /**
     * @throws FireflyException
     */
    private function getLocaleInfo(): array
    {
        // get config from preference, not from translation:
        $locale                    = Steam::getLocale();
        $array                     = Steam::getLocaleArray($locale);

        setlocale(LC_MONETARY, $array);
        $info                      = localeconv();

        // correct variables
        $info['n_cs_precedes']     = $this->getLocaleField($info, 'n_cs_precedes');
        $info['p_cs_precedes']     = $this->getLocaleField($info, 'p_cs_precedes');

        $info['n_sep_by_space']    = $this->getLocaleField($info, 'n_sep_by_space');
        $info['p_sep_by_space']    = $this->getLocaleField($info, 'p_sep_by_space');

        $fmt                       = new NumberFormatter($locale, NumberFormatter::CURRENCY);

        $info['mon_decimal_point'] = $fmt->getSymbol(NumberFormatter::MONETARY_SEPARATOR_SYMBOL);
        $info['mon_thousands_sep'] = $fmt->getSymbol(NumberFormatter::MONETARY_GROUPING_SEPARATOR_SYMBOL);

        return $info;
    }

    private function getLocaleField(array $info, string $field): bool
    {
        return (is_bool($info[$field]) && true === $info[$field])
               || (is_int($info[$field]) && 1 === $info[$field]);
    }

    /**
     * bool $sepBySpace is $localeconv['n_sep_by_space']
     * int $signPosn = $localeconv['n_sign_posn']
     * string $sign = $localeconv['negative_sign']
     * bool $csPrecedes = $localeconv['n_cs_precedes'].
     */
    public static function getAmountJsConfig(bool $sepBySpace, int $signPosn, string $sign, bool $csPrecedes): string
    {
        // negative first:
        $space = ' ';

        // require space between symbol and amount?
        if (false === $sepBySpace) {
            $space = ''; // no
        }

        // there are five possible positions for the "+" or "-" sign (if it is even used)
        // pos_a and pos_e could be the ( and ) symbol.
        $posA  = ''; // before everything
        $posB  = ''; // before currency symbol
        $posC  = ''; // after currency symbol
        $posD  = ''; // before amount
        $posE  = ''; // after everything

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

        if ($csPrecedes) {
            return $posA.$posB.'%s'.$posC.$space.$posD.'%v'.$posE;
        }

        return $posA.$posD.'%v'.$space.$posB.'%s'.$posC.$posE;
    }
}
