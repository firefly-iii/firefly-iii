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
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace FireflyIII\Repositories\Currency;

use Carbon\Carbon;
use FireflyIII\Factory\TransactionCurrencyFactory;
use FireflyIII\Models\CurrencyExchangeRate;
use FireflyIII\Models\Preference;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use FireflyIII\Services\Internal\Destroy\CurrencyDestroyService;
use FireflyIII\Services\Internal\Update\CurrencyUpdateService;
use FireflyIII\User;
use Illuminate\Support\Collection;
use Log;

/**
 * Class CurrencyRepository.
 */
class CurrencyRepository implements CurrencyRepositoryInterface
{
    /** @var User */
    private $user;

    /**
     * Constructor.
     */
    public function __construct()
    {
        if ('testing' === env('APP_ENV')) {
            Log::warning(sprintf('%s should not be instantiated in the TEST environment!', \get_class($this)));
        }
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
        if (1 === $this->get()->count()) {
            return false;
        }

        // is the default currency for the user or the system
        $defaultCode = app('preferences')->getForUser($this->user, 'currencyPreference', config('firefly.default_currency', 'EUR'))->data;
        if ($currency->code === $defaultCode) {
            return false;
        }

        // is the default currency for the system
        $defaultSystemCode = config('firefly.default_currency', 'EUR');

        return !($currency->code === $defaultSystemCode);
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
        /** @var UserRepositoryInterface $repository */
        $repository = app(UserRepositoryInterface::class);


        if ($repository->hasRole($this->user, 'owner')) {
            /** @var CurrencyDestroyService $service */
            $service = app(CurrencyDestroyService::class);
            $service->destroy($currency);
        }

        return true;
    }

    /**
     * Find by currency code, return NULL if unfound.
     * Used in Import Currency!
     *
     * @param string $currencyCode
     *
     * @return TransactionCurrency|null
     */
    public function findByCodeNull(string $currencyCode): ?TransactionCurrency
    {
        return TransactionCurrency::where('code', $currencyCode)->first();
    }

    /**
     * Find by currency name or return null.
     * Used in Import Currency!
     *
     * @param string $currencyName
     *
     * @return TransactionCurrency
     */
    public function findByNameNull(string $currencyName): ?TransactionCurrency
    {
        return TransactionCurrency::whereName($currencyName)->first();
    }

    /**
     * Find by currency symbol or return NULL
     * Used in Import Currency!
     *
     * @param string $currencySymbol
     *
     * @return TransactionCurrency
     */
    public function findBySymbolNull(string $currencySymbol): ?TransactionCurrency
    {
        return TransactionCurrency::whereSymbol($currencySymbol)->first();
    }

    /**
     * Find by ID, return NULL if not found.
     * Used in Import Currency!
     *
     * @param int $currencyId
     *
     * @return TransactionCurrency|null
     */
    public function findNull(int $currencyId): ?TransactionCurrency
    {
        return TransactionCurrency::find($currencyId);
    }

    /**
     * @return Collection
     */
    public function get(): Collection
    {
        return TransactionCurrency::orderBy('code', 'ASC')->get();
    }

    /**
     * @param array $ids
     *
     * @return Collection
     */
    public function getByIds(array $ids): Collection
    {
        return TransactionCurrency::orderBy('code', 'ASC')->whereIn('id', $ids)->get();
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
     * Get currency exchange rate.
     *
     * @param TransactionCurrency $fromCurrency
     * @param TransactionCurrency $toCurrency
     * @param Carbon              $date
     *
     * @return CurrencyExchangeRate|null
     */
    public function getExchangeRate(TransactionCurrency $fromCurrency, TransactionCurrency $toCurrency, Carbon $date): ?CurrencyExchangeRate
    {
        if ($fromCurrency->id === $toCurrency->id) {
            $rate       = new CurrencyExchangeRate;
            $rate->rate = 1;
            $rate->id   = 0;

            return $rate;
        }
        /** @var CurrencyExchangeRate $rate */
        $rate = $this->user->currencyExchangeRates()
                           ->where('from_currency_id', $fromCurrency->id)
                           ->where('to_currency_id', $toCurrency->id)
                           ->where('date', $date->format('Y-m-d'))->first();
        if (null !== $rate) {
            Log::debug(sprintf('Found cached exchange rate in database for %s to %s on %s', $fromCurrency->code, $toCurrency->code, $date->format('Y-m-d')));

            return $rate;
        }

        return null;
    }

    /**
     * @param User $user
     */
    public function setUser(User $user): void
    {
        $this->user = $user;
    }

    /**
     * @param array $data
     *
     * @return TransactionCurrency|null
     */
    public function store(array $data): ?TransactionCurrency
    {
        /** @var TransactionCurrencyFactory $factory */
        $factory = app(TransactionCurrencyFactory::class);

        return $factory->create($data);
    }

    /**
     * @param TransactionCurrency $currency
     * @param array               $data
     *
     * @return TransactionCurrency
     */
    public function update(TransactionCurrency $currency, array $data): TransactionCurrency
    {
        /** @var CurrencyUpdateService $service */
        $service = app(CurrencyUpdateService::class);

        return $service->update($currency, $data);
    }
}
