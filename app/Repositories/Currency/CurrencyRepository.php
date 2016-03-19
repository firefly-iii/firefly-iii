<?php
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
    public function countJournals(TransactionCurrency $currency)
    {
        return $currency->transactionJournals()->count();
    }

    /**
     * @return Collection
     */
    public function get()
    {
        return TransactionCurrency::get();
    }

    /**
     * @param Preference $preference
     *
     * @return TransactionCurrency
     */
    public function getCurrencyByPreference(Preference $preference)
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
    public function store(array $data)
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
    public function update(TransactionCurrency $currency, array $data)
    {
        $currency->code   = $data['code'];
        $currency->symbol = $data['symbol'];
        $currency->name   = $data['name'];
        $currency->save();

        return $currency;
    }
}
