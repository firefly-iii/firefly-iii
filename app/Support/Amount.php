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
     * @return \FireflyIII\Models\TransactionCurrency
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
     * @return \FireflyIII\Models\TransactionCurrency
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
     * @return \FireflyIII\Models\TransactionCurrency
     */
    public function getDefaultCurrencyByUser(User $user): TransactionCurrency
    {
        if ('testing' === config('app.env')) {
            Log::warning(sprintf('%s should NOT be called in the TEST environment!', __METHOD__));
        }
        $cache = new CacheProperties;
        $cache->addProperty('getDefaultCurrency');
        $cache->addProperty($user->id);
        if ($cache->has()) {
            return $cache->get(); // @codeCoverageIgnore
        }
        $currencyPreference = app('preferences')->getForUser($user, 'currencyPreference', config('firefly.default_currency', 'EUR'));
        $currencyPrefStr    = $currencyPreference ? $currencyPreference->data : 'EUR';

        // at this point the currency preference could be encrypted, if coming from an old version.
        Log::debug('Going to try to decrypt users currency preference.');
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
     * @return array
     */
    public function getAccountingLocaleInfo(): array
    {
        $locale = app('steam')->getLocale();

        $fmt = new NumberFormatter( $locale, NumberFormatter::CURRENCY );

        $positivePrefixed = '' !== $fmt->getAttribute(NumberFormatter::POSITIVE_PREFIX);
        $negativePrefixed = '' !== $fmt->getAttribute(NumberFormatter::NEGATIVE_PREFIX);

        $positive = ($positivePrefixed) ? '%s %v' : '%v %s';
        $negative = ($negativePrefixed) ? '%s %v' : '%v %s';

        return [
            'mon_decimal_point' => $fmt->getSymbol(NumberFormatter::MONETARY_SEPARATOR_SYMBOL),
            'mon_thousands_sep' => $fmt->getSymbol(NumberFormatter::MONETARY_GROUPING_SEPARATOR_SYMBOL),
            'format' => [
                'pos'  => $positive,
                'neg'  => $negative,
                'zero' => $positive,
            ]
        ];
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
            Log::debug(sprintf('Could not decrypt "%s". %s', $value, $e->getMessage()));
        }

        return $value;
    }
}
