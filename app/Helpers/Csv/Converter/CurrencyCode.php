<?php
declare(strict_types = 1);
namespace FireflyIII\Helpers\Csv\Converter;

use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;

/**
 * Class CurrencyCode
 *
 * @package FireflyIII\Helpers\Csv\Converter
 */
class CurrencyCode extends BasicConverter implements ConverterInterface
{

    /**
     * @return TransactionCurrency
     */
    public function convert(): TransactionCurrency
    {
        /** @var CurrencyRepositoryInterface $repository */
        $repository = app('FireflyIII\Repositories\Currency\CurrencyRepositoryInterface');

        if (isset($this->mapped[$this->index][$this->value])) {
            $currency = $repository->find($this->mapped[$this->index][$this->value]);

            return $currency;
        }
        
        $currency = $repository->findByCode($this->value);


        return $currency;
    }
}
