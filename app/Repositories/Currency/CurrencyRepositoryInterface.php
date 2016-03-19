<?php
declare(strict_types = 1);

namespace FireflyIII\Repositories\Currency;


use FireflyIII\Models\Preference;
use FireflyIII\Models\TransactionCurrency;
use Illuminate\Support\Collection;

/**
 * Interface CurrencyRepositoryInterface
 *
 * @package FireflyIII\Repositories\Currency
 */
interface CurrencyRepositoryInterface
{

    /**
     * @param TransactionCurrency $currency
     *
     * @return int
     */
    public function countJournals(TransactionCurrency $currency);

    /**
     * @return Collection
     */
    public function get();

    /**
     * @param Preference $preference
     *
     * @return TransactionCurrency
     */
    public function getCurrencyByPreference(Preference $preference);

    /**
     * @param array $data
     *
     * @return TransactionCurrency
     */
    public function store(array $data);

    /**
     * @param TransactionCurrency $currency
     * @param array               $data
     *
     * @return TransactionCurrency
     */
    public function update(TransactionCurrency $currency, array $data);

}
