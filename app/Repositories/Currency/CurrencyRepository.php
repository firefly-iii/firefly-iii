<?php
/**
 * CurrencyRepository.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
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
 * Class CurrencyRepository
 *
 * @package FireflyIII\Repositories\Currency
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
        if ($this->get()->count() === 1) {
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
     * Find by ID
     *
     * @param int $currencyId
     *
     * @return TransactionCurrency
     */
    public function find(int $currencyId): TransactionCurrency
    {
        $currency = TransactionCurrency::find($currencyId);
        if (is_null($currency)) {
            $currency = new TransactionCurrency;

        }

        return $currency;
    }

    /**
     * Find by currency code
     *
     * @param string $currencyCode
     *
     * @return TransactionCurrency
     */
    public function findByCode(string $currencyCode): TransactionCurrency
    {
        $currency = TransactionCurrency::where('code', $currencyCode)->first();
        if (is_null($currency)) {
            $currency = new TransactionCurrency;
        }

        return $currency;
    }

    /**
     * Find by currency name
     *
     * @param string $currencyName
     *
     * @return TransactionCurrency
     */
    public function findByName(string $currencyName): TransactionCurrency
    {
        $preferred = TransactionCurrency::whereName($currencyName)->first();
        if (is_null($preferred)) {
            $preferred = new TransactionCurrency;
        }

        return $preferred;
    }

    /**
     * Find by currency symbol
     *
     * @param string $currencySymbol
     *
     * @return TransactionCurrency
     */
    public function findBySymbol(string $currencySymbol): TransactionCurrency
    {
        $currency = TransactionCurrency::whereSymbol($currencySymbol)->first();
        if (is_null($currency)) {
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
     * @param Preference $preference
     *
     * @return TransactionCurrency
     */
    public function getCurrencyByPreference(Preference $preference): TransactionCurrency
    {
        $preferred = TransactionCurrency::where('code', $preference->data)->first();
        if (is_null($preferred)) {
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
        if (!is_null($rate)) {
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
