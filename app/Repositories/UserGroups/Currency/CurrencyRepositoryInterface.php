<?php
declare(strict_types=1);

namespace FireflyIII\Repositories\UserGroups\Currency;

use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\TransactionCurrency;
use Illuminate\Support\Collection;

interface CurrencyRepositoryInterface
{
    /**
     * @param TransactionCurrency $currency
     *
     * @return bool
     */
    public function currencyInUse(TransactionCurrency $currency): bool;

    /**
     * Currency is in use where exactly.
     *
     * @param TransactionCurrency $currency
     *
     * @return string|null
     */
    public function currencyInUseAt(TransactionCurrency $currency): ?string;

    /**
     * @param TransactionCurrency $currency
     *
     * @return bool
     */
    public function destroy(TransactionCurrency $currency): bool;

    /**
     * Disables a currency
     *
     * @param TransactionCurrency $currency
     */
    public function disable(TransactionCurrency $currency): void;

    /**
     * Enables a currency
     *
     * @param TransactionCurrency $currency
     */
    public function enable(TransactionCurrency $currency): void;

    /**
     * Find by ID, return NULL if not found.
     *
     * @param int $currencyId
     *
     * @return TransactionCurrency|null
     */
    public function find(int $currencyId): ?TransactionCurrency;

    /**
     * Find by object, ID or code. Returns user default or system default.
     *
     * @param int|null    $currencyId
     * @param string|null $currencyCode
     *
     * @return TransactionCurrency
     */
    public function findCurrency(?int $currencyId, ?string $currencyCode): TransactionCurrency;

    /**
     * Find by object, ID or code. Returns NULL if nothing found.
     *
     * @param int|null    $currencyId
     * @param string|null $currencyCode
     *
     * @return TransactionCurrency|null
     */
    public function findCurrencyNull(?int $currencyId, ?string $currencyCode): ?TransactionCurrency;

    /**
     * Get the user group's currencies.
     *
     * @return Collection
     */
    public function get(): Collection;

    /**
     * Get ALL currencies.
     *
     * @return Collection
     */
    public function getAll(): Collection;

    /**
     * @param TransactionCurrency $currency
     *
     * @return bool
     */
    public function isFallbackCurrency(TransactionCurrency $currency): bool;

    /**
     * @param TransactionCurrency $currency
     *
     * @return void
     */
    public function makeDefault(TransactionCurrency $currency): void;

    /**
     * @param string $search
     * @param int    $limit
     *
     * @return Collection
     */
    public function searchCurrency(string $search, int $limit): Collection;

    /**
     * @param array $data
     *
     * @return TransactionCurrency
     * @throws FireflyException
     */
    public function store(array $data): TransactionCurrency;

    /**
     * @param TransactionCurrency $currency
     * @param array               $data
     *
     * @return TransactionCurrency
     */
    public function update(TransactionCurrency $currency, array $data): TransactionCurrency;
}
