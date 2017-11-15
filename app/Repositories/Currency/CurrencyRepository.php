<?php
/**
 * CurrencyRepository.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III.
 *
 * Firefly III is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Firefly III is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Firefly III.  If not, see <http://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace FireflyIII\Repositories\Currency;

use Carbon\Carbon;
use FireflyIII\Models\CurrencyExchangeRate;
use FireflyIII\Models\Preference;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\User;
use Illuminate\Support\Collection;
use Log;
use Preferences;

/**
 * Class CurrencyRepository.
 */
class CurrencyRepository implements CurrencyRepositoryInterface
{
    /** @var User */
    private $user;

    /**
     * @param TransactionCurrency $currency
     *
     * @return bool
     */
    public function canDeleteCurrency(TransactionCurrency $currency): bool
    {
        if ($this->countJournals($currency) > 0) {
            return false;
        }

        // is the only currency left
        if (1 === $this->get()->count()) {
            return false;
        }

        // is the default currency for the user or the system
        $defaultCode = Preferences::getForUser($this->user, 'currencyPreference', config('firefly.default_currency', 'EUR'))->data;
        if ($currency->code === $defaultCode) {
            return false;
        }

        // is the default currency for the system
        $defaultSystemCode = config('firefly.default_currency', 'EUR');
        if ($currency->code === $defaultSystemCode) {
            return false;
        }

        // can be deleted
        return true;
    }

    /**
     * @param TransactionCurrency $currency
     *
     * @return int
     */
    public function countJournals(TransactionCurrency $currency): int
    {
        return $currency->transactionJournals()->count();
    }

    /**
     * @param TransactionCurrency $currency
     *
     * @return bool
     */
    public function destroy(TransactionCurrency $currency): bool
    {
        if ($this->user->hasRole('owner')) {
            $currency->forceDelete();
        }

        return true;
    }

    /**
     * Find by ID.
     *
     * @param int $currencyId
     *
     * @return TransactionCurrency
     */
    public function find(int $currencyId): TransactionCurrency
    {
        $currency = TransactionCurrency::find($currencyId);
        if (null === $currency) {
            $currency = new TransactionCurrency;
        }

        return $currency;
    }

    /**
     * Find by currency code.
     *
     * @param string $currencyCode
     *
     * @return TransactionCurrency
     */
    public function findByCode(string $currencyCode): TransactionCurrency
    {
        $currency = TransactionCurrency::where('code', $currencyCode)->first();
        if (null === $currency) {
            $currency = new TransactionCurrency;
        }

        return $currency;
    }

    /**
     * Find by currency name.
     *
     * @param string $currencyName
     *
     * @return TransactionCurrency
     */
    public function findByName(string $currencyName): TransactionCurrency
    {
        $preferred = TransactionCurrency::whereName($currencyName)->first();
        if (null === $preferred) {
            $preferred = new TransactionCurrency;
        }

        return $preferred;
    }

    /**
     * Find by currency symbol.
     *
     * @param string $currencySymbol
     *
     * @return TransactionCurrency
     */
    public function findBySymbol(string $currencySymbol): TransactionCurrency
    {
        $currency = TransactionCurrency::whereSymbol($currencySymbol)->first();
        if (null === $currency) {
            $currency = new TransactionCurrency;
        }

        return $currency;
    }

    /**
     * @return Collection
     */
    public function get(): Collection
    {
        return TransactionCurrency::get();
    }

    /**
     * @param array $ids
     *
     * @return Collection
     */
    public function getByIds(array $ids): Collection
    {
        return TransactionCurrency::whereIn('id', $ids)->get();
    }

    /**
     * @param Preference $preference
     *
     * @return TransactionCurrency
     */
    public function getCurrencyByPreference(Preference $preference): TransactionCurrency
    {
        $preferred = TransactionCurrency::where('code', $preference->data)->first();
        if (null === $preferred) {
            $preferred = TransactionCurrency::first();
        }

        return $preferred;
    }

    /**
     * @param TransactionCurrency $fromCurrency
     * @param TransactionCurrency $toCurrency
     * @param Carbon              $date
     *
     * @return CurrencyExchangeRate
     */
    public function getExchangeRate(TransactionCurrency $fromCurrency, TransactionCurrency $toCurrency, Carbon $date): CurrencyExchangeRate
    {
        if ($fromCurrency->id === $toCurrency->id) {
            $rate       = new CurrencyExchangeRate;
            $rate->rate = 1;
            $rate->id   = 0;

            return $rate;
        }

        $rate = $this->user->currencyExchangeRates()
                           ->where('from_currency_id', $fromCurrency->id)
                           ->where('to_currency_id', $toCurrency->id)
                           ->where('date', $date->format('Y-m-d'))->first();
        if (null !== $rate) {
            Log::debug(sprintf('Found cached exchange rate in database for %s to %s on %s', $fromCurrency->code, $toCurrency->code, $date->format('Y-m-d')));

            return $rate;
        }

        return new CurrencyExchangeRate;
    }

    /**
     * @param User $user
     */
    public function setUser(User $user)
    {
        $this->user = $user;
    }

    /**
     * @param array $data
     *
     * @return TransactionCurrency
     */
    public function store(array $data): TransactionCurrency
    {
        /** @var TransactionCurrency $currency */
        $currency = TransactionCurrency::create(
            [
                'name'           => $data['name'],
                'code'           => $data['code'],
                'symbol'         => $data['symbol'],
                'decimal_places' => $data['decimal_places'],
            ]
        );

        return $currency;
    }

    /**
     * @param TransactionCurrency $currency
     * @param array               $data
     *
     * @return TransactionCurrency
     */
    public function update(TransactionCurrency $currency, array $data): TransactionCurrency
    {
        $currency->code           = $data['code'];
        $currency->symbol         = $data['symbol'];
        $currency->name           = $data['name'];
        $currency->decimal_places = $data['decimal_places'];
        $currency->save();

        return $currency;
    }
}
