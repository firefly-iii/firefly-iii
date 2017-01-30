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
use FireflyIII\User;
use Illuminate\Support\Collection;
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
     * CategoryRepository constructor.
     *
     * @param User $user
     */
    public function __construct(User $user)
    {
        $this->user = $user;
    }
    /**
     * @param User $user
     */
    public function setUser(User $user)
    {
        $this->user = $user;
    }
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
