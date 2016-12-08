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

declare(strict_types = 1);

namespace FireflyIII\Repositories\Currency;


use FireflyIII\Models\Preference;
use FireflyIII\Models\TransactionCurrency;
use Illuminate\Support\Collection;

/**
 * Class CurrencyRepository
 *
 * @package FireflyIII\Repositories\Currency
 */
class CurrencyRepository implements CurrencyRepositoryInterface
{

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
        $currency = TransactionCurrency::whereCode($currencyCode)->first();
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
        $preferred = TransactionCurrency::whereCode($preference->data)->first();
        if (is_null($preferred)) {
            $preferred = TransactionCurrency::first();
        }

        return $preferred;
    }

    /**
     * @param array $data
     *
     * @return TransactionCurrency
     */
    public function store(array $data): TransactionCurrency
    {
        $currency = TransactionCurrency::create(
            [
                'name'   => $data['name'],
                'code'   => $data['code'],
                'symbol' => $data['symbol'],
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
        $currency->code   = $data['code'];
        $currency->symbol = $data['symbol'];
        $currency->name   = $data['name'];
        $currency->save();

        return $currency;
    }
}
