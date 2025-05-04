<?php

/**
 * CurrencyRepository.php
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

namespace FireflyIII\Repositories\Currency;

use Carbon\Carbon;
use FireflyIII\Events\Preferences\UserGroupChangedDefaultCurrency;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Factory\TransactionCurrencyFactory;
use FireflyIII\Models\AccountMeta;
use FireflyIII\Models\AvailableBudget;
use FireflyIII\Models\Bill;
use FireflyIII\Models\BudgetLimit;
use FireflyIII\Models\CurrencyExchangeRate;
use FireflyIII\Models\RecurrenceTransaction;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use FireflyIII\Services\Internal\Destroy\CurrencyDestroyService;
use FireflyIII\Services\Internal\Update\CurrencyUpdateService;
use FireflyIII\Support\Repositories\UserGroup\UserGroupInterface;
use FireflyIII\Support\Repositories\UserGroup\UserGroupTrait;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Class CurrencyRepository.
 */
class CurrencyRepository implements CurrencyRepositoryInterface, UserGroupInterface
{
    use UserGroupTrait;

    /**
     * @throws FireflyException
     */
    public function currencyInUse(TransactionCurrency $currency): bool
    {
        $result = $this->currencyInUseAt($currency);

        return null !== $result;
    }

    /**
     * @throws FireflyException
     */
    public function currencyInUseAt(TransactionCurrency $currency): ?string
    {
        app('log')->debug(sprintf('Now in currencyInUse() for #%d ("%s")', $currency->id, $currency->code));
        $countJournals    = $this->countJournals($currency);
        if ($countJournals > 0) {
            app('log')->info(sprintf('Count journals is %d, return true.', $countJournals));

            return 'journals';
        }

        // is the only currency left
        if (1 === $this->getAll()->count()) {
            app('log')->info('Is the last currency in the system, return true. ');

            return 'last_left';
        }

        // is being used in accounts:
        $meta             = AccountMeta::where('name', 'currency_id')->where('data', \Safe\json_encode((string) $currency->id))->count();
        if ($meta > 0) {
            app('log')->info(sprintf('Used in %d accounts as currency_id, return true. ', $meta));

            return 'account_meta';
        }

        // second search using integer check.
        $meta             = AccountMeta::where('name', 'currency_id')->where('data', \Safe\json_encode((int) $currency->id))->count();
        if ($meta > 0) {
            app('log')->info(sprintf('Used in %d accounts as currency_id, return true. ', $meta));

            return 'account_meta';
        }

        // is being used in bills:
        $bills            = Bill::where('transaction_currency_id', $currency->id)->count();
        if ($bills > 0) {
            app('log')->info(sprintf('Used in %d bills as currency, return true. ', $bills));

            return 'bills';
        }

        // is being used in recurring transactions
        $recurringAmount  = RecurrenceTransaction::where('transaction_currency_id', $currency->id)->count();
        $recurringForeign = RecurrenceTransaction::where('foreign_currency_id', $currency->id)->count();

        if ($recurringAmount > 0 || $recurringForeign > 0) {
            app('log')->info(sprintf('Used in %d recurring transactions as (foreign) currency id, return true. ', $recurringAmount + $recurringForeign));

            return 'recurring';
        }

        // is being used in accounts (as integer)
        $meta             = AccountMeta::leftJoin('accounts', 'accounts.id', '=', 'account_meta.account_id')
            ->whereNull('accounts.deleted_at')
            ->where('account_meta.name', 'currency_id')->where('account_meta.data', \Safe\json_encode($currency->id))->count()
        ;
        if ($meta > 0) {
            app('log')->info(sprintf('Used in %d accounts as currency_id, return true. ', $meta));

            return 'account_meta';
        }

        // is being used in available budgets
        $availableBudgets = AvailableBudget::where('transaction_currency_id', $currency->id)->count();
        if ($availableBudgets > 0) {
            app('log')->info(sprintf('Used in %d available budgets as currency, return true. ', $availableBudgets));

            return 'available_budgets';
        }

        // is being used in budget limits
        $budgetLimit      = BudgetLimit::where('transaction_currency_id', $currency->id)->count();
        if ($budgetLimit > 0) {
            app('log')->info(sprintf('Used in %d budget limits as currency, return true. ', $budgetLimit));

            return 'budget_limits';
        }

        // is the default currency for the user or the system
        $count            = $this->userGroup->currencies()->where('transaction_currencies.id', $currency->id)->wherePivot('group_default', 1)->count();
        if ($count > 0) {
            app('log')->info('Is the default currency of the user, return true.');

            return 'current_default';
        }

        // is the default currency for the user or the system
        $count            = $this->userGroup->currencies()->where('transaction_currencies.id', $currency->id)->wherePivot('group_default', 1)->count();
        if ($count > 0) {
            app('log')->info('Is the default currency of the user group, return true.');

            return 'current_default';
        }

        app('log')->debug('Currency is not used, return false.');

        return null;
    }

    private function countJournals(TransactionCurrency $currency): int
    {
        $count = $currency->transactions()->whereNull('deleted_at')->count() + $currency->transactionJournals()->whereNull('deleted_at')->count();

        // also count foreign:
        return $count + Transaction::where('foreign_currency_id', $currency->id)->count();
    }

    /**
     * Returns ALL currencies, regardless of whether they are enabled or not.
     */
    public function getAll(): Collection
    {
        $all   = TransactionCurrency::orderBy('code', 'ASC')->get();
        $local = $this->get();

        return $all->map(static function (TransactionCurrency $current) use ($local) {
            $hasId                     = $local->contains(static fn (TransactionCurrency $entry) => $entry->id === $current->id);
            $isNative                  = $local->contains(static fn (TransactionCurrency $entry) => 1 === (int) $entry->pivot->group_default && $entry->id === $current->id);
            $current->userGroupEnabled = $hasId;
            $current->userGroupNative  = $isNative;

            return $current;
        });
    }

    public function get(): Collection
    {
        $all = $this->userGroup->currencies()->orderBy('code', 'ASC')->withPivot(['group_default'])->get();
        $all->map(static function (TransactionCurrency $current) { // @phpstan-ignore-line
            $current->userGroupEnabled = true;
            $current->userGroupNative  = 1 === (int) $current->pivot->group_default;

            return $current;
        });

        /** @var Collection */
        return $all;
    }

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

    public function disable(TransactionCurrency $currency): void
    {
        $this->userGroup->currencies()->detach($currency->id);
        $currency->enabled = false;
        $currency->save();
    }

    public function findByName(string $name): ?TransactionCurrency
    {
        return TransactionCurrency::where('name', $name)->first();
    }

    /**
     * Find by object, ID or code. Returns user default or system default.
     *
     * @throws FireflyException
     */
    public function findCurrency(?int $currencyId, ?string $currencyCode): TransactionCurrency
    {
        $result = $this->findCurrencyNull($currencyId, $currencyCode);

        if (null === $result) {
            app('log')->debug('Grabbing default currency for this user...');

            /** @var null|TransactionCurrency $result */
            $result = app('amount')->getNativeCurrencyByUserGroup($this->user->userGroup);
        }

        app('log')->debug(sprintf('Final result: %s', $result->code));
        if (false === $result->enabled) {
            app('log')->debug(sprintf('Also enabled currency %s', $result->code));
            $this->enable($result);
        }

        return $result;
    }

    /**
     * Find by object, ID or code. Returns NULL if nothing found.
     */
    public function findCurrencyNull(?int $currencyId, ?string $currencyCode): ?TransactionCurrency
    {
        app('log')->debug('Now in findCurrencyNull()');
        $result = $this->find((int) $currencyId);
        if (null === $result) {
            app('log')->debug(sprintf('Searching for currency with code %s...', $currencyCode));
            $result = $this->findByCode((string) $currencyCode);
        }
        if (null !== $result && false === $result->enabled) {
            app('log')->debug(sprintf('Also enabled currency %s', $result->code));
            $this->enable($result);
        }

        return $result;
    }

    #[\Override]
    public function find(int $currencyId): ?TransactionCurrency
    {
        return TransactionCurrency::find($currencyId);
    }

    /**
     * Find by currency code, return NULL if unfound.
     */
    public function findByCode(string $currencyCode): ?TransactionCurrency
    {
        return TransactionCurrency::where('code', $currencyCode)->first();
    }

    public function enable(TransactionCurrency $currency): void
    {
        $this->userGroup->currencies()->syncWithoutDetaching([$currency->id]);
        $currency->enabled = false;
        $currency->save();
    }

    /**
     * Returns the complete set of transactions but needs
     * no user object.
     */
    public function getCompleteSet(): Collection
    {
        return TransactionCurrency::where('enabled', true)->orderBy('code', 'ASC')->get();
    }

    /**
     * Get currency exchange rate.
     */
    public function getExchangeRate(TransactionCurrency $fromCurrency, TransactionCurrency $toCurrency, Carbon $date): ?CurrencyExchangeRate
    {
        if ($fromCurrency->id === $toCurrency->id) {
            $rate       = new CurrencyExchangeRate();
            $rate->rate = '1';
            $rate->id   = 0;

            return $rate;
        }

        /** @var null|CurrencyExchangeRate $rate */
        $rate = $this->user->currencyExchangeRates()
            ->where('from_currency_id', $fromCurrency->id)
            ->where('to_currency_id', $toCurrency->id)
            ->where('date', $date->format('Y-m-d'))->first()
        ;
        if (null !== $rate) {
            app('log')->debug(sprintf('Found cached exchange rate in database for %s to %s on %s', $fromCurrency->code, $toCurrency->code, $date->format('Y-m-d')));

            return $rate;
        }

        return null;
    }

    public function isFallbackCurrency(TransactionCurrency $currency): bool
    {
        return $currency->code === config('firefly.default_currency', 'EUR');
    }

    public function searchCurrency(string $search, int $limit): Collection
    {
        $query = TransactionCurrency::where('enabled', true);
        if ('' !== $search) {
            $query->whereLike('name', sprintf('%%%s%%', $search));
        }

        return $query->take($limit)->get();
    }

    /**
     * TODO must be a factory
     */
    public function setExchangeRate(TransactionCurrency $fromCurrency, TransactionCurrency $toCurrency, Carbon $date, float $rate): CurrencyExchangeRate
    {
        return CurrencyExchangeRate::create(
            [
                'user_id'          => $this->user->id,
                'user_group_id'    => $this->user->user_group_id,
                'from_currency_id' => $fromCurrency->id,
                'to_currency_id'   => $toCurrency->id,
                'date'             => $date,
                'date_tz'          => $date->format('e'),
                'rate'             => $rate,
            ]
        );
    }

    /**
     * @throws FireflyException
     */
    public function store(array $data): TransactionCurrency
    {
        /** @var TransactionCurrencyFactory $factory */
        $factory = app(TransactionCurrencyFactory::class);
        $result  = $factory->create($data);

        if (true === $data['enabled']) {
            $this->userGroup->currencies()->attach($result->id);
        }

        return $result;
    }

    public function update(TransactionCurrency $currency, array $data): TransactionCurrency
    {
        app('log')->debug('Now in update()');
        // can be true, false, null
        $enabled = array_key_exists('enabled', $data) ? $data['enabled'] : null;
        // can be true, false, but method only responds to "true".
        $default = array_key_exists('default', $data) ? $data['default'] : false;

        // remove illegal combo's:
        if (false === $enabled && true === $default) {
            $enabled = true;
        }

        // update currency with current user specific settings
        $currency->refreshForUser($this->user);

        // currency is enabled, must be disabled.
        if (false === $enabled) {
            app('log')->debug(sprintf('Disabled currency %s for user #%d', $currency->code, $this->userGroup->id));
            $this->userGroup->currencies()->detach($currency->id);
        }
        // currency must be enabled
        if (true === $enabled) {
            app('log')->debug(sprintf('Enabled currency %s for user #%d', $currency->code, $this->userGroup->id));
            $this->userGroup->currencies()->detach($currency->id);
            $this->userGroup->currencies()->syncWithoutDetaching([$currency->id => ['group_default' => false]]);
        }

        // currency must be made default.
        if (true === $default) {
            $this->makeDefault($currency);
        }

        /** @var CurrencyUpdateService $service */
        $service = app(CurrencyUpdateService::class);

        return $service->update($currency, $data);
    }

    public function makeDefault(TransactionCurrency $currency): void
    {
        $current = app('amount')->getNativeCurrencyByUserGroup($this->userGroup);
        app('log')->debug(sprintf('Enabled + made default currency %s for user #%d', $currency->code, $this->userGroup->id));
        $this->userGroup->currencies()->detach($currency->id);
        foreach ($this->userGroup->currencies()->get() as $item) {
            $this->userGroup->currencies()->updateExistingPivot($item->id, ['group_default' => false]);
        }
        $this->userGroup->currencies()->syncWithoutDetaching([$currency->id => ['group_default' => true]]);
        if ($current->id !== $currency->id) {
            Log::debug('Trigger on a different default currency.');
            // clear all native amounts through an event.
            event(new UserGroupChangedDefaultCurrency($this->userGroup));
        }
    }
}
