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
use FireflyIII\Models\AccountMeta;
use FireflyIII\Models\AvailableBudget;
use FireflyIII\Models\Bill;
use FireflyIII\Models\BudgetLimit;
use FireflyIII\Models\CurrencyExchangeRate;
use FireflyIII\Models\Preference;
use FireflyIII\Models\RecurrenceTransaction;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use FireflyIII\Services\Internal\Destroy\CurrencyDestroyService;
use FireflyIII\Services\Internal\Update\CurrencyUpdateService;
use FireflyIII\User;
use Illuminate\Database\Eloquent\Builder;
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
        if ('testing' === config('app.env')) {
            Log::warning(sprintf('%s should not be instantiated in the TEST environment!', get_class($this)));
        }
    }

    /**
     * @param TransactionCurrency $currency
     *
     * @return int
     */
    public function countJournals(TransactionCurrency $currency): int
    {
        return $currency->transactions()->whereNull('deleted_at')->count() + $currency->transactionJournals()->whereNull('deleted_at')->count();

    }

    /**
     * @param TransactionCurrency $currency
     *
     * @return bool
     */
    public function currencyInUse(TransactionCurrency $currency): bool
    {
        $result = $this->currencyInUseAt($currency);

        return null !== $result;
    }

    /**
     * @param TransactionCurrency $currency
     *
     * @return string|null
     */
    public function currencyInUseAt(TransactionCurrency $currency): ?string
    {
        Log::debug(sprintf('Now in currencyInUse() for #%d ("%s")', $currency->id, $currency->code));
        $countJournals = $this->countJournals($currency);
        if ($countJournals > 0) {
            Log::info(sprintf('Count journals is %d, return true.', $countJournals));

            return 'journals';
        }

        // is the only currency left
        if (1 === $this->getAll()->count()) {
            Log::info('Is the last currency in the system, return true. ');

            return 'last_left';
        }

        // is being used in accounts:
        $meta = AccountMeta::where('name', 'currency_id')->where('data', json_encode((string)$currency->id))->count();
        if ($meta > 0) {
            Log::info(sprintf('Used in %d accounts as currency_id, return true. ', $meta));

            return 'account_meta';
        }

        // is being used in bills:
        $bills = Bill::where('transaction_currency_id', $currency->id)->count();
        if ($bills > 0) {
            Log::info(sprintf('Used in %d bills as currency, return true. ', $bills));

            return 'bills';
        }

        // is being used in recurring transactions
        $recurringAmount  = RecurrenceTransaction::where('transaction_currency_id', $currency->id)->count();
        $recurringForeign = RecurrenceTransaction::where('foreign_currency_id', $currency->id)->count();

        if ($recurringAmount > 0 || $recurringForeign > 0) {
            Log::info(sprintf('Used in %d recurring transactions as (foreign) currency id, return true. ', $recurringAmount + $recurringForeign));

            return 'recurring';
        }

        // is being used in accounts (as integer)
        $meta = AccountMeta
            ::leftJoin('accounts', 'accounts.id', '=', 'account_meta.account_id')
            ->whereNull('accounts.deleted_at')
            ->where('account_meta.name', 'currency_id')->where('account_meta.data', json_encode((int)$currency->id))->count();
        if ($meta > 0) {
            Log::info(sprintf('Used in %d accounts as currency_id, return true. ', $meta));

            return 'account_meta';
        }

        // is being used in available budgets
        $availableBudgets = AvailableBudget::where('transaction_currency_id', $currency->id)->count();
        if ($availableBudgets > 0) {
            Log::info(sprintf('Used in %d available budgets as currency, return true. ', $availableBudgets));

            return 'available_budgets';
        }

        // is being used in budget limits
        $budgetLimit = BudgetLimit::where('transaction_currency_id', $currency->id)->count();
        if ($budgetLimit > 0) {
            Log::info(sprintf('Used in %d budget limits as currency, return true. ', $budgetLimit));

            return 'budget_limits';
        }

        // is the default currency for the user or the system
        $defaultCode = app('preferences')->getForUser($this->user, 'currencyPreference', config('firefly.default_currency', 'EUR'))->data;
        if ($currency->code === $defaultCode) {
            Log::info('Is the default currency of the user, return true.');

            return 'current_default';
        }

//        // is the default currency for the system
//        $defaultSystemCode = config('firefly.default_currency', 'EUR');
//        $result            = $currency->code === $defaultSystemCode;
//        if (true === $result) {
//            Log::info('Is the default currency of the SYSTEM, return true.');
//
//            return 'system_fallback';
//        }
        Log::debug('Currency is not used, return false.');

        return null;
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
     * Disables a currency
     *
     * @param TransactionCurrency $currency
     */
    public function disable(TransactionCurrency $currency): void
    {
        $currency->enabled = false;
        $currency->save();
    }

    /**
     * @param TransactionCurrency $currency
     * Enables a currency
     */
    public function enable(TransactionCurrency $currency): void
    {
        $currency->enabled = true;
        $currency->save();
    }

    /**
     * Find by ID, return NULL if not found.
     *
     * @param int $currencyId
     *
     * @return TransactionCurrency|null
     */
    public function find(int $currencyId): ?TransactionCurrency
    {
        return TransactionCurrency::find($currencyId);
    }

    /**
     * Find by currency code, return NULL if unfound.
     *
     * @param string $currencyCode
     *
     * @return TransactionCurrency|null
     */
    public function findByCode(string $currencyCode): ?TransactionCurrency
    {
        return TransactionCurrency::where('code', $currencyCode)->first();
    }

    /**
     * Find by currency code, return NULL if unfound.
     * Used in Import Currency!
     *
     * @param string $currencyCode
     *
     * @return TransactionCurrency|null
     * @deprecated
     */
    public function findByCodeNull(string $currencyCode): ?TransactionCurrency
    {
        return TransactionCurrency::where('code', $currencyCode)->first();
    }

    /**
     * Find by currency name.
     *
     * @param string $currencyName
     *
     * @return TransactionCurrency
     */
    public function findByName(string $currencyName): ?TransactionCurrency
    {
        return TransactionCurrency::whereName($currencyName)->first();
    }

    /**
     * Find by currency name or return null.
     * Used in Import Currency!
     *
     * @param string $currencyName
     *
     * @return TransactionCurrency
     * @deprecated
     */
    public function findByNameNull(string $currencyName): ?TransactionCurrency
    {
        return TransactionCurrency::whereName($currencyName)->first();
    }

    /**
     * Find by currency symbol.
     *
     * @param string $currencySymbol
     *
     * @return TransactionCurrency
     */
    public function findBySymbol(string $currencySymbol): ?TransactionCurrency
    {
        return TransactionCurrency::whereSymbol($currencySymbol)->first();
    }

    /**
     * Find by currency symbol or return NULL
     * Used in Import Currency!
     *
     * @param string $currencySymbol
     *
     * @return TransactionCurrency
     * @deprecated
     */
    public function findBySymbolNull(string $currencySymbol): ?TransactionCurrency
    {
        return TransactionCurrency::whereSymbol($currencySymbol)->first();
    }

    /**
     * Find by object, ID or code. Returns user default or system default.
     *
     * @param int|null $currencyId
     * @param string|null $currencyCode
     *
     * @return TransactionCurrency|null
     */
    public function findCurrency(?int $currencyId, ?string $currencyCode): TransactionCurrency
    {
        $result = $this->findCurrencyNull($currencyId, $currencyCode);

        if (null === $result) {
            Log::debug('Grabbing default currency for this user...');
            $result = app('amount')->getDefaultCurrencyByUser($this->user);
        }

        if (null === $result) {
            Log::debug('Grabbing EUR as fallback.');
            $result = $this->findByCode('EUR');
        }
        Log::debug(sprintf('Final result: %s', $result->code));
        if (false === $result->enabled) {
            Log::debug(sprintf('Also enabled currency %s', $result->code));
            $this->enable($result);
        }

        return $result;
    }

    /**
     * Find by object, ID or code. Returns NULL if nothing found.
     *
     * @param int|null $currencyId
     * @param string|null $currencyCode
     *
     * @return TransactionCurrency|null
     */
    public function findCurrencyNull(?int $currencyId, ?string $currencyCode): ?TransactionCurrency
    {
        Log::debug('Now in findCurrencyNull()');
        $result = $this->find((int)$currencyId);
        if (null === $result) {
            Log::debug(sprintf('Searching for currency with code %s...', $currencyCode));
            $result = $this->findByCode((string)$currencyCode);
        }
        if (null !== $result && false === $result->enabled) {
            Log::debug(sprintf('Also enabled currency %s', $result->code));
            $this->enable($result);
        }

        return $result;
    }

    /**
     * Find by ID, return NULL if not found.
     * Used in Import Currency!
     *
     * @param int $currencyId
     *
     * @return TransactionCurrency|null
     * @deprecated
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
        return TransactionCurrency::where('enabled', true)->orderBy('code', 'ASC')->get();
    }

    /**
     * @return Collection
     */
    public function getAll(): Collection
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
     * @param Carbon $date
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
     * Return a list of exchange rates with this currency.
     *
     * @param TransactionCurrency $currency
     *
     * @return Collection
     */
    public function getExchangeRates(TransactionCurrency $currency): Collection
    {
        /** @var CurrencyExchangeRate $rate */
        return $this->user->currencyExchangeRates()
                          ->where(
                              function (Builder $query) use ($currency) {
                                  $query->where('from_currency_id', $currency->id);
                                  $query->orWhere('to_currency_id', $currency->id);
                              }
                          )->get();
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
     * @param array $data
     *
     * @return TransactionCurrency
     */
    public function update(TransactionCurrency $currency, array $data): TransactionCurrency
    {
        /** @var CurrencyUpdateService $service */
        $service = app(CurrencyUpdateService::class);

        return $service->update($currency, $data);
    }

    /**
     * @param string $search
     * @return Collection
     */
    public function searchCurrency(string $search): Collection
    {
        $query = TransactionCurrency::where('enabled', 1);
        if ('' !== $search) {
            $query->where('name', 'LIKE', sprintf('%%%s%%', $search));
        }

        return $query->get();
    }
}
