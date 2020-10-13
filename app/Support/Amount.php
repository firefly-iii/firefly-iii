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

use Crypt;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\User;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Collection;
use Log;
use NumberFormatter;

/**
 * Class Amount.
 *
 * @codeCoverageIgnore
 */
class Amount
{
    /**
     * bool $sepBySpace is $localeconv['n_sep_by_space']
     * int $signPosn = $localeconv['n_sign_posn']
     * string $sign = $localeconv['negative_sign']
     * bool $csPrecedes = $localeconv['n_cs_precedes'].
     *
     * @param bool   $sepBySpace
     * @param int    $signPosn
     * @param string $sign
     * @param bool   $csPrecedes
     *
     * @return string
     *
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
     * This method returns the correct format rules required by accounting.js,
     * the library used to format amounts in charts.
     *
     * Used only in one place.
     *
     * @return array
     */
    public function getJsConfig(): array
    {
        $config     = $this->getLocaleInfo();
        $negative   = self::getAmountJsConfig($config['n_sep_by_space'], $config['n_sign_posn'], $config['negative_sign'], $config['n_cs_precedes']);
        $positive   = self::getAmountJsConfig($config['p_sep_by_space'], $config['p_sign_posn'], $config['positive_sign'], $config['p_cs_precedes']);

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
     * @return array
     */
    private function getLocaleInfo(): array
    {
        // get config from preference, not from translation:
        $locale = app('steam')->getLocale();
        $array  = app('steam')->getLocaleArray($locale);

        setlocale(LC_MONETARY, $array);
        $info = localeconv();

        // correct variables
        $info['n_cs_precedes'] = $this->getLocaleField($info, 'n_cs_precedes');
        $info['p_cs_precedes'] = $this->getLocaleField($info, 'p_cs_precedes');

        $info['n_sep_by_space'] = $this->getLocaleField($info, 'n_sep_by_space');
        $info['p_sep_by_space'] = $this->getLocaleField($info, 'p_sep_by_space');

        $fmt = new NumberFormatter( $locale, NumberFormatter::CURRENCY);

        $info['mon_decimal_point'] = $fmt->getSymbol(NumberFormatter::MONETARY_SEPARATOR_SYMBOL);
        $info['mon_thousands_sep'] = $fmt->getSymbol(NumberFormatter::MONETARY_GROUPING_SEPARATOR_SYMBOL);

        return $info;
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
     *
     */
    public function formatAnything(TransactionCurrency $format, string $amount, bool $coloured = null): string
    {
        return $this->formatFlat($format->symbol, (int)$format->decimal_places, $amount, $coloured);
    }

    /**
     * This method will properly format the given number, in color or "black and white",
     * as a currency, given two things: the currency required and the current locale.
     *
     * @param string $symbol
     * @param int    $decimalPlaces
     * @param string $amount
     * @param bool   $coloured
     *
     * @return string
     *
     * @noinspection MoreThanThreeArgumentsInspection
     */
    public function formatFlat(string $symbol, int $decimalPlaces, string $amount, bool $coloured = null): string
    {
        $locale = app('steam')->getLocale();

        $coloured  = $coloured ?? true;

        $fmt = new NumberFormatter( $locale, NumberFormatter::CURRENCY );
        $fmt->setSymbol(NumberFormatter::CURRENCY_SYMBOL, $symbol);
        $fmt->setAttribute(NumberFormatter::MIN_FRACTION_DIGITS, $decimalPlaces);
        $fmt->setAttribute(NumberFormatter::MAX_FRACTION_DIGITS, $decimalPlaces);
        $result = $fmt->format($amount);

        if (true === $coloured) {
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
        if ('testing' === config('app.env')) {
            Log::warning(sprintf('%s should NOT be called in the TEST environment!', __METHOD__));
        }

        return TransactionCurrency::orderBy('code', 'ASC')->get();
    }

    /**
     * @return Collection
     */
    public function getCurrencies(): Collection
    {
        if ('testing' === config('app.env')) {
            Log::warning(sprintf('%s should NOT be called in the TEST environment!', __METHOD__));
        }

        return TransactionCurrency::where('enabled', true)->orderBy('code', 'ASC')->get();
    }

    /**
     * @return string
     */
    public function getCurrencyCode(): string
    {
        if ('testing' === config('app.env')) {
            Log::warning(sprintf('%s should NOT be called in the TEST environment!', __METHOD__));
        }
        $cache = new CacheProperties;
        $cache->addProperty('getCurrencyCode');
        if ($cache->has()) {
            return $cache->get(); // @codeCoverageIgnore
        }
        $currencyPreference = app('preferences')->get('currencyPreference', config('firefly.default_currency', 'EUR'));

        $currency = TransactionCurrency::where('code', $currencyPreference->data)->first();
        if ($currency) {
            $cache->store($currency->code);

            return $currency->code;
        }
        $cache->store(config('firefly.default_currency', 'EUR'));

        return (string)config('firefly.default_currency', 'EUR');
    }

    /**
     * @return TransactionCurrency
     */
    public function getDefaultCurrency(): TransactionCurrency
    {
        if ('testing' === config('app.env')) {
            Log::warning(sprintf('%s should NOT be called in the TEST environment!', __METHOD__));
        }
        /** @var User $user */
        $user = auth()->user();

        return $this->getDefaultCurrencyByUser($user);
    }

    /**
     * @return TransactionCurrency
     */
    public function getSystemCurrency(): TransactionCurrency
    {
        if ('testing' === config('app.env')) {
            Log::warning(sprintf('%s should NOT be called in the TEST environment!', __METHOD__));
        }

            return TransactionCurrency::where('code', 'EUR')->first();
    }

    /**
     * @param User $user
     *
     * @return TransactionCurrency
     */
    public function getDefaultCurrencyByUser(User $user): TransactionCurrency
    {
        $cache = new CacheProperties;
        $cache->addProperty('getDefaultCurrency');
        $cache->addProperty($user->id);
        if ($cache->has()) {
            return $cache->get(); // @codeCoverageIgnore
        }
        $currencyPreference = app('preferences')->getForUser($user, 'currencyPreference', config('firefly.default_currency', 'EUR'));
        $currencyPrefStr    = $currencyPreference ? $currencyPreference->data : 'EUR';

        // at this point the currency preference could be encrypted, if coming from an old version.
        $currencyCode = $this->tryDecrypt((string) $currencyPrefStr);

        // could still be json encoded:
        if (strlen($currencyCode) > 3) {
            $currencyCode = json_decode($currencyCode, true, 512, JSON_THROW_ON_ERROR) ?? 'EUR';
        }
        /** @var TransactionCurrency $currency */
        $currency = TransactionCurrency::where('code', $currencyCode)->first();
        if (null === $currency) {
            // get EUR
            $currency = TransactionCurrency::where('code', 'EUR')->first();
        }
        $cache->store($currency);

        return $currency;
    }

    /**
     * @param array  $info
     * @param string $field
     *
     * @return bool
     */
    private function getLocaleField(array $info, string $field): bool
    {
        return (is_bool($info[$field]) && true === $info[$field])
               || (is_int($info[$field]) && 1 === $info[$field]);
    }

    /**
     * @param string $value
     *
     * @return string
     */
    private function tryDecrypt(string $value): string
    {
        try {
            $value = Crypt::decrypt($value); // verified
        } catch (DecryptException $e) {
        }

        return $value;
    }
}
